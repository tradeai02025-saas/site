<?php

/* app/Models/DadosBancarios.php */

namespace App\Models;

use Illuminate\Database\Eloquent\{Factories\HasFactory, Model, SoftDeletes};
use OwenIt\Auditing\{Auditable, Contracts\Auditable as AuditableContract};

class DadosBancarios extends Model implements AuditableContract
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'dados_bancarios';

    protected $fillable = [
        'fornecedor_id',
        'banco',
        'agencia',
        'digito_agencia',
        'conta',
        'digito_conta',
        'tipo_conta',
        'pix_tipo',
        'pix_chave',
        'titular',
        'cpf_cnpj_titular',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function fornecedor()
    {
        return $this->belongsTo(Fornecedor::class);
    }
}