<?php

/* app/Models/ValidacaoDocumento.php */

namespace App\Models;

use Illuminate\Database\Eloquent\{Factories\HasFactory, Model, SoftDeletes};
use OwenIt\Auditing\{Auditable, Contracts\Auditable as AuditableContract};

class ValidacaoDocumento extends Model implements AuditableContract
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'validacoes_documentos';

    protected $fillable = [
        'documento_enviado_id',
        'operacional_id',
        'status_id',
        'data_validacao',
        'observacoes',
    ];

    protected $casts = [
        'data_validacao' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function documentoEnviado()
    {
        return $this->belongsTo(DocumentoEnviado::class);
    }

    public function operacional()
    {
        return $this->belongsTo(GenOp::class, 'operacional_id');
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }
}