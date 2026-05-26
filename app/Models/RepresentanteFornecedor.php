<?php

/* app/Models/RepresentanteFornecedor.php */

namespace App\Models;

use Illuminate\Database\Eloquent\{Factories\HasFactory, Model, SoftDeletes};
use Illuminate\Support\Facades\Cache;
use OwenIt\Auditing\{Auditable, Contracts\Auditable as AuditableContract};

class RepresentanteFornecedor extends Model implements AuditableContract
{
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'representante_fornecedores';

    protected static function booted(): void
    {
        static::saved(function ($model) {
            if ($model->isDirty(['status_vinculo', 'ativo'])) {
                Cache::forget('pending_vinculos_count');
            }
        });

        static::deleted(function () {
            Cache::forget('pending_vinculos_count');
        });
    }

    protected $fillable = [
        'representante_usuario_id',
        'fornecedor_id',
        'status_vinculo',
        'aprovado_por',
        'data_vinculo',
        'data_aprovacao',
        'data_desvinculo',
        'motivo_rejeicao',
        'ativo',
    ];

    protected $casts = [
        'data_vinculo' => 'datetime',
        'data_aprovacao' => 'datetime',
        'data_desvinculo' => 'datetime',
        'ativo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function representanteUsuario()
    {
        return $this->belongsTo(RepresentanteUsuario::class, 'representante_usuario_id');
    }

    public function fornecedor()
    {
        return $this->belongsTo(Fornecedor::class);
    }

    public function aprovadoPor()
    {
        return $this->belongsTo(User::class, 'aprovado_por');
    }

    public function isPendente(): bool
    {
        return $this->status_vinculo === 'pendente';
    }

    public function isAprovado(): bool
    {
        return $this->status_vinculo === 'aprovado' && $this->ativo && ! $this->data_desvinculo;
    }

    public function isRejeitado(): bool
    {
        return $this->status_vinculo === 'rejeitado';
    }

    public function aprovar(int $aprovadorId): void
    {
        $this->update([
            'status_vinculo' => 'aprovado',
            'aprovado_por' => $aprovadorId,
            'data_aprovacao' => now(),
        ]);
    }

    public function rejeitar(int $aprovadorId, ?string $motivo = null): void
    {
        $this->update([
            'status_vinculo' => 'rejeitado',
            'aprovado_por' => $aprovadorId,
            'data_aprovacao' => now(),
            'motivo_rejeicao' => $motivo,
            'ativo' => false,
        ]);
    }

    public function desvincular(): void
    {
        $this->update([
            'data_desvinculo' => now(),
            'ativo' => false,
        ]);
    }

    public function scopeAtivos($query)
    {
        return $query->where('status_vinculo', 'aprovado')
            ->where('ativo', true)
            ->whereNull('data_desvinculo');
    }

    public function scopePendentes($query)
    {
        return $query->where('status_vinculo', 'pendente')
            ->where('ativo', true);
    }
}