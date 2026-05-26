<?php

/* app/Models/User.php */

namespace App\Models;

use App\Mail\ResetPasswordMail;
use App\Services\MailRotator;
use Illuminate\Database\Eloquent\{Factories\HasFactory, SoftDeletes};
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use OwenIt\Auditing\{Auditable, Contracts\Auditable as AuditableContract};
use Spatie\Activitylog\{LogOptions, Models\Activity, Traits\LogsActivity};
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements AuditableContract
{
    use Auditable, HasFactory, HasRoles, LogsActivity, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['name', 'email'])->logOnlyDirty();
    }

    public function genOp()
    {
        return $this->hasOne(GenOp::class);
    }

    public function fornecedor()
    {
        return $this->hasOne(Fornecedor::class);
    }

    public function representanteUsuario()
    {
        return $this->hasOne(RepresentanteUsuario::class);
    }

    public function pisComoGerencial()
    {
        return $this->hasManyThrough(Pi::class, GenOp::class, 'user_id', 'gerencial_id');
    }

    public function pisComoOperacional()
    {
        return $this->hasManyThrough(Pi::class, GenOp::class, 'user_id', 'operacional_id');
    }

    public function conversasComoCriador()
    {
        return $this->morphMany(Conversa::class, 'criador');
    }

    public function participacoes()
    {
        return $this->morphMany(ConversaParticipante::class, 'participante');
    }

    public function participacoesAtivas()
    {
        return $this->morphMany(ConversaParticipante::class, 'participante')->whereNull('data_saida');
    }

    public function mensagensEnviadas()
    {
        return $this->morphMany(Mensagem::class, 'remetente');
    }

    public function conversasAtivas()
    {
        return $this->belongsToMany(Conversa::class, 'conversa_participantes', 'participante_id', 'conversa_id')
            ->wherePivot('participante_type', User::class)
            ->wherePivotNull('data_saida')
            ->withPivot('nivel_participante', 'data_entrada', 'data_saida')
            ->withTimestamps();
    }

    public function conversasNaoLidas()
    {
        $conversasIds = $this->participacoesAtivas()
            ->whereHas('conversa', function ($q) {
                $q->whereNull('deleted_at');
            })
            ->pluck('conversa_id');

        return Mensagem::whereIn('conversa_id', $conversasIds)
            ->where(function ($q) {
                $q->where('remetente_type', '!=', self::class)
                    ->orWhere('remetente_id', '!=', $this->id);
            })
            ->where('lida', false);
    }

    public function totalMensagensNaoLidas()
    {
        return $this->conversasNaoLidas()->count();
    }

    public function totalConversasAbertas()
    {
        $conversasIds = $this->participacoesAtivas()->pluck('conversa_id');

        return Conversa::whereIn('id', $conversasIds)
            ->whereIn('status_conversa', ['aberta', 'escalada'])
            ->count();
    }

    public function cargaDeTrabalho()
    {
        return [
            'conversas_abertas' => $this->totalConversasAbertas(),
            'mensagens_nao_lidas' => $this->totalMensagensNaoLidas(),
            'ativo_recentemente' => $this->estaAtivoRecentemente(24),
        ];
    }

    public function ultimaAtividade()
    {
        return Activity::where('causer_type', User::class)
            ->where('causer_id', $this->id)
            ->latest()
            ->first();
    }

    public function estaAtivoRecentemente($horasLimite = 24)
    {
        $ultimaAtividade = $this->ultimaAtividade();
        if (! $ultimaAtividade) {
            return false;
        }

        return $ultimaAtividade->created_at->diffInHours(now()) <= $horasLimite;
    }

    public function registrarAtividade($descricao = 'Acessou o sistema')
    {
        activity()->causedBy($this)->log($descricao);
    }

    public function sendPasswordResetNotification($token)
    {
        MailRotator::send(
            new ResetPasswordMail($this, $token),
            $this->email
        );
    }

    public function needsPasswordChange(): bool
    {
        return Activity::where('causer_type', self::class)
            ->where('causer_id', $this->id)
            ->where('description', 'Senha temporária enviada - requer troca obrigatória')
            ->whereJsonContains('properties->tipo', 'senha_temporaria')
            ->exists();
    }

    public function needsPhoneNumber(): bool
    {
        if (! $this->hasRole('fornecedor') || ! $this->fornecedor) {
            return false;
        }

        return empty($this->fornecedor->telefone);
    }

    public function needsBankingInfo(): bool
    {
        if (! $this->hasRole('fornecedor') || ! $this->fornecedor) {
            return false;
        }

        return ! $this->fornecedor->dadosBancarios()->exists();
    }

    public function needsRepresentanteInfo(): bool
    {
        if (! $this->hasRole('representante') || ! $this->representanteUsuario) {
            return false;
        }

        return $this->representanteUsuario->precisaCompletarCadastro();
    }

    public function getIncompleteProfileStep(): ?array
    {
        if ($this->needsPasswordChange()) {
            return [
                'step' => 'password',
                'tab' => 'senha',
                'title' => 'Alterar Senha Temporária',
            ];
        }

        if ($this->needsPhoneNumber()) {
            return [
                'step' => 'phone',
                'tab' => 'empresa',
                'title' => 'Cadastrar Telefone',
            ];
        }

        if ($this->needsBankingInfo()) {
            return [
                'step' => 'banking',
                'tab' => 'banking',
                'title' => 'Cadastrar Dados Bancários',
            ];
        }

        if ($this->needsRepresentanteInfo()) {
            return [
                'step' => 'representante',
                'tab' => 'empresa',
                'title' => 'Completar Dados da Empresa',
            ];
        }

        return null;
    }

    public function hasCompleteProfile(): bool
    {
        return $this->getIncompleteProfileStep() === null;
    }

    public function getProfileCompletionPercentage(): int
    {
        if ($this->hasRole('fornecedor')) {
            $steps = [
                'password' => ! $this->needsPasswordChange(),
                'phone' => ! $this->needsPhoneNumber(),
                'banking' => ! $this->needsBankingInfo(),
            ];
        } elseif ($this->hasRole('representante')) {
            $steps = [
                'password' => ! $this->needsPasswordChange(),
                'empresa' => ! $this->needsRepresentanteInfo(),
            ];
        } else {
            return 100;
        }

        $completed = count(array_filter($steps));
        $total = count($steps);

        return round(($completed / $total) * 100);
    }
}