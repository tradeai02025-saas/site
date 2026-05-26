<?php

namespace App\Models;

use Illuminate\Database\Eloquent\{Factories\HasFactory, Model, SoftDeletes};
use Illuminate\Support\Facades\Cache;
use OwenIt\Auditing\{Auditable, Contracts\Auditable as AuditableContract};

class Mensagem extends Model implements AuditableContract
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'mensagens';

    protected $fillable = [
        'conversa_id',
        'remetente_type',
        'remetente_id',
        'mensagem',
        'lida',
        'data_leitura',
    ];

    protected $casts = [
        'lida' => 'boolean',
        'data_leitura' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::created(function (Mensagem $mensagem) {
            self::invalidarCacheParticipantes($mensagem->conversa_id);
        });
    }

    public static function invalidarCacheParticipantes($conversaId, array $userIdsExtras = []): void
    {
        $userIds = ConversaParticipante::where('conversa_id', $conversaId)
            ->where('participante_type', User::class)
            ->whereNull('data_saida')
            ->pluck('participante_id')
            ->toArray();

        $todos = array_unique(array_merge($userIds, $userIdsExtras));

        foreach ($todos as $uid) {
            Cache::forget("unread_messages_{$uid}");
        }
    }

    public function scopeUnreadForUser($query, $userId)
    {
        return $query->whereHas('conversa', function ($q) use ($userId) {
            $q->whereNull('deleted_at')
                ->whereHas('participantes', function ($q2) use ($userId) {
                    $q2->where('participante_id', $userId)
                        ->where('participante_type', User::class)
                        ->whereNull('data_saida');
                });
        })
            ->where(function ($q) use ($userId) {
                $q->where('remetente_type', '!=', User::class)
                    ->orWhere('remetente_id', '!=', $userId);
            })
            ->where('lida', false);
    }

    public function conversa()
    {
        return $this->belongsTo(Conversa::class);
    }

    public function remetente()
    {
        return $this->morphTo();
    }

    public function anexos()
    {
        return $this->hasMany(MensagemAnexo::class);
    }
}