<?php

/* app/Models/Representante.php */

namespace App\Models;

use Illuminate\Database\Eloquent\{Factories\HasFactory, Model, SoftDeletes};
use OwenIt\Auditing\{Auditable, Contracts\Auditable as AuditableContract};

class Representante extends Model implements AuditableContract
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'representantes';

    protected $fillable = [
        'fornecedor_id',
        'nome_completo',
        'cpf',
        'telefone_1',
        'telefone_2',
        'email_1',
        'email_2',
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