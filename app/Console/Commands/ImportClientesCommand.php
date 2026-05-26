<?php

/* app/Console/Commands/ImportClientesCommand.php */

namespace App\Console\Commands;

use App\Models\{Cliente, TipoDocumento, ClienteDocumentoRequerido, Fornecedor};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB, Storage};

class ImportClientesCommand extends Command
{
    protected $signature = 'clientes:import 
                          {--dry-run : Simula a importação sem salvar dados}
                          {--force : Sobrescreve clientes existentes}
                          {--file= : Caminho personalizado do arquivo CSV}';

    protected $description = 'Importa clientes do arquivo CSV incluindo documentos requeridos';

    private $tiposDocumentos;
    private $stats = [
        'total' => 0,
        'importados' => 0,
        'atualizados' => 0,
        'erros' => 0,
        'pulados' => 0,
    ];

    public function handle()
    {
        $this->info('🚀 Iniciando importação de clientes...');
        $this->newLine();

        if ($this->option('dry-run')) {
            $this->warn('⚠️  MODO DRY-RUN: Nenhum dado será salvo no banco');
            $this->newLine();
        }

        $this->carregarTiposDocumentos();

        $filePath = $this->option('file') ?? 'clientes.csv';
        
        $paths = [
            'private/' . $filePath,
            $filePath,
        ];
        
        $foundPath = null;
        foreach ($paths as $path) {
            if (Storage::exists($path)) {
                $foundPath = $path;
                break;
            }
        }
        
        if (!$foundPath) {
            $this->error("❌ Arquivo não encontrado!");
            $this->info("   Tentou em:");
            foreach ($paths as $path) {
                $this->info("   - " . Storage::path($path));
            }
            return 1;
        }
        
        $filePath = $foundPath;

        try {
            $filePath = Storage::path($filePath);
            
            if (!file_exists($filePath)) {
                $this->error("❌ Erro ao acessar arquivo!");
                return 1;
            }

            $handle = fopen($filePath, 'r');
            if (!$handle) {
                $this->error("❌ Erro ao abrir arquivo!");
                return 1;
            }

            $header = fgetcsv($handle, 0, ';');
            if (!$header) {
                $this->error("❌ Arquivo CSV vazio ou sem cabeçalho!");
                fclose($handle);
                return 1;
            }

            $header[0] = preg_replace('/^\x{FEFF}/u', '', $header[0]);

            $this->stats['total'] = 0;
            while (fgets($handle) !== false) {
                $this->stats['total']++;
            }
            
            rewind($handle);
            fgetcsv($handle, 0, ';'); 

            $this->info("📊 Total de registros no CSV: {$this->stats['total']}");
            $this->newLine();

            $bar = $this->output->createProgressBar($this->stats['total']);
            $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% - %message%');

            $linha = 2; 
            while (($data = fgetcsv($handle, 0, ';')) !== false) {
                $bar->setMessage("Processando linha {$linha}");
                
                if (count($header) !== count($data)) {
                    $this->stats['erros']++;
                    $this->newLine();
                    $this->error("Linha {$linha}: numero de colunas incompativel (esperado " . count($header) . ", encontrado " . count($data) . ")");
                    $linha++;
                    $bar->advance();
                    continue;
                }

                $record = array_combine($header, $data);

                try {
                    $this->processarLinha($record);
                } catch (\Exception $e) {
                    $this->stats['erros']++;
                    $this->newLine();
                    $this->error("Erro na linha {$linha}: " . $e->getMessage());
                }

                $bar->advance();
                $linha++;
            }

            fclose($handle);
            $bar->finish();
            $this->newLine(2);

            $this->exibirResultados();

            return 0;
        } catch (\Exception $e) {
            $this->error("❌ Erro ao processar arquivo: {$e->getMessage()}");
            return 1;
        }
    }

    private function carregarTiposDocumentos()
    {
        $this->info('📂 Carregando tipos de documentos da base...');
        
        $this->tiposDocumentos = TipoDocumento::where('ativo', true)
            ->get()
            ->keyBy(function($tipo) {
                return strtolower(trim($tipo->nome));
            });

        $this->info("   ✓ {$this->tiposDocumentos->count()} tipos de documentos carregados");
        $this->newLine();
    }

    private function processarLinha(array $record)
    {
        $cnpj = $this->limparCnpj($record['CNPJ'] ?? '');
        
        if (empty($cnpj)) {
            $this->stats['pulados']++;
            return;
        }

        $clienteExistente = Cliente::withTrashed()->where('cnpj', $cnpj)->first();
        
        if ($clienteExistente && !$this->option('force')) {
            $this->stats['pulados']++;
            return;
        }

        if (Fornecedor::where('cnpj', $cnpj)->exists() && !$clienteExistente) {
            $this->stats['pulados']++;
            return;
        }

        if ($this->option('dry-run')) {
            if ($clienteExistente) {
                $this->stats['atualizados']++;
            } else {
                $this->stats['importados']++;
            }
            return;
        }

        DB::beginTransaction();
        try {
            if ($clienteExistente) {
                $cliente = $this->atualizarCliente($clienteExistente, $record);
                $this->stats['atualizados']++;
            } else {
                $cliente = $this->criarCliente($record, $cnpj);
                $this->stats['importados']++;
            }

            $this->criarEndereco($cliente, $record);

            $this->vincularDocumentos($cliente, $record);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function criarCliente(array $record, string $cnpj): Cliente
    {
        return Cliente::create([
            'razao_social' => $record['RAZÃO SOCIAL'] ?? '',
            'nome_fantasia' => null,
            'nome_cliente' => $record['RAZÃO SOCIAL'] ?? '',
            'cnpj' => $cnpj,
            'inscricao_estadual' => null,
            'inscricao_municipal' => null,
            'praca' => $record['MUNICIPIO'] ?? null,
            'telefone' => null,
            'email_contato' => null,
            'site' => null,
            'observacoes' => 'Importado via CSV em ' . now()->format('d/m/Y H:i'),
        ]);
    }

    private function atualizarCliente(Cliente $cliente, array $record): Cliente
    {
        if ($cliente->trashed()) {
            $cliente->restore();
        }

        $cliente->update([
            'razao_social' => $record['RAZÃO SOCIAL'] ?? $cliente->razao_social,
            'nome_cliente' => $record['RAZÃO SOCIAL'] ?? $cliente->nome_cliente,
            'praca' => $record['MUNICIPIO'] ?? $cliente->praca,
            'observacoes' => ($cliente->observacoes ? $cliente->observacoes . "\n" : '') 
                           . 'Atualizado via CSV em ' . now()->format('d/m/Y H:i'),
        ]);

        return $cliente;
    }

    private function criarEndereco(Cliente $cliente, array $record): void
    {
        $cliente->enderecos()->delete();

        $cep = $this->limparCep($record['CEP'] ?? '');
        $estado = $this->extrairSiglaEstado($record['ESTADO'] ?? '');

        if (!empty($record['ENDEREÇO']) || !empty($cep)) {
            $cliente->enderecos()->create([
                'cep' => $cep,
                'logradouro' => $record['ENDEREÇO'] ?? null,
                'numero' => null,
                'complemento' => null,
                'bairro' => $record['BAIRRO'] ?? null,
                'cidade' => $record['MUNICIPIO'] ?? null,
                'estado' => $estado,
            ]);
        }
    }

    private function vincularDocumentos(Cliente $cliente, array $record): void
    {
        $cliente->clienteDocumentosRequeridos()->delete();

        $documentosVinculados = 0;

        foreach ($record as $nomeColuna => $valor) {
            if (in_array($nomeColuna, ['RAZÃO SOCIAL', 'CNPJ', 'ENDEREÇO', 'CEP', 'BAIRRO', 'MUNICIPIO', 'ESTADO'])) {
                continue;
            }

            if (strtoupper(trim($valor)) === 'SIM') {
                $nomeDocumento = strtolower(trim($nomeColuna));
                $tipoDocumento = $this->tiposDocumentos->get($nomeDocumento);
                
                if ($tipoDocumento) {
                    ClienteDocumentoRequerido::create([
                        'cliente_id' => $cliente->id,
                        'tipo_documento_id' => $tipoDocumento->id,
                    ]);
                    $documentosVinculados++;
                }
            }
        }

        if ($documentosVinculados === 0 && $this->tiposDocumentos->count() > 0) {
            $tipoDocPadrao = $this->tiposDocumentos->first();
            ClienteDocumentoRequerido::create([
                'cliente_id' => $cliente->id,
                'tipo_documento_id' => $tipoDocPadrao->id,
            ]);
        }
    }

    private function limparCnpj(string $cnpj): string
    {
        return preg_replace('/[^0-9]/', '', $cnpj);
    }

    private function limparCep(string $cep): string
    {
        return preg_replace('/[^0-9]/', '', $cep);
    }

    private function extrairSiglaEstado(string $estado): ?string
    {
        if (preg_match('/\(([A-Z]{2})\)/', $estado, $matches)) {
            return $matches[1];
        }
        
        if (strlen($estado) === 2) {
            return strtoupper($estado);
        }

        return null;
    }

    private function exibirResultados(): void
    {
        $this->info('✅ Importação concluída!');
        $this->newLine();

        $this->table(
            ['Métrica', 'Quantidade'],
            [
                ['Total de registros', $this->stats['total']],
                ['Importados com sucesso', $this->stats['importados']],
                ['Atualizados', $this->stats['atualizados']],
                ['Pulados (já existentes)', $this->stats['pulados']],
                ['Erros', $this->stats['erros']],
            ]
        );

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->warn('⚠️  Lembre-se: Esta foi uma execução DRY-RUN. Nenhum dado foi salvo.');
        }
    }
}