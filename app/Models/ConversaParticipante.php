<?php

/* app/Models/ConversaParticipante.php */

namespace App\Models;

use Illuminate\Database\Eloquent\{Factories\HasFactory, Model, SoftDeletes};
use OwenIt\Auditing\{Auditable, Contracts\Auditable as AuditableContract};

class ConversaParticipante extends Model implements AuditableContract
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'conversa_participantes';

    protected $fillable = [
        'conversa_id',
        'participante_type',
        'participante_id',
        'data_entrada',
        'data_saida',
        'nivel_participante',
    ];

    protected $casts = [
        'data_entrada' => 'datetime',
        'data_saida' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function conversa()
    {
        return $this->belongsTo(Conversa::class);
    }

    public function participante()
    {
        return $this->morphTo();
    }
}