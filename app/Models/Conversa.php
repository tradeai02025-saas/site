<?php

/* app/Models/Conversa.php */

namespace App\Models;

use Illuminate\Database\Eloquent\{Factories\HasFactory, Model, SoftDeletes};
use OwenIt\Auditing\{Auditable, Contracts\Auditable as AuditableContract};

class Conversa extends Model implements AuditableContract
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'conversas';

    protected $fillable = [
        'assunto',
        'pi_id',
        'nivel_atual',
        'status_conversa',
        'criador_type',
        'criador_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function pi()
    {
        return $this->belongsTo(Pi::class);
    }

    public function criador()
    {
        return $this->morphTo();
    }

    public function participantes()
    {
        return $this->hasMany(ConversaParticipante::class);
    }

    public function participantesAtivos()
    {
        return $this->hasMany(ConversaParticipante::class)->whereNull('data_saida');
    }

    public function transferencias()
    {
        return $this->hasMany(ConversaTransferencia::class);
    }

    public function mensagens()
    {
        return $this->hasMany(Mensagem::class);
    }

    public function ultimaMensagem()
    {
        return $this->hasOne(Mensagem::class)->latest();
    }

    public function estaResolvida()
    {
        return $this->status_conversa === 'resolvida';
    }

    public function estaAberta()
    {
        return $this->status_conversa === 'aberta';
    }

    public function estaEscalada()
    {
        return $this->status_conversa === 'escalada';
    }

    public function podeSerEscalada()
    {
        return $this->nivel_atual !== 'admin' && !$this->estaResolvida();
    }
}