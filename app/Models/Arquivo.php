<?php

/* app/Models/Arquivo.php */

namespace App\Models;

use Illuminate\Database\Eloquent\{Factories\HasFactory, Model, SoftDeletes};
use OwenIt\Auditing\{Auditable, Contracts\Auditable as AuditableContract};

class Arquivo extends Model implements AuditableContract
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'arquivos';

    protected $fillable = [
        'documento_enviado_id',
        'nome_original',
        'nome_arquivo',
        'file_path',
        'extensao',
        'tamanho',
        'mime_type',
    ];

    protected $casts = [
        'tamanho' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function documentoEnviado()
    {
        return $this->belongsTo(DocumentoEnviado::class);
    }
}