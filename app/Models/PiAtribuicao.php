<?php

/* app/Models/PiAtribuicao.php */

namespace App\Models;

use Illuminate\Database\Eloquent\{Factories\HasFactory, Model, SoftDeletes};
use Illuminate\Support\Facades\DB;
use OwenIt\Auditing\{Auditable, Contracts\Auditable as AuditableContract};

class PiAtribuicao extends Model implements AuditableContract
{
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'pi_atribuicoes';

    protected $fillable = [
        'cliente_id',
        'pi_id',
        'operacional_id',
        'data_atribuicao',
        'data_desatribuicao',
        'motivo_mudanca',
        'ativo',
        'principal',
    ];

    protected $casts = [
        'data_atribuicao' => 'datetime',
        'data_desatribuicao' => 'datetime',
        'ativo' => 'boolean',
        'principal' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (! $model->pi_id || ! $model->ativo) {
                return true;
            }

            return DB::transaction(function () use ($model) {
                $existente = static::withTrashed()
                    ->where('pi_id', $model->pi_id)
                    ->where('operacional_id', $model->operacional_id)
                    ->lockForUpdate()
                    ->first();

                if (! $existente) {
                    return true;
                }

                if ($existente->trashed()) {
                    $existente->restore();
                }

                $existente->update([
                    'cliente_id' => $model->cliente_id ?? $existente->cliente_id,
                    'data_atribuicao' => $model->data_atribuicao ?? now(),
                    'ativo' => true,
                    'principal' => $model->principal ?? $existente->principal,
                    'data_desatribuicao' => null,
                ]);

                $existente->refresh();
                foreach ($existente->getAttributes() as $key => $value) {
                    $model->setAttribute($key, $value);
                }
                $model->exists = true;
                $model->wasRecentlyCreated = false;
                $model->syncOriginal();

                return false;
            });
        });
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function pi()
    {
        return $this->belongsTo(Pi::class);
    }

    public function operacional()
    {
        return $this->belongsTo(GenOp::class, 'operacional_id');
    }

    public function scopeClienteOperacionais($query)
    {
        return $query->whereNull('pi_id')->where('ativo', true);
    }

    public function scopePiOperacionais($query)
    {
        return $query->whereNotNull('pi_id')->where('ativo', true);
    }

    public function scopeAtivos($query)
    {
        return $query->where('ativo', true);
    }
}
