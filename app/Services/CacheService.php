<?php

/* app/Services/CacheService.php */

namespace App\Services;

use App\Models\Status;
use App\Models\TipoDocumento;
use App\Models\TipoVeiculo;
use Illuminate\Support\Facades\Cache;

class CacheService
{
    public static function statusPorTipo(string $tipo): \Illuminate\Database\Eloquent\Collection
    {
        return Cache::remember("status_{$tipo}_ativos", 3600, function () use ($tipo) {
            return Status::where('tipo', $tipo)
                ->where('ativo', true)
                ->orderBy('nome')
                ->get();
        });
    }

    public static function statusPorNome(string $tipo, string $nome): ?Status
    {
        return static::statusPorTipo($tipo)->firstWhere('nome', $nome);
    }

    public static function statusId(string $tipo, string $nome): ?int
    {
        return static::statusPorNome($tipo, $nome)?->id;
    }

    public static function tiposDocumentoAtivos(): \Illuminate\Database\Eloquent\Collection
    {
        return Cache::remember('tipos_documento_ativos', 3600, function () {
            return TipoDocumento::where('ativo', true)
                ->orderBy('nome')
                ->get();
        });
    }

    public static function tiposDocumentoComVeiculos(): \Illuminate\Database\Eloquent\Collection
    {
        return Cache::remember('tipos_documento_com_veiculos', 3600, function () {
            return TipoDocumento::where('ativo', true)
                ->with('tipoVeiculos')
                ->orderBy('nome')
                ->get();
        });
    }

    public static function tiposVeiculoAtivos(): \Illuminate\Database\Eloquent\Collection
    {
        return Cache::remember('tipos_veiculo_ativos', 3600, function () {
            return TipoVeiculo::where('ativo', true)
                ->orderBy('nome')
                ->get();
        });
    }

    public static function statusIdsPorNomes(string $tipo, array $nomes): array
    {
        return static::statusPorTipo($tipo)
            ->whereIn('nome', $nomes)
            ->pluck('id')
            ->toArray();
    }

    public static function limparStatus(): void
    {
        Cache::forget('status_pi_ativos');
        Cache::forget('status_documento_ativos');
        Cache::forget('status_geral_ativos');
        Cache::forget('status_visivel_pi_ids');
        Cache::forget('status_pendente_documento');
        Cache::forget('status_aprovado_documento_id');
    }

    public static function limparTiposDocumento(): void
    {
        Cache::forget('tipos_documento_ativos');
        Cache::forget('tipos_documento_com_veiculos');
    }

    public static function limparTiposVeiculo(): void
    {
        Cache::forget('tipos_veiculo_ativos');
    }

    public static function limparTudo(): void
    {
        static::limparStatus();
        static::limparTiposDocumento();
        static::limparTiposVeiculo();
        Cache::forget('pending_vinculos_count');
    }
}
