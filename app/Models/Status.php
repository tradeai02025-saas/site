<?php

/* app/Models/Status.php */

namespace App\Models;

use App\Services\CacheService;
use Illuminate\Database\Eloquent\{Factories\HasFactory, Model, SoftDeletes};
use OwenIt\Auditing\{Auditable, Contracts\Auditable as AuditableContract};

class Status extends Model implements AuditableContract
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'status';

    protected static function booted(): void
    {
        static::saved(fn () => CacheService::limparStatus());
        static::deleted(fn () => CacheService::limparStatus());
    }

    protected $fillable = [
        'nome',
        'tipo',
        'cor',
        'ativo',
        'descricao',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function scopeAtivos($query)
    {
        return $query->where('ativo', true);
    }

    public function scopeTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopePi($query)
    {
        return $query->where('tipo', 'pi')->where('ativo', true);
    }

    public function scopeDocumento($query)
    {
        return $query->where('tipo', 'documento')->where('ativo', true);
    }

    public function pis()
    {
        return $this->hasMany(Pi::class);
    }

    public function piDocumentos()
    {
        return $this->hasMany(PiDocumento::class);
    }

    public function documentosEnviados()
    {
        return $this->hasMany(DocumentoEnviado::class);
    }

    public function validacoes()
    {
        return $this->hasMany(ValidacaoDocumento::class);
    }
}