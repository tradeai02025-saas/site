<?php

/* app/Models/Cliente.php */

namespace App\Models;

use Illuminate\Database\Eloquent\{Factories\HasFactory, Model, SoftDeletes};
use OwenIt\Auditing\{Auditable, Contracts\Auditable as AuditableContract};

class Cliente extends Model implements AuditableContract
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'clientes';

    protected $fillable = [
        'razao_social',
        'nome_fantasia',
        'nome_cliente',
        'cnpj',
        'inscricao_estadual',
        'inscricao_municipal',
        'praca',
        'telefone',
        'email_contato',
        'site',
        'observacoes',
        'exige_documento_por_veiculo',
    ];

    protected $casts = [
        'exige_documento_por_veiculo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function enderecos()
    {
        return $this->morphMany(Endereco::class, 'enderecavel');
    }

    public function pis()
    {
        return $this->hasMany(Pi::class);
    }

    public function clienteDocumentosRequeridos()
    {
        return $this->hasMany(ClienteDocumentoRequerido::class);
    }

    public function tipoDocumentosRequeridos()
    {
        return $this->belongsToMany(TipoDocumento::class, 'cliente_documentos_requeridos')
            ->withTimestamps()
            ->withPivot('deleted_at');
    }

    public function atribuicoesOperacionais()
    {
        return $this->hasMany(PiAtribuicao::class, 'cliente_id')
            ->whereNull('pi_id');
    }

    public function operacionais()
    {
        return $this->belongsToMany(GenOp::class, 'pi_atribuicoes', 'cliente_id', 'operacional_id')
            ->whereNull('pi_id')
            ->whereNull('pi_atribuicoes.deleted_at')
            ->whereNull('gen_op.deleted_at') 
            ->whereHas('user', function($q) {
                $q->whereNull('deleted_at');
            })
            ->withPivot('id', 'data_atribuicao', 'ativo', 'principal', 'deleted_at')
            ->withTimestamps();
    }

    public function operacionalPrincipal()
    {
        return $this->belongsToMany(GenOp::class, 'pi_atribuicoes', 'cliente_id', 'operacional_id')
            ->whereNull('pi_atribuicoes.pi_id')
            ->whereNull('pi_atribuicoes.deleted_at')
            ->where('pi_atribuicoes.ativo', true)
            ->wherePivot('principal', true)
            ->withPivot('principal', 'data_atribuicao', 'id')
            ->withTimestamps();
    }
}