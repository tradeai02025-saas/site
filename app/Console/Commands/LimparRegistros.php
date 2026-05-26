<?php

/* app/Console/Commands/LimparRegistros.php */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB, Storage};
use Carbon\Carbon;

class LimparRegistros extends Command
{
    protected $signature = 'sistema:registros 
                            {--users : Limpa usuários deletados}
                            {--clientes : Limpa clientes deletados}
                            {--fornecedores : Limpa fornecedores deletados}
                            {--pis : Limpa PIs deletados}
                            {--documentos : Limpa documentos deletados}
                            {--chats : Limpa conversas deletadas}
                            {--all : Limpa TUDO que está deletado}
                            {--dry-run : Mostra o que seria deletado sem executar}';

    protected $description = 'Remove PERMANENTEMENTE todos os registros com soft delete (deleted_at NOT NULL)';

    private $isDryRun = false;
    private $stats = [
        'users' => 0,
        'clientes' => 0,
        'fornecedores' => 0,
        'pis' => 0,
        'documentos' => 0,
        'chats' => 0,
        'arquivos_fisicos' => 0,
        'errors' => 0,
    ];

    public function handle()
    {
        $this->isDryRun = $this->option('dry-run');

        $this->info('🗑️  LIMPEZA PERMANENTE DE REGISTROS DELETADOS');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        if ($this->isDryRun) {
            $this->warn('🔍 MODO DRY RUN - Nada será deletado');
        } else {
            $this->error('⚠️  ATENÇÃO: Esta operação é IRREVERSÍVEL!');
            $this->warn('Todos os registros com deleted_at preenchido serão PERMANENTEMENTE excluídos.');
        }
        $this->newLine();

        $cleanAll = $this->option('all');

        if ($cleanAll || $this->option('chats')) {
            $this->cleanChats();
        }

        if ($cleanAll || $this->option('documentos')) {
            $this->cleanDocumentos();
        }

        if ($cleanAll || $this->option('pis')) {
            $this->cleanPis();
        }

        if ($cleanAll || $this->option('fornecedores')) {
            $this->cleanFornecedores();
        }

        if ($cleanAll || $this->option('clientes')) {
            $this->cleanClientes();
        }

        if ($cleanAll || $this->option('users')) {
            $this->cleanUsers();
        }

        if (!$cleanAll && !$this->option('users') && !$this->option('clientes') && 
            !$this->option('fornecedores') && !$this->option('pis') && 
            !$this->option('documentos') && !$this->option('chats')) {
            
            $this->warn('⚠️  Nenhuma opção selecionada!');
            $this->info('Use: --all ou especifique --users, --clientes, --fornecedores, --pis, --documentos, --chats');
            return Command::FAILURE;
        }

        $this->showSummary();

        return Command::SUCCESS;
    }

    private function cleanChats()
    {
        $this->info('💬 Limpando CONVERSAS deletadas...');
        
        $conversas = DB::table('conversas')
            ->whereNotNull('deleted_at')
            ->select('id', 'assunto', 'deleted_at')
            ->get();

        if ($conversas->isEmpty()) {
            $this->line('   ✓ Nenhuma conversa deletada');
            $this->newLine();
            return;
        }

        $this->line("   Encontradas: {$conversas->count()}");

        if ($this->isDryRun) {
            foreach ($conversas->take(10) as $conversa) {
                $this->line("   • [{$conversa->id}] {$conversa->assunto}");
            }
            if ($conversas->count() > 10) {
                $this->line("   • ... e mais " . ($conversas->count() - 10));
            }
            $this->newLine();
            return;
        }

        if (!$this->confirm("   Deletar PERMANENTEMENTE {$conversas->count()} conversa(s)?", false)) {
            $this->line('   ✗ Cancelado');
            $this->newLine();
            return;
        }

        $bar = $this->output->createProgressBar($conversas->count());
        $bar->start();

        foreach ($conversas as $conversa) {
            try {
                $anexos = DB::table('mensagens_anexos')
                    ->join('mensagens', 'mensagens_anexos.mensagem_id', '=', 'mensagens.id')
                    ->where('mensagens.conversa_id', $conversa->id)
                    ->select('mensagens_anexos.file_path')
                    ->get();

                foreach ($anexos as $anexo) {
                    if ($anexo->file_path && file_exists(storage_path('app/' . $anexo->file_path))) {
                        unlink(storage_path('app/' . $anexo->file_path));
                        $this->stats['arquivos_fisicos']++;
                    }
                }

                DB::table('mensagens_anexos')
                    ->whereIn('mensagem_id', function($query) use ($conversa) {
                        $query->select('id')
                            ->from('mensagens')
                            ->where('conversa_id', $conversa->id);
                    })
                    ->delete();

                DB::table('mensagens')
                    ->where('conversa_id', $conversa->id)
                    ->delete();

                DB::table('conversa_participantes')
                    ->where('conversa_id', $conversa->id)
                    ->delete();

                DB::table('conversa_transferencias')
                    ->where('conversa_id', $conversa->id)
                    ->delete();

                DB::table('conversas')
                    ->where('id', $conversa->id)
                    ->delete();

                $this->stats['chats']++;
            } catch (\Exception $e) {
                $this->stats['errors']++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
    }

    private function cleanDocumentos()
    {
        $this->info('📄 Limpando DOCUMENTOS deletados...');
        
        $documentos = DB::table('documentos_enviados')
            ->whereNotNull('deleted_at')
            ->select('id', 'fornecedor_id', 'deleted_at')
            ->get();

        if ($documentos->isEmpty()) {
            $this->line('   ✓ Nenhum documento deletado');
            $this->newLine();
            return;
        }

        $this->line("   Encontrados: {$documentos->count()}");

        if ($this->isDryRun) {
            $this->line("   • {$documentos->count()} documento(s) para deletar");
            $this->newLine();
            return;
        }

        if (!$this->confirm("   Deletar PERMANENTEMENTE {$documentos->count()} documento(s)?", false)) {
            $this->line('   ✗ Cancelado');
            $this->newLine();
            return;
        }

        $bar = $this->output->createProgressBar($documentos->count());
        $bar->start();

        $pisAfetadosIds = collect();

        foreach ($documentos as $doc) {
            try {
                $arquivos = DB::table('arquivos')
                    ->where('documento_enviado_id', $doc->id)
                    ->select('file_path')
                    ->get();

                foreach ($arquivos as $arquivo) {
                    if ($arquivo->file_path && file_exists(storage_path('app/' . $arquivo->file_path))) {
                        unlink(storage_path('app/' . $arquivo->file_path));
                        $this->stats['arquivos_fisicos']++;
                    }
                }

                DB::table('arquivos')
                    ->where('documento_enviado_id', $doc->id)
                    ->delete();

                DB::table('validacoes_documentos')
                    ->where('documento_enviado_id', $doc->id)
                    ->delete();

                DB::table('pi_documentos')
                    ->where('documento_enviado_id', $doc->id)
                    ->whereNotNull('deleted_at')
                    ->delete();

                $piIdsDesvinculados = DB::table('pi_documentos')
                    ->where('documento_enviado_id', $doc->id)
                    ->whereNull('deleted_at')
                    ->pluck('pi_id');

                if ($piIdsDesvinculados->isNotEmpty()) {
                    $pisAfetadosIds = $pisAfetadosIds->merge($piIdsDesvinculados);
                }

                DB::table('pi_documentos')
                    ->where('documento_enviado_id', $doc->id)
                    ->whereNull('deleted_at')
                    ->update(['documento_enviado_id' => null]);

                DB::table('documentos_enviados')
                    ->where('id', $doc->id)
                    ->delete();

                $this->stats['documentos']++;
            } catch (\Exception $e) {
                $this->stats['errors']++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $pisAfetadosIds = $pisAfetadosIds->unique()->filter();
        if ($pisAfetadosIds->isNotEmpty()) {
            $this->line("   Reconciliando {$pisAfetadosIds->count()} PI(s) afetado(s)...");
            foreach ($pisAfetadosIds as $piId) {
                $pi = \App\Models\Pi::find($piId);
                if ($pi && !$pi->concluido) {
                    $pi->atualizarStatusAutomaticamente();
                }
            }
        }
    }

    private function cleanPis()
    {
        $this->info('📋 Limpando PIs deletados...');
        
        $pis = DB::table('pis')
            ->whereNotNull('deleted_at')
            ->select('id', 'numero_pi', 'file_path', 'deleted_at')
            ->get();

        if ($pis->isEmpty()) {
            $this->line('   ✓ Nenhum PI deletado');
            $this->newLine();
            return;
        }

        $this->line("   Encontrados: {$pis->count()}");

        if ($this->isDryRun) {
            foreach ($pis->take(10) as $pi) {
                $this->line("   • PI {$pi->numero_pi}");
            }
            if ($pis->count() > 10) {
                $this->line("   • ... e mais " . ($pis->count() - 10));
            }
            $this->newLine();
            return;
        }

        if (!$this->confirm("   Deletar PERMANENTEMENTE {$pis->count()} PI(s)?", false)) {
            $this->line('   ✗ Cancelado');
            $this->newLine();
            return;
        }

        $bar = $this->output->createProgressBar($pis->count());
        $bar->start();

        foreach ($pis as $pi) {
            try {
                if ($pi->file_path && file_exists(storage_path('app/' . $pi->file_path))) {
                    unlink(storage_path('app/' . $pi->file_path));
                    $this->stats['arquivos_fisicos']++;
                }

                DB::table('pi_documentos')
                    ->where('pi_id', $pi->id)
                    ->delete();

                DB::table('pi_valores')
                    ->where('pi_id', $pi->id)
                    ->delete();

                DB::table('pi_atribuicoes')
                    ->where('pi_id', $pi->id)
                    ->delete();

                $conversaIds = DB::table('conversas')
                    ->where('pi_id', $pi->id)
                    ->pluck('id');

                if ($conversaIds->isNotEmpty()) {
                    $mensagemIds = DB::table('mensagens')
                        ->whereIn('conversa_id', $conversaIds)
                        ->pluck('id');

                    if ($mensagemIds->isNotEmpty()) {
                        $anexos = DB::table('mensagens_anexos')
                            ->whereIn('mensagem_id', $mensagemIds)
                            ->select('file_path')
                            ->get();

                        foreach ($anexos as $anexo) {
                            if ($anexo->file_path && file_exists(storage_path('app/' . $anexo->file_path))) {
                                unlink(storage_path('app/' . $anexo->file_path));
                                $this->stats['arquivos_fisicos']++;
                            }
                        }

                        DB::table('mensagens_anexos')
                            ->whereIn('mensagem_id', $mensagemIds)
                            ->delete();
                    }

                    DB::table('mensagens')
                        ->whereIn('conversa_id', $conversaIds)
                        ->delete();

                    DB::table('conversa_participantes')
                        ->whereIn('conversa_id', $conversaIds)
                        ->delete();

                    DB::table('conversa_transferencias')
                        ->whereIn('conversa_id', $conversaIds)
                        ->delete();

                    DB::table('conversas')
                        ->whereIn('id', $conversaIds)
                        ->delete();
                }

                DB::table('pis')
                    ->where('id', $pi->id)
                    ->delete();

                $this->stats['pis']++;
            } catch (\Exception $e) {
                $this->stats['errors']++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
    }

    private function cleanFornecedores()
    {
        $this->info('📦 Limpando FORNECEDORES deletados...');
        
        $fornecedores = DB::table('fornecedores')
            ->whereNotNull('deleted_at')
            ->select('id', 'razao_social', 'user_id', 'deleted_at')
            ->get();

        if ($fornecedores->isEmpty()) {
            $this->line('   ✓ Nenhum fornecedor deletado');
            $this->newLine();
            return;
        }

        $this->line("   Encontrados: {$fornecedores->count()}");

        if ($this->isDryRun) {
            foreach ($fornecedores->take(10) as $fornecedor) {
                $this->line("   • {$fornecedor->razao_social}");
            }
            if ($fornecedores->count() > 10) {
                $this->line("   • ... e mais " . ($fornecedores->count() - 10));
            }
            $this->newLine();
            return;
        }

        if (!$this->confirm("   Deletar PERMANENTEMENTE {$fornecedores->count()} fornecedor(es)?", false)) {
            $this->line('   ✗ Cancelado');
            $this->newLine();
            return;
        }

        $bar = $this->output->createProgressBar($fornecedores->count());
        $bar->start();

        foreach ($fornecedores as $fornecedor) {
            try {
                DB::table('enderecos')
                    ->where('enderecavel_type', 'App\\Models\\Fornecedor')
                    ->where('enderecavel_id', $fornecedor->id)
                    ->delete();

                DB::table('representantes')
                    ->where('fornecedor_id', $fornecedor->id)
                    ->delete();

                DB::table('dados_bancarios')
                    ->where('fornecedor_id', $fornecedor->id)
                    ->delete();

                DB::table('representante_fornecedores')
                    ->where('fornecedor_id', $fornecedor->id)
                    ->delete();

                DB::table('fornecedores')
                    ->where('id', $fornecedor->id)
                    ->delete();

                $this->stats['fornecedores']++;
            } catch (\Exception $e) {
                $this->stats['errors']++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
    }

    private function cleanClientes()
    {
        $this->info('🏢 Limpando CLIENTES deletados...');
        
        $clientes = DB::table('clientes')
            ->whereNotNull('deleted_at')
            ->select('id', 'razao_social', 'deleted_at')
            ->get();

        if ($clientes->isEmpty()) {
            $this->line('   ✓ Nenhum cliente deletado');
            $this->newLine();
            return;
        }

        $this->line("   Encontrados: {$clientes->count()}");

        if ($this->isDryRun) {
            foreach ($clientes->take(10) as $cliente) {
                $this->line("   • {$cliente->razao_social}");
            }
            if ($clientes->count() > 10) {
                $this->line("   • ... e mais " . ($clientes->count() - 10));
            }
            $this->newLine();
            return;
        }

        if (!$this->confirm("   Deletar PERMANENTEMENTE {$clientes->count()} cliente(s)?", false)) {
            $this->line('   ✗ Cancelado');
            $this->newLine();
            return;
        }

        $bar = $this->output->createProgressBar($clientes->count());
        $bar->start();

        foreach ($clientes as $cliente) {
            try {
                DB::table('enderecos')
                    ->where('enderecavel_type', 'App\\Models\\Cliente')
                    ->where('enderecavel_id', $cliente->id)
                    ->delete();

                DB::table('cliente_documentos_requeridos')
                    ->where('cliente_id', $cliente->id)
                    ->delete();

                DB::table('pi_atribuicoes')
                    ->where('cliente_id', $cliente->id)
                    ->whereNull('pi_id')
                    ->delete();

                DB::table('clientes')
                    ->where('id', $cliente->id)
                    ->delete();

                $this->stats['clientes']++;
            } catch (\Exception $e) {
                $this->stats['errors']++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
    }

    private function cleanUsers()
    {
        $this->info('👤 Limpando USUÁRIOS deletados...');
        
        $users = DB::table('users')
            ->whereNotNull('deleted_at')
            ->select('id', 'name', 'email', 'deleted_at')
            ->get();

        if ($users->isEmpty()) {
            $this->line('   ✓ Nenhum usuário deletado');
            $this->newLine();
            return;
        }

        $this->line("   Encontrados: {$users->count()}");

        if ($this->isDryRun) {
            foreach ($users->take(10) as $user) {
                $this->line("   • {$user->name} ({$user->email})");
            }
            if ($users->count() > 10) {
                $this->line("   • ... e mais " . ($users->count() - 10));
            }
            $this->newLine();
            return;
        }

        if (!$this->confirm("   Deletar PERMANENTEMENTE {$users->count()} usuário(s)?", false)) {
            $this->line('   ✗ Cancelado');
            $this->newLine();
            return;
        }

        $bar = $this->output->createProgressBar($users->count());
        $bar->start();

        foreach ($users as $user) {
            try {
                DB::table('conversa_participantes')
                    ->where('participante_type', 'App\\Models\\User')
                    ->where('participante_id', $user->id)
                    ->delete();

                DB::table('mensagens')
                    ->where('remetente_type', 'App\\Models\\User')
                    ->where('remetente_id', $user->id)
                    ->whereNotIn('conversa_id', function ($q) {
                        $q->select('id')->from('conversas')->whereNull('deleted_at');
                    })
                    ->delete();

                DB::table('gen_op')
                    ->where('user_id', $user->id)
                    ->delete();

                DB::table('representante_usuarios')
                    ->where('user_id', $user->id)
                    ->delete();

                DB::table('activity_log')
                    ->where('causer_type', 'App\\Models\\User')
                    ->where('causer_id', $user->id)
                    ->delete();

                DB::table('model_has_roles')
                    ->where('model_type', 'App\\Models\\User')
                    ->where('model_id', $user->id)
                    ->delete();

                DB::table('model_has_permissions')
                    ->where('model_type', 'App\\Models\\User')
                    ->where('model_id', $user->id)
                    ->delete();

                DB::table('password_reset_tokens')
                    ->where('email', $user->email)
                    ->delete();

                DB::table('users')
                    ->where('id', $user->id)
                    ->delete();

                $this->stats['users']++;
            } catch (\Exception $e) {
                $this->stats['errors']++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
    }

    private function showSummary()
    {
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('📊 RESUMO DA LIMPEZA');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        if ($this->isDryRun) {
            $this->warn('(Modo DRY RUN - Nada foi deletado)');
        }
        
        $total = array_sum(array_diff_key($this->stats, ['errors' => 0, 'arquivos_fisicos' => 0]));
        
        $this->table(
            ['Tipo', 'Quantidade'],
            [
                ['Usuários', $this->stats['users']],
                ['Clientes', $this->stats['clientes']],
                ['Fornecedores', $this->stats['fornecedores']],
                ['PIs', $this->stats['pis']],
                ['Documentos', $this->stats['documentos']],
                ['Conversas', $this->stats['chats']],
                ['Arquivos Físicos', $this->stats['arquivos_fisicos']],
                ['━━━━━━━━━━━━', '━━━━━━━━━━'],
                ['TOTAL REGISTROS', $total],
            ]
        );
        
        if ($this->stats['errors'] > 0) {
            $this->error("⚠️  Erros encontrados: {$this->stats['errors']}");
        }
        
        if ($total > 0 && !$this->isDryRun) {
            $this->info('✅ Limpeza concluída com sucesso!');
        }
        
        $this->newLine();
    }
}