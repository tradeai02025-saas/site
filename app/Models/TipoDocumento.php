<?php

/* app/Models/TipoDocumento.php */

namespace App\Models;

use App\Services\CacheService;
use Illuminate\Database\Eloquent\{Factories\HasFactory, Model, SoftDeletes};
use OwenIt\Auditing\{Auditable, Contracts\Auditable as AuditableContract};

class TipoDocumento extends Model implements AuditableContract
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'tipo_documentos';

    protected static function booted(): void
    {
        static::saved(fn () => CacheService::limparTiposDocumento());
        static::deleted(fn () => CacheService::limparTiposDocumento());
    }

    protected $fillable = [
        'nome',
        'descricao',
        'validade_dias',
        'validar',
        'avulso',
        'ativo',
    ];

    protected $casts = [
        'validade_dias' => 'integer',
        'validar' => 'boolean',
        'avulso' => 'boolean',
        'ativo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function clienteDocumentosRequeridos()
    {
        return $this->hasMany(ClienteDocumentoRequerido::class);
    }

    public function clientes()
    {
        return $this->belongsToMany(Cliente::class, 'cliente_documentos_requeridos')
            ->withTimestamps()
            ->withPivot('deleted_at');
    }

    public function piDocumentos()
    {
        return $this->hasMany(PiDocumento::class);
    }

    public function pis()
    {
        return $this->belongsToMany(Pi::class, 'pi_documentos')
            ->withTimestamps()
            ->withPivot('documento_enviado_id', 'status_id', 'observacoes', 'deleted_at');
    }

    public function documentosEnviados()
    {
        return $this->hasMany(DocumentoEnviado::class);
    }

    public function tipoVeiculos()
    {
        return $this->belongsToMany(TipoVeiculo::class, 'tipo_documento_veiculo')
            ->withTimestamps();
    }

    public function isGeral()
    {
        return $this->tipoVeiculos->isEmpty();
    }

    public function isTodosVeiculos()
    {
        $veiculosAtivos = TipoVeiculo::where('ativo', true)->count();
        return !$this->tipoVeiculos->isEmpty() && $this->tipoVeiculos->count() === $veiculosAtivos;
    }

    public function isUniversal()
    {
        return $this->isGeral() || $this->isTodosVeiculos();
    }

    public function aplicavelAoVeiculo($tipoVeiculoId)
    {
        if ($this->tipoVeiculos->isEmpty()) {
            return true;
        }
        return $this->tipoVeiculos->contains('id', $tipoVeiculoId);
    }
}