<?php

/* app/Models/MensagemAnexo.php */

namespace App\Models;

use Illuminate\Database\Eloquent\{Factories\HasFactory, Model, SoftDeletes};
use OwenIt\Auditing\{Auditable, Contracts\Auditable as AuditableContract};

class MensagemAnexo extends Model implements AuditableContract
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'mensagens_anexos';

    protected $fillable = [
        'mensagem_id',
        'nome_original',
        'nome_arquivo',
        'file_path',
        'tipo_arquivo',
        'extensao',
        'tamanho',
    ];

    protected $casts = [
        'tamanho' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function mensagem()
    {
        return $this->belongsTo(Mensagem::class);
    }
}