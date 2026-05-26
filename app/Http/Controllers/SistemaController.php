<?php

/* app/Http/Controllers/SistemaController.php */

namespace App\Http\Controllers;

use Illuminate\Support\Facades\{Artisan, Log};

class SistemaController extends Controller
{
    public function limparCaches()
    {
        try {
            $commands = ['route:clear', 'config:clear', 'view:clear', 'cache:clear', 'optimize:clear'];
            $results = [];

            foreach ($commands as $cmd) {
                Artisan::call($cmd);
                $results[] = $cmd;
            }

            return response()->json([
                'success' => true,
                'mensagem' => 'Todos os caches foram limpos com sucesso.',
                'detalhes' => $results,
            ]);
        } catch (\Exception $e) {
            Log::channel('pis')->error('Erro ao limpar caches', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'mensagem' => 'Erro ao limpar caches: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function reconstruirCaches()
    {
        try {
            $results = [];

            $clear = ['route:clear', 'config:clear', 'view:clear', 'cache:clear', 'optimize:clear'];
            foreach ($clear as $cmd) {
                Artisan::call($cmd);
                $results[] = "Limpo: {$cmd}";
            }

            $cache = ['route:cache', 'config:cache', 'view:cache'];
            foreach ($cache as $cmd) {
                Artisan::call($cmd);
                $results[] = "Cacheado: {$cmd}";
            }

            return response()->json([
                'success' => true,
                'mensagem' => 'Todos os caches foram reconstruídos com sucesso.',
                'detalhes' => $results,
            ]);
        } catch (\Exception $e) {
            Log::channel('pis')->error('Erro ao reconstruir caches', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'mensagem' => 'Erro ao reconstruir caches: ' . $e->getMessage(),
            ], 500);
        }
    }
}