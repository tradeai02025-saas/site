<?php

/* app/Models/Pi.php */

namespace App\Models;

use Illuminate\Database\Eloquent\{Factories\HasFactory, Model, SoftDeletes};
use Illuminate\Support\Facades\DB;
use OwenIt\Auditing\{Auditable, Contracts\Auditable as AuditableContract};

class Pi extends Model implements AuditableContract
{
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'pis';

    protected $fillable = [
        'numero_pi',
        'cliente_id',
        'fornecedor_id',
        'gerencial_id',
        'operacional_id',
        'status_id',
        'tipo_veiculo_id',
        'grau_prioridade',
        'periodo',
        'PIT_n',
        'plano_n',
        'planilha_n',
        'file_path',
        'data_cadastro',
        'data_conclusao',
        'observacoes',
        'concluido',
        'pago',
        'data_pagamento',
        'envio_confirmado',
    ];

    protected $casts = [
        'grau_prioridade' => 'integer',
        'data_cadastro' => 'datetime',
        'data_conclusao' => 'datetime',
        'concluido' => 'boolean',
        'pago' => 'boolean',
        'data_pagamento' => 'datetime',
        'envio_confirmado' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function setNumeroPiAttribute($value)
    {
        $this->attributes['numero_pi'] = static::normalizarNumeroPi($value);
    }

    public static function normalizarNumeroPi($valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $apenasNumeros = preg_replace('/\D/', '', (string) $valor);

        if ($apenasNumeros === '' || $apenasNumeros === null) {
            return '';
        }

        $semZerosEsquerda = ltrim($apenasNumeros, '0');

        return $semZerosEsquerda === '' ? '0' : $semZerosEsquerda;
    }

    public static function variacoesNumeroPi($valor): array
    {
        $normalizado = static::normalizarNumeroPi($valor);

        if ($normalizado === null || $normalizado === '') {
            return [];
        }

        $variacoes = [$normalizado];

        for ($i = 1; $i <= 10; $i++) {
            $variacoes[] = str_repeat('0', $i).$normalizado;
        }

        return array_values(array_unique($variacoes));
    }

    public function scopeMesmoNumeroNormalizado($query, $valor)
    {
        $variacoes = static::variacoesNumeroPi($valor);

        if (empty($variacoes)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('numero_pi', $variacoes);
    }

    public function scopeConcluidos($query)
    {
        return $query->where('concluido', true);
    }

    public function scopePendentes($query)
    {
        return $query->where('concluido', false);
    }

    public function scopeDoFornecedor($query, $fornecedorId)
    {
        return $query->where('fornecedor_id', $fornecedorId);
    }

    public function scopeDosFornecedores($query, array $fornecedorIds)
    {
        return $query->whereIn('fornecedor_id', $fornecedorIds);
    }

    public function scopeDoCliente($query, $clienteId)
    {
        return $query->where('cliente_id', $clienteId);
    }

    public function scopeComRelacoes($query)
    {
        return $query->with(['cliente', 'fornecedor', 'status', 'gerencial.user', 'operacionais']);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function fornecedor()
    {
        return $this->belongsTo(Fornecedor::class);
    }

    public function gerencial()
    {
        return $this->belongsTo(GenOp::class, 'gerencial_id');
    }

    public function operacional()
    {
        return $this->belongsTo(GenOp::class, 'operacional_id');
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function tipoVeiculo()
    {
        return $this->belongsTo(TipoVeiculo::class);
    }

    public function valores()
    {
        return $this->hasOne(PiValor::class);
    }

    public function piDocumentos()
    {
        return $this->hasMany(PiDocumento::class);
    }

    public function documentosRequeridos()
    {
        return $this->piDocumentos()->with('tipoDocumento');
    }

    public function documentosAtendidos()
    {
        return $this->piDocumentos()->whereNotNull('documento_enviado_id')->with('documentoEnviado');
    }

    public function documentosPendentes()
    {
        return $this->piDocumentos()->whereNull('documento_enviado_id')->with('tipoDocumento');
    }

    public function atribuicoes()
    {
        return $this->hasMany(PiAtribuicao::class);
    }

    public function atribuicoesOperacionais()
    {
        return $this->hasMany(PiAtribuicao::class, 'pi_id');
    }

    public function operacionais()
    {
        return $this->belongsToMany(GenOp::class, 'pi_atribuicoes', 'pi_id', 'operacional_id')
            ->where('pi_atribuicoes.ativo', true)
            ->whereNull('pi_atribuicoes.deleted_at')
            ->withPivot('cliente_id', 'principal', 'data_atribuicao', 'data_desatribuicao', 'id')
            ->withTimestamps();
    }

    public function operacionaisAtivos()
    {
        return $this->operacionais()->wherePivot('ativo', true);
    }

    public function operacionalPrincipalAtribuido()
    {
        return $this->hasOne(PiAtribuicao::class, 'pi_id')
            ->where('ativo', true)
            ->where('principal', true)
            ->whereNull('deleted_at')
            ->with('operacional');
    }

    public function operacionalPrincipalDoCliente()
    {
        return $this->hasOneThrough(
            GenOp::class,
            PiAtribuicao::class,
            'cliente_id',
            'id',
            'cliente_id',
            'operacional_id'
        )
            ->whereNull('pi_atribuicoes.pi_id')
            ->where('pi_atribuicoes.ativo', true)
            ->where('pi_atribuicoes.principal', true)
            ->whereNull('pi_atribuicoes.deleted_at');
    }

    public function getOperacionalPrincipalAttribute()
    {
        $atribuicao = PiAtribuicao::where('pi_id', $this->id)
            ->where('ativo', true)
            ->where('principal', true)
            ->whereNull('deleted_at')
            ->with('operacional')
            ->first();

        if ($atribuicao && $atribuicao->operacional) {
            return $atribuicao->operacional;
        }

        if ($this->operacional_id) {
            return GenOp::find($this->operacional_id);
        }

        return null;
    }

    public function operacionalTemAcesso($genOpId): bool
    {
        $temAtribuicao = $this->operacionais()
            ->where('gen_op.id', $genOpId)
            ->exists();

        if ($temAtribuicao) {
            return true;
        }

        if ((int) $this->operacional_id === (int) $genOpId) {
            return GenOp::where('id', $genOpId)
                ->whereNull('deleted_at')
                ->whereHas('user', function ($q) {
                    $q->whereNull('deleted_at');
                })
                ->exists();
        }

        return false;
    }

    public function conversas()
    {
        return $this->hasMany(Conversa::class);
    }

    public function atualizarStatusAutomaticamente(): ?string
    {
        if ($this->concluido) {
            return null;
        }

        return DB::transaction(function () {
            $totalDocumentos = $this->piDocumentos()
                ->where(function ($q) {
                    $q->where('opcional', false)
                        ->orWhereNotNull('documento_enviado_id');
                })
                ->count();

            if ($totalDocumentos === 0) {
                $statusGerado = \App\Models\Status::where('tipo', 'pi')
                    ->where('nome', 'Gerado')
                    ->where('ativo', true)
                    ->first();

                if ($statusGerado && $this->status_id !== $statusGerado->id) {
                    $this->update(['status_id' => $statusGerado->id]);

                    return 'Gerado';
                }

                return null;
            }

            $docsEnviados = $this->piDocumentos()
                ->whereNotNull('documento_enviado_id')
                ->count();

            $docsAprovados = $this->piDocumentos()
                ->whereNotNull('documento_enviado_id')
                ->whereHas('documentoEnviado', function ($q) {
                    $q->whereHas('status', function ($sq) {
                        $sq->where('nome', 'Aprovado');
                    });
                })
                ->count();

            $docsComProblema = $this->piDocumentos()
                ->whereNotNull('documento_enviado_id')
                ->whereHas('documentoEnviado', function ($q) {
                    $q->whereHas('status', function ($sq) {
                        $sq->whereIn('nome', ['Reprovado', 'Aguardando Reenvio']);
                    });
                })
                ->count();

            $docsPendentesEnvio = $totalDocumentos - $docsEnviados;

            if ($totalDocumentos === $docsAprovados && $docsAprovados > 0) {
                $novoStatusNome = 'Em Processo';
                if (! $this->envio_confirmado) {
                    $this->update(['envio_confirmado' => true]);
                }

                $novoStatus = \App\Models\Status::where('tipo', 'pi')
                    ->where('nome', $novoStatusNome)
                    ->where('ativo', true)
                    ->first();

                if ($novoStatus && $this->status_id !== $novoStatus->id) {
                    $this->update(['status_id' => $novoStatus->id]);
                    return $novoStatusNome;
                }

                return null;
            }

            if ($docsComProblema > 0 && $docsPendentesEnvio === 0) {
                $novoStatusNome = 'Reenvio de Documentos';
                if ($this->envio_confirmado) {
                    $this->update(['envio_confirmado' => false]);
                }
            } elseif ($docsPendentesEnvio === 0 && $docsEnviados === $totalDocumentos) {
                if (! $this->envio_confirmado) {
                    $this->update(['envio_confirmado' => true]);
                }
                $novoStatusNome = 'Em Processo';
            } else {
                $novoStatusNome = 'Aguardando Documentos';
            }

            $novoStatus = \App\Models\Status::where('tipo', 'pi')
                ->where('nome', $novoStatusNome)
                ->where('ativo', true)
                ->first();

            if ($novoStatus && $this->status_id !== $novoStatus->id) {
                $this->update(['status_id' => $novoStatus->id]);

                return $novoStatusNome;
            }

            return null;
        });
    }

    public function todosDocumentosAprovados(): bool
    {
        $total = $this->piDocumentos()
            ->where(function ($q) {
                $q->where('opcional', false)
                    ->orWhereNotNull('documento_enviado_id');
            })
            ->count();

        if ($total === 0) {
            return false;
        }

        $aprovados = $this->piDocumentos()
            ->whereNotNull('documento_enviado_id')
            ->whereHas('documentoEnviado', function ($q) {
                $q->whereHas('status', function ($sq) {
                    $sq->where('nome', 'Aprovado');
                });
            })
            ->count();

        return $total === $aprovados;
    }

    public function invalidarDocumentosVencidos(): int
    {
        $hoje = now()->toDateString();

        $statusAguardandoReenvio = \App\Models\Status::where('tipo', 'documento')
            ->where('nome', 'Aguardando Reenvio')
            ->where('ativo', true)
            ->first();

        if (! $statusAguardandoReenvio) {
            return 0;
        }

        $piDocsVencidos = $this->piDocumentos()
            ->whereNotNull('documento_enviado_id')
            ->whereHas('documentoEnviado', function ($q) use ($hoje) {
                $q->whereHas('status', function ($sq) {
                    $sq->where('nome', 'Aprovado');
                })
                    ->whereNotNull('data_validade_documento')
                    ->where('data_validade_documento', '<', $hoje);
            })
            ->with('documentoEnviado.status')
            ->get();

        if ($piDocsVencidos->isEmpty()) {
            return 0;
        }

        $invalidados = 0;
        $idsProcessados = [];

        DB::transaction(function () use ($piDocsVencidos, $statusAguardandoReenvio, &$invalidados, &$idsProcessados) {
            foreach ($piDocsVencidos as $piDoc) {
                $docEnviado = $piDoc->documentoEnviado;

                if ($docEnviado->status && $docEnviado->status->nome === 'Aprovado') {
                    $dataValidade = $docEnviado->data_validade_documento
                        ? $docEnviado->data_validade_documento->format('d/m/Y')
                        : 'N/A';

                    $docEnviado->update([
                        'valido' => false,
                        'status_id' => $statusAguardandoReenvio->id,
                    ]);

                    if (! in_array($docEnviado->id, $idsProcessados)) {
                        \App\Models\ValidacaoDocumento::create([
                            'documento_enviado_id' => $docEnviado->id,
                            'operacional_id' => null,
                            'status_id' => $statusAguardandoReenvio->id,
                            'data_validacao' => now(),
                            'observacoes' => "Documento vencido em {$dataValidade} — invalidado automaticamente pelo sistema",
                        ]);
                        $idsProcessados[] = $docEnviado->id;
                    }

                    \App\Models\PiDocumento::where('documento_enviado_id', $docEnviado->id)
                        ->whereNull('deleted_at')
                        ->update(['status_id' => $statusAguardandoReenvio->id]);

                    $invalidados++;
                }
            }
        });

        if ($invalidados > 0) {
            $this->refresh();
            $this->atualizarStatusAutomaticamente();
        }

        return $invalidados;
    }
}