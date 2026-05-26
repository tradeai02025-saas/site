<?php

/* app/Models/PiDocumento.php */

namespace App\Models;

use Illuminate\Database\Eloquent\{Factories\HasFactory, Model, SoftDeletes};
use OwenIt\Auditing\{Auditable, Contracts\Auditable as AuditableContract};

class PiDocumento extends Model implements AuditableContract
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'pi_documentos';

    protected $fillable = [
        'pi_id',
        'tipo_documento_id',
        'documento_enviado_id',
        'status_id',
        'opcional',
        'observacoes',
    ];

    protected $casts = [
        'opcional' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function pi()
    {
        return $this->belongsTo(Pi::class);
    }

    public function tipoDocumento()
    {
        return $this->belongsTo(TipoDocumento::class);
    }

    public function documentoEnviado()
    {
        return $this->belongsTo(DocumentoEnviado::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function estaPendente()
    {
        return is_null($this->documento_enviado_id);
    }

    public function estaAtendido()
    {
        return !is_null($this->documento_enviado_id);
    }
}