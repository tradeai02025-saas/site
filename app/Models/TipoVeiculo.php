<?php

/* app/Models/TipoVeiculo.php */

namespace App\Models;

use App\Services\CacheService;
use Illuminate\Database\Eloquent\{Factories\HasFactory, Model, SoftDeletes};
use OwenIt\Auditing\{Auditable, Contracts\Auditable as AuditableContract};

class TipoVeiculo extends Model implements AuditableContract
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'tipo_veiculos';

    protected static function booted(): void
    {
        static::saved(fn () => CacheService::limparTiposVeiculo());
        static::deleted(fn () => CacheService::limparTiposVeiculo());
    }

    protected $fillable = [
        'nome',
        'descricao',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function tipoDocumentos()
    {
        return $this->belongsToMany(TipoDocumento::class, 'tipo_documento_veiculo')
            ->withTimestamps();
    }

    public function pis()
    {
        return $this->hasMany(Pi::class);
    }
}