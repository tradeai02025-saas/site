<?php

/* app/Models/GenOp.php */

namespace App\Models;

use Illuminate\Database\Eloquent\{Factories\HasFactory, Model, SoftDeletes};
use OwenIt\Auditing\{Auditable, Contracts\Auditable as AuditableContract};

class GenOp extends Model implements AuditableContract
{
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'gen_op';

    protected $fillable = [
        'user_id',
        'nome_completo',
        'telefone',
        'departamento',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function roles()
    {
        return $this->hasOneThrough(
            \Spatie\Permission\Models\Role::class,
            User::class,
            'id',
            'id',
            'user_id',
            'id'
        )->join('model_has_roles', function ($join) {
            $join->on('roles.id', '=', 'model_has_roles.role_id')
                ->where('model_has_roles.model_type', '=', User::class)
                ->whereColumn('model_has_roles.model_id', 'users.id');
        });
    }

    public function getRolesAttribute()
    {
        return $this->user?->roles ?? collect();
    }

    public function getNameAttribute()
    {
        return $this->nome_completo ?? $this->user?->name;
    }

    public function pisComoGerencial()
    {
        return $this->hasMany(Pi::class, 'gerencial_id');
    }

    public function pisComoOperacional()
    {
        return $this->hasMany(Pi::class, 'operacional_id');
    }

    public function atribuicoes()
    {
        return $this->hasMany(PiAtribuicao::class, 'operacional_id');
    }

    public function atribuicoesClientes()
    {
        return $this->hasMany(PiAtribuicao::class, 'operacional_id')
            ->whereNull('pi_id');
    }

    public function atribuicoesPis()
    {
        return $this->hasMany(PiAtribuicao::class, 'operacional_id')
            ->whereNotNull('pi_id');
    }

    public function clientes()
    {
        return $this->belongsToMany(Cliente::class, 'pi_atribuicoes', 'operacional_id', 'cliente_id')
            ->whereNull('pi_atribuicoes.pi_id')
            ->where('pi_atribuicoes.ativo', true)
            ->withPivot('principal', 'data_atribuicao', 'id')
            ->withTimestamps();
    }

    public function pisAtribuidos()
    {
        return $this->belongsToMany(Pi::class, 'pi_atribuicoes', 'operacional_id', 'pi_id')
            ->where('pi_atribuicoes.ativo', true)
            ->whereNull('pi_atribuicoes.deleted_at')
            ->withPivot('cliente_id', 'principal', 'data_atribuicao', 'id')
            ->withTimestamps();
    }

    public function validacoes()
    {
        return $this->hasMany(ValidacaoDocumento::class, 'operacional_id');
    }

    public function conversasComoParticipante()
    {
        return $this->morphMany(ConversaParticipante::class, 'participante');
    }

    public function mensagensEnviadas()
    {
        return $this->morphMany(Mensagem::class, 'remetente');
    }
}
