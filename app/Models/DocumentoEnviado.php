<?php

/* app/Models/DocumentoEnviado.php */

namespace App\Models;

use Illuminate\Database\Eloquent\{Factories\HasFactory, Model, SoftDeletes};
use OwenIt\Auditing\{Auditable, Contracts\Auditable as AuditableContract};

class DocumentoEnviado extends Model implements AuditableContract
{
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'documentos_enviados';

    protected $fillable = [
        'fornecedor_id',
        'tipo_documento_id',
        'status_id',
        'data_envio',
        'data_validade_documento',
        'valido',
    ];

    protected $casts = [
        'data_envio' => 'datetime',
        'data_validade_documento' => 'date',
        'valido' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $attributes = [
        'valido' => false,
    ];

    public function fornecedor()
    {
        return $this->belongsTo(Fornecedor::class);
    }

    public function tipoDocumento()
    {
        return $this->belongsTo(TipoDocumento::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function arquivos()
    {
        return $this->hasMany(Arquivo::class);
    }

    public function validacoes()
    {
        return $this->hasMany(ValidacaoDocumento::class);
    }

    public function piDocumentos()
    {
        return $this->hasMany(PiDocumento::class);
    }

    public function pisVinculados()
    {
        return $this->belongsToMany(Pi::class, 'pi_documentos', 'documento_enviado_id', 'pi_id')
            ->withTimestamps()
            ->withPivot('tipo_documento_id', 'status_id', 'observacoes', 'deleted_at');
    }

    public function scopeValidos($query)
    {
        return $query->where('valido', true);
    }

    public function scopeVencendo($query, int $dias = 14)
    {
        return $query->where('valido', true)
            ->whereBetween('data_validade_documento', [
                now()->toDateString(),
                now()->addDays($dias)->toDateString(),
            ]);
    }

    public function scopeVencidos($query)
    {
        return $query->where(function ($q) {
            $q->where('valido', false)
                ->orWhere('data_validade_documento', '<', now()->toDateString());
        });
    }

    public function scopeComStatus($query, array $nomes)
    {
        return $query->whereHas('status', fn ($q) => $q->whereIn('nome', $nomes));
    }

    public function scopePendentes($query)
    {
        return $query->comStatus(['Pendente', 'Aguardando validação']);
    }

    public function scopeReprovados($query)
    {
        return $query->comStatus(['Reprovado', 'Aguardando Reenvio']);
    }

    public function scopeAprovados($query)
    {
        return $query->comStatus(['Aprovado']);
    }

    public function scopeDoFornecedor($query, $fornecedorId)
    {
        return $query->where('fornecedor_id', $fornecedorId);
    }

    public function scopeDosFornecedores($query, array $fornecedorIds)
    {
        return $query->whereIn('fornecedor_id', $fornecedorIds);
    }

    public function isValido()
    {
        if (! $this->valido) {
            return false;
        }

        if (is_null($this->data_validade_documento)) {
            return true;
        }

        return $this->data_validade_documento->endOfDay() >= now();
    }

    public function isVencendo()
    {
        if (is_null($this->data_validade_documento)) {
            return false;
        }

        $diasRestantes = now()->diffInDays($this->data_validade_documento, false);

        return $diasRestantes >= 0 && $diasRestantes <= 10;
    }

    public function isVencido()
    {
        if (is_null($this->data_validade_documento)) {
            return false;
        }

        return $this->data_validade_documento->endOfDay() < now();
    }
}