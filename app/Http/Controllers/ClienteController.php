<?php

/* app/Http/Controllers/ClienteController.php */

namespace App\Http\Controllers;

use App\Http\Requests\ClienteRequest;
use App\Models\{Cliente, ClienteDocumentoRequerido, Fornecedor, GenOp, Pi, PiAtribuicao};
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Log};

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user->hasRole('admin');

        if ($isAdmin) {
            $totalClientes = Cliente::withTrashed()->count();
            $clientesAtivos = Cliente::whereNull('deleted_at')->count();
            $clientesInativos = Cliente::onlyTrashed()->count();
            $query = Cliente::withTrashed()->with([
                'enderecos',
                'operacionais',
                'operacionalPrincipal',
            ]);
        } else {
            $totalClientes = Cliente::whereNull('deleted_at')->count();
            $clientesAtivos = $totalClientes;
            $clientesInativos = 0;
            $query = Cliente::whereNull('deleted_at')->with([
                'enderecos',
                'operacionais',
                'operacionalPrincipal',
            ]);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $searchClean = preg_replace('/[.\-\/]/', '', $search);
                $q->where('razao_social', 'like', "%{$search}%")
                    ->orWhere('nome_fantasia', 'like', "%{$search}%")
                    ->orWhere('nome_cliente', 'like', "%{$search}%")
                    ->orWhere('cnpj', 'like', "%{$search}%")
                    ->orWhere('inscricao_estadual', 'like', "%{$search}%")
                    ->orWhere('inscricao_municipal', 'like', "%{$search}%")
                    ->orWhere('praca', 'like', "%{$search}%")
                    ->orWhere('telefone', 'like', "%{$search}%")
                    ->orWhere('email_contato', 'like', "%{$search}%")
                    ->orWhere(DB::raw("REPLACE(REPLACE(REPLACE(cnpj, '.', ''), '/', ''), '-', '')"), 'like', "%{$searchClean}%");

            });
        }

        if ($request->filled('praca')) {
            $query->where('praca', 'like', "%{$request->praca}%");
        }

        if ($request->filled('status') && $isAdmin) {
            if ($request->status === 'inativos') {
                $query->onlyTrashed();
            } else {
                $query->withoutTrashed();
            }
        }

        $clientes = $query->orderBy('razao_social')->paginate(15)->withQueryString();

        $tiposDocumentos = CacheService::tiposDocumentoAtivos()->where('avulso', false)->values();

        $operacionais = GenOp::with('user')
            ->whereHas('user', function ($q) {
                $q->whereNull('deleted_at')
                    ->whereHas('roles', function ($rq) {
                        $rq->where('name', 'operacional');
                    });
            })
            ->orderBy('nome_completo')
            ->get();

        return view('clientes.index', compact(
            'clientes',
            'totalClientes',
            'clientesAtivos',
            'clientesInativos',
            'tiposDocumentos',
            'operacionais'
        ));
    }

    public function create()
    {
        $operacionais = GenOp::with('user')
            ->whereHas('user', function ($q) {
                $q->whereNull('deleted_at')
                    ->whereHas('roles', function ($rq) {
                        $rq->where('name', 'operacional');
                    });
            })
            ->orderBy('nome_completo')
            ->get();

        $tiposDocumentos = CacheService::tiposDocumentoComVeiculos()->where('avulso', false)->values();

        $tipoVeiculos = CacheService::tiposVeiculoAtivos();

        return view('clientes.form', compact('operacionais', 'tiposDocumentos', 'tipoVeiculos'));
    }

    public function store(ClienteRequest $request)
    {
        $validated = $request->validated();

        if (! $this->validarCnpjUnico($validated['cnpj'])) {
            return back()->withInput()->withErrors(['cnpj' => 'Este CNPJ já está cadastrado no sistema.']);
        }

        DB::beginTransaction();
        try {
            $cnpjLimpo = preg_replace('/[^0-9]/', '', $validated['cnpj']);

            $cliente = Cliente::create([
                'razao_social' => $validated['razao_social'],
                'nome_fantasia' => $validated['nome_fantasia'],
                'nome_cliente' => $validated['nome_cliente'],
                'cnpj' => $cnpjLimpo,
                'inscricao_estadual' => $validated['inscricao_estadual'],
                'inscricao_municipal' => $validated['inscricao_municipal'],
                'praca' => $validated['praca'],
                'telefone' => $validated['telefone'],
                'email_contato' => $validated['email_contato'],
                'site' => $validated['site'],
                'observacoes' => $validated['observacoes'],
                'exige_documento_por_veiculo' => $request->boolean('exige_documento_por_veiculo', false),
            ]);

            if ($request->filled('cep') || $request->filled('logradouro') || $request->filled('cidade')) {
                $cliente->enderecos()->create([
                    'cep' => preg_replace('/[^0-9]/', '', $request->cep ?? ''),
                    'logradouro' => $request->logradouro,
                    'numero' => $request->numero,
                    'complemento' => $request->complemento,
                    'bairro' => $request->bairro,
                    'cidade' => $request->cidade,
                    'estado' => $request->estado,
                ]);
            }

            if ($request->filled('tipos_documentos')) {
                $opcionais = $request->input('opcionais', []);
                foreach ($request->tipos_documentos as $tipoDocId) {
                    ClienteDocumentoRequerido::create([
                        'cliente_id' => $cliente->id,
                        'tipo_documento_id' => $tipoDocId,
                        'opcional' => in_array($tipoDocId, $opcionais),
                    ]);
                }
            }

            PiAtribuicao::create([
                'cliente_id' => $cliente->id,
                'pi_id' => null,
                'operacional_id' => $validated['operacional_principal'],
                'data_atribuicao' => now(),
                'ativo' => true,
                'principal' => true,
            ]);

            DB::commit();

            return redirect()->route('clientes.index')->with('success', 'Cliente criado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('pis')->error('Erro ao criar cliente', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withInput()->with('error', 'Erro ao criar cliente: '.$e->getMessage());
        }
    }

    public function detalhes(Cliente $cliente)
    {
        try {
            $cliente->load([
                'enderecos',
                'clienteDocumentosRequeridos.tipoDocumento' => function ($q) {
                    $q->select('id', 'nome', 'descricao', 'validade_dias', 'validar');
                },
                'clienteDocumentosRequeridos.tipoDocumento.tipoVeiculos',
                'operacionais' => function ($q) {
                    $q->whereNull('gen_op.deleted_at')
                        ->whereHas('user', function ($query) {
                            $query->whereNull('deleted_at');
                        });
                },
                'operacionais.user' => function ($q) {
                    $q->select('id', 'email')->whereNull('deleted_at');
                },
            ]);

            $totalPis = $cliente->pis()->count();
            $pisConcluidos = $cliente->pis()->where('concluido', true)->count();
            $pisPendentes = $totalPis - $pisConcluidos;

            $fornecedoresVinculados = $cliente->pis()
                ->with('fornecedor:id,razao_social')
                ->get()
                ->pluck('fornecedor')
                ->filter()
                ->unique('id')
                ->values();

            $tipoVeiculos = CacheService::tiposVeiculoAtivos();

            return response()->json([
                'success' => true,
                'cliente' => $cliente,
                'fornecedores' => $fornecedoresVinculados,
                'totalPis' => $totalPis,
                'pisConcluidos' => $pisConcluidos,
                'pisPendentes' => $pisPendentes,
                'tipoVeiculos' => $tipoVeiculos,
            ]);
        } catch (\Exception $e) {
            Log::channel('pis')->error('Erro ao carregar detalhes do cliente: '.$e->getMessage(), [
                'cliente_id' => $cliente->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar os dados do cliente.',
            ], 500);
        }
    }

    public function edit(Cliente $cliente)
    {
        $cliente->load([
            'enderecos',
            'clienteDocumentosRequeridos.tipoDocumento',
            'atribuicoesOperacionais' => function ($query) {
                $query->whereNull('deleted_at');
            },
            'atribuicoesOperacionais.operacional.user',
        ]);

        $operacionaisVinculados = $cliente->atribuicoesOperacionais->map(function ($atribuicao) {
            $operacional = $atribuicao->operacional;
            if ($operacional && $operacional->user) {
                $operacional->pivot = $atribuicao;

                return $operacional;
            }

            return null;
        })->filter();

        $operacionais = GenOp::with('user')
            ->whereHas('user', function ($q) {
                $q->whereNull('deleted_at');
            })
            ->orderBy('nome_completo')
            ->get();

        $tiposDocumentos = CacheService::tiposDocumentoComVeiculos()->where('avulso', false)->values();

        $tipoVeiculos = CacheService::tiposVeiculoAtivos();

        return view('clientes.form', compact('cliente', 'operacionais', 'tiposDocumentos', 'operacionaisVinculados', 'tipoVeiculos'));
    }

    public function update(ClienteRequest $request, Cliente $cliente)
    {
        $validated = $request->validated();

        $cnpjAtual = $cliente->cnpj;
        $cnpjNovo = preg_replace('/[^0-9]/', '', $validated['cnpj']);

        if ($cnpjAtual !== $cnpjNovo) {
            if (! $this->validarCnpjUnico($validated['cnpj'], $cliente->id)) {
                return back()->withInput()->withErrors(['cnpj' => 'Este CNPJ já está cadastrado no sistema.']);
            }
        }

        DB::beginTransaction();
        try {
            $cliente->update([
                'razao_social' => $validated['razao_social'],
                'nome_fantasia' => $validated['nome_fantasia'],
                'nome_cliente' => $validated['nome_cliente'],
                'cnpj' => $cnpjNovo,
                'inscricao_estadual' => $validated['inscricao_estadual'],
                'inscricao_municipal' => $validated['inscricao_municipal'],
                'praca' => $validated['praca'],
                'telefone' => $validated['telefone'],
                'email_contato' => $validated['email_contato'],
                'site' => $validated['site'],
                'observacoes' => $validated['observacoes'],
                'exige_documento_por_veiculo' => $request->boolean('exige_documento_por_veiculo', false),
            ]);

            $cliente->enderecos()->delete();

            if ($request->filled('cep') || $request->filled('logradouro') || $request->filled('cidade')) {
                $cliente->enderecos()->create([
                    'cep' => preg_replace('/[^0-9]/', '', $request->cep ?? ''),
                    'logradouro' => $request->logradouro,
                    'numero' => $request->numero,
                    'complemento' => $request->complemento,
                    'bairro' => $request->bairro,
                    'cidade' => $request->cidade,
                    'estado' => $request->estado,
                ]);
            }

            $cliente->clienteDocumentosRequeridos()->delete();

            if ($request->filled('tipos_documentos')) {
                $opcionais = $request->input('opcionais', []);
                foreach ($request->tipos_documentos as $tipoDocId) {
                    ClienteDocumentoRequerido::create([
                        'cliente_id' => $cliente->id,
                        'tipo_documento_id' => $tipoDocId,
                        'opcional' => in_array($tipoDocId, $opcionais),
                    ]);
                }
            }

            $cliente->atribuicoesOperacionais()->delete();

            PiAtribuicao::create([
                'cliente_id' => $cliente->id,
                'pi_id' => null,
                'operacional_id' => $validated['operacional_principal'],
                'data_atribuicao' => now(),
                'ativo' => true,
                'principal' => true,
            ]);

            DB::commit();

            return redirect()->route('clientes.index')->with('success', 'Cliente atualizado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('pis')->error('Erro ao atualizar cliente', [
                'cliente_id' => $cliente->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withInput()->with('error', 'Erro ao atualizar cliente: '.$e->getMessage());
        }
    }

    private function sincronizarOperacionaisComPis(Cliente $cliente, array $operacionaisAdicionados, array $operacionaisRemovidos, $operacionalPrincipalId)
    {
        $pisDoCliente = Pi::where('cliente_id', $cliente->id)->whereNull('deleted_at')->get();

        foreach ($pisDoCliente as $pi) {

            foreach ($operacionaisAdicionados as $operacionalId) {
                $atribuicaoExistente = PiAtribuicao::withTrashed()
                    ->where('pi_id', $pi->id)
                    ->where('operacional_id', $operacionalId)
                    ->first();

                if (! $atribuicaoExistente) {
                    PiAtribuicao::create([
                        'pi_id' => $pi->id,
                        'cliente_id' => $cliente->id,
                        'operacional_id' => $operacionalId,
                        'data_atribuicao' => now(),
                        'ativo' => true,
                        'principal' => ((int) $operacionalPrincipalId === (int) $operacionalId),
                    ]);
                } elseif ($atribuicaoExistente->trashed()) {
                    $atribuicaoExistente->restore();
                    $atribuicaoExistente->update([
                        'ativo' => true,
                        'principal' => ((int) $operacionalPrincipalId === (int) $operacionalId),
                    ]);
                } else {
                    $atribuicaoExistente->update([
                        'ativo' => true,
                        'principal' => ((int) $operacionalPrincipalId === (int) $operacionalId),
                    ]);
                }
            }

            if ($operacionalPrincipalId) {
                PiAtribuicao::where('pi_id', $pi->id)
                    ->where('cliente_id', $cliente->id)
                    ->where('operacional_id', '!=', $operacionalPrincipalId)
                    ->whereNull('deleted_at')
                    ->update(['principal' => false]);

                PiAtribuicao::where('pi_id', $pi->id)
                    ->where('cliente_id', $cliente->id)
                    ->where('operacional_id', $operacionalPrincipalId)
                    ->whereNull('deleted_at')
                    ->update(['principal' => true]);
            }

            foreach ($operacionaisRemovidos as $operacionalId) {
                PiAtribuicao::where('pi_id', $pi->id)
                    ->where('operacional_id', $operacionalId)
                    ->where('cliente_id', $cliente->id)
                    ->delete();
            }

            $principalAtribuicao = PiAtribuicao::where('pi_id', $pi->id)
                ->where('ativo', true)
                ->where('principal', true)
                ->whereNull('deleted_at')
                ->first();

            if (! $principalAtribuicao) {
                $primeiroAtivo = PiAtribuicao::where('pi_id', $pi->id)
                    ->where('ativo', true)
                    ->whereNull('deleted_at')
                    ->first();

                if ($primeiroAtivo) {
                    $primeiroAtivo->update(['principal' => true]);
                    $principalAtribuicao = $primeiroAtivo;
                }
            }

            if ($principalAtribuicao) {
                $pi->update(['operacional_id' => $principalAtribuicao->operacional_id]);
            } else {
                $pi->update(['operacional_id' => null]);
                Log::channel('pis')->warning('PI ficou sem operacional após sincronização', [
                    'pi_id' => $pi->id,
                    'cliente_id' => $cliente->id,
                ]);
            }
        }
    }

    public function destroy(Cliente $cliente)
    {
        try {
            $pisCount = $cliente->pis()->count();

            if ($pisCount > 0) {
                return back()->with('warning', "Este cliente possui {$pisCount} PI(s) vinculado(s) e não pode ser excluído.");
            }

            $operacionaisCount = $cliente->atribuicoesOperacionais()->count();

            if ($operacionaisCount > 0) {
                $cliente->atribuicoesOperacionais()->delete();
            }

            $cliente->delete();

            return redirect()->route('clientes.index')->with('success', 'Cliente inativado com sucesso!');
        } catch (\Exception $e) {
            Log::channel('pis')->error('Erro ao inativar cliente', [
                'cliente_id' => $cliente->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Erro ao inativar cliente: '.$e->getMessage());
        }
    }

    public function restore($id)
    {
        $user = Auth::user();

        if (! $user->hasRole('admin')) {
            abort(403, 'Apenas administradores podem restaurar clientes.');
        }

        try {
            $cliente = Cliente::withTrashed()->findOrFail($id);
            $cliente->restore();

            $cliente->atribuicoesOperacionais()->withTrashed()->restore();

            return redirect()->route('clientes.index')->with('success', 'Cliente restaurado com sucesso!');
        } catch (\Exception $e) {
            Log::channel('pis')->error('Erro ao restaurar cliente', [
                'cliente_id' => $id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Erro ao restaurar cliente: '.$e->getMessage());
        }
    }

    public function buscarPorCnpj(Request $request)
    {
        $request->validate([
            'cnpj' => 'required|string|size:18',
        ]);

        try {
            $cnpjLimpo = preg_replace('/[^0-9]/', '', $request->cnpj);

            $cliente = Cliente::where('cnpj', $cnpjLimpo)
                ->with(['enderecos', 'operacionais'])
                ->first();

            if (! $cliente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cliente não encontrado.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'cliente' => [
                    'id' => $cliente->id,
                    'razao_social' => $cliente->razao_social,
                    'nome_fantasia' => $cliente->nome_fantasia,
                    'nome_cliente' => $cliente->nome_cliente,
                    'cnpj' => $cliente->cnpj,
                    'inscricao_estadual' => $cliente->inscricao_estadual,
                    'inscricao_municipal' => $cliente->inscricao_municipal,
                    'praca' => $cliente->praca,
                    'telefone' => $cliente->telefone,
                    'email_contato' => $cliente->email_contato,
                    'site' => $cliente->site,
                    'endereco' => $cliente->enderecos->first(),
                    'operacionais' => $cliente->operacionais,
                    'exige_documento_por_veiculo' => $cliente->exige_documento_por_veiculo,
                ],
            ]);
        } catch (\Exception $e) {
            Log::channel('pis')->error('Erro ao buscar cliente por CNPJ', [
                'cnpj' => $request->cnpj ?? null,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar cliente: '.$e->getMessage(),
            ], 500);
        }
    }

    public function listarAtivos()
    {
        try {
            $clientes = Cliente::whereNull('deleted_at')
                ->orderBy('razao_social')
                ->get()
                ->map(function ($cliente) {
                    return [
                        'id' => $cliente->id,
                        'razao_social' => $cliente->razao_social,
                        'nome_fantasia' => $cliente->nome_fantasia,
                        'cnpj' => $cliente->cnpj,
                        'praca' => $cliente->praca,
                        'exige_documento_por_veiculo' => $cliente->exige_documento_por_veiculo,
                    ];
                });

            return response()->json([
                'success' => true,
                'clientes' => $clientes,
            ]);
        } catch (\Exception $e) {
            Log::channel('pis')->error('Erro ao listar clientes ativos', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar clientes: '.$e->getMessage(),
            ], 500);
        }
    }

    public function adicionarEndereco(Request $request, Cliente $cliente)
    {
        $validated = $request->validate([
            'cep' => 'required|string|max:10',
            'logradouro' => 'required|string|max:255',
            'numero' => 'nullable|string|max:20',
            'complemento' => 'nullable|string|max:100',
            'bairro' => 'required|string|max:100',
            'cidade' => 'required|string|max:100',
            'estado' => 'required|string|size:2',
        ]);

        try {
            $endereco = $cliente->enderecos()->create([
                'cep' => preg_replace('/[^0-9]/', '', $validated['cep']),
                'logradouro' => $validated['logradouro'],
                'numero' => $validated['numero'] ?? null,
                'complemento' => $validated['complemento'] ?? null,
                'bairro' => $validated['bairro'],
                'cidade' => $validated['cidade'],
                'estado' => $validated['estado'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'endereço adicionado com sucesso!',
                'endereco' => $endereco,
            ]);
        } catch (\Exception $e) {
            Log::channel('pis')->error('Erro ao adicionar endereço ao cliente', [
                'cliente_id' => $cliente->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao adicionar endereço: '.$e->getMessage(),
            ], 500);
        }
    }

    public function removerEndereco(Cliente $cliente, $enderecoId)
    {
        try {
            $endereco = $cliente->enderecos()->findOrFail($enderecoId);
            $endereco->delete();

            return response()->json([
                'success' => true,
                'message' => 'endereço removido com sucesso!',
            ]);
        } catch (\Exception $e) {
            Log::channel('pis')->error('Erro ao remover endereço do cliente', [
                'cliente_id' => $cliente->id,
                'endereco_id' => $enderecoId,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover endereço: '.$e->getMessage(),
            ], 500);
        }
    }

    public function vincularOperacional(Request $request, Cliente $cliente)
    {
        $validated = $request->validate([
            'operacional_id' => 'required|exists:gen_op,id',
        ]);

        try {
            $operacional = GenOp::with('user')
                ->whereNull('deleted_at')
                ->whereHas('user', function ($q) {
                    $q->whereNull('deleted_at');
                })
                ->findOrFail($validated['operacional_id']);

            $jaVinculado = PiAtribuicao::where('cliente_id', $cliente->id)
                ->whereNull('pi_id')
                ->where('operacional_id', $validated['operacional_id'])
                ->where('ativo', true)
                ->whereNull('deleted_at')
                ->exists();

            if ($jaVinculado) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este operacional já está vinculado a este cliente.',
                ], 422);
            }

            if ($request->boolean('principal')) {
                PiAtribuicao::where('cliente_id', $cliente->id)
                    ->whereNull('pi_id')
                    ->update(['principal' => false]);
            }

            $atribuicao = PiAtribuicao::create([
                'cliente_id' => $cliente->id,
                'pi_id' => null,
                'operacional_id' => $validated['operacional_id'],
                'data_atribuicao' => now(),
                'ativo' => true,
                'principal' => $request->boolean('principal', false),
            ]);

            $pisDoCliente = Pi::where('cliente_id', $cliente->id)
                ->whereNull('deleted_at')
                ->where('concluido', false)
                ->get();

            foreach ($pisDoCliente as $pi) {
                $jaVinculadoPi = PiAtribuicao::where('pi_id', $pi->id)
                    ->where('operacional_id', $validated['operacional_id'])
                    ->where('ativo', true)
                    ->whereNull('deleted_at')
                    ->exists();

                if (! $jaVinculadoPi) {
                    PiAtribuicao::create([
                        'pi_id' => $pi->id,
                        'cliente_id' => $cliente->id,
                        'operacional_id' => $validated['operacional_id'],
                        'data_atribuicao' => now(),
                        'ativo' => true,
                        'principal' => $request->boolean('principal', false),
                    ]);
                }
            }

            $atribuicao->load('operacional.user');

            return response()->json([
                'success' => true,
                'message' => 'Operacional vinculado com sucesso!',
                'atribuicao' => $atribuicao,
            ]);
        } catch (\Exception $e) {
            Log::channel('pis')->error('Erro ao vincular operacional ao cliente', [
                'cliente_id' => $cliente->id,
                'operacional_id' => $validated['operacional_id'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao vincular operacional: '.$e->getMessage(),
            ], 500);
        }
    }

    private function validarCnpjUnico($cnpj, $clienteIdIgnorar = null)
    {
        $cnpjLimpo = preg_replace('/[^0-9]/', '', $cnpj);

        $queryCliente = Cliente::where('cnpj', $cnpjLimpo);
        if ($clienteIdIgnorar) {
            $queryCliente->where('id', '!=', $clienteIdIgnorar);
        }

        $queryFornecedor = Fornecedor::where('cnpj', $cnpjLimpo);

        $queryRepresentante = \App\Models\RepresentanteUsuario::where('cnpj', $cnpjLimpo);

        return ! $queryCliente->exists() && ! $queryFornecedor->exists() && ! $queryRepresentante->exists();
    }

    public function desvincularDocumento(Cliente $cliente, $documentoRequeridoId)
    {
        try {
            $totalDocumentos = $cliente->clienteDocumentosRequeridos()->count();

            if ($totalDocumentos <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'não é possível remover o último documento requerido. O cliente deve ter pelo menos um documento obrigatório.',
                ], 422);
            }

            $documentoRequerido = ClienteDocumentoRequerido::where('cliente_id', $cliente->id)
                ->where('id', $documentoRequeridoId)
                ->firstOrFail();

            $documentoRequerido->delete();

            return response()->json([
                'success' => true,
                'message' => 'Documento desvinculado com sucesso!',
            ]);
        } catch (\Exception $e) {
            Log::channel('docs')->error('Erro ao desvincular documento do cliente', [
                'cliente_id' => $cliente->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao desvincular documento: '.$e->getMessage(),
            ], 500);
        }
    }

    public function desvincularOperacional(Request $request, Cliente $cliente, $atribuicaoId)
    {
        $totalOperacionais = PiAtribuicao::where('cliente_id', $cliente->id)
            ->whereNull('pi_id')
            ->where('ativo', true)
            ->whereNull('deleted_at')
            ->count();

        if ($totalOperacionais <= 1) {
            return response()->json([
                'success' => false,
                'message' => 'O cliente deve ter pelo menos um operacional responsável.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $atribuicao = PiAtribuicao::where('cliente_id', $cliente->id)
                ->whereNull('pi_id')
                ->where('id', $atribuicaoId)
                ->firstOrFail();

            $operacionalId = $atribuicao->operacional_id;
            $eraPrincipal = $atribuicao->principal;

            $atribuicao->delete();

            $pisDoCliente = Pi::where('cliente_id', $cliente->id)
                ->whereNull('deleted_at')
                ->get();

            foreach ($pisDoCliente as $pi) {
                PiAtribuicao::where('pi_id', $pi->id)
                    ->where('cliente_id', $cliente->id)
                    ->where('operacional_id', $operacionalId)
                    ->delete();
            }

            if ($eraPrincipal) {
                $novoP = PiAtribuicao::where('cliente_id', $cliente->id)
                    ->whereNull('pi_id')
                    ->where('ativo', true)
                    ->whereNull('deleted_at')
                    ->first();

                if ($novoP) {
                    $novoP->update(['principal' => true]);

                    foreach ($pisDoCliente as $pi) {
                        $atribPi = PiAtribuicao::where('pi_id', $pi->id)
                            ->where('cliente_id', $cliente->id)
                            ->where('operacional_id', $novoP->operacional_id)
                            ->whereNull('deleted_at')
                            ->first();

                        if ($atribPi) {
                            $atribPi->update(['principal' => true]);
                        } else {
                            PiAtribuicao::create([
                                'pi_id' => $pi->id,
                                'cliente_id' => $cliente->id,
                                'operacional_id' => $novoP->operacional_id,
                                'data_atribuicao' => now(),
                                'ativo' => true,
                                'principal' => true,
                            ]);
                        }

                        $pi->update(['operacional_id' => $novoP->operacional_id]);
                    }
                }
            } else {
                foreach ($pisDoCliente as $pi) {
                    $temOperacional = PiAtribuicao::where('pi_id', $pi->id)
                        ->where('ativo', true)
                        ->whereNull('deleted_at')
                        ->exists();

                    if (! $temOperacional) {
                        $principalCliente = PiAtribuicao::where('cliente_id', $cliente->id)
                            ->whereNull('pi_id')
                            ->where('ativo', true)
                            ->where('principal', true)
                            ->whereNull('deleted_at')
                            ->first();

                        if ($principalCliente) {
                            PiAtribuicao::create([
                                'pi_id' => $pi->id,
                                'cliente_id' => $cliente->id,
                                'operacional_id' => $principalCliente->operacional_id,
                                'data_atribuicao' => now(),
                                'ativo' => true,
                                'principal' => true,
                            ]);

                            $pi->update(['operacional_id' => $principalCliente->operacional_id]);
                        }
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Operacional desvinculado com sucesso!',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::channel('pis')->error('Erro ao desvincular operacional do cliente', [
                'cliente_id' => $cliente->id,
                'atribuicao_id' => $atribuicaoId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao desvincular operacional: '.$e->getMessage(),
            ], 500);
        }
    }

    public function getOperacionais(Cliente $cliente)
    {
        try {
            $operacionais = $cliente->operacionais()
                ->whereNull('gen_op.deleted_at')
                ->whereHas('user', function ($q) {
                    $q->whereNull('deleted_at');
                })
                ->with('user')
                ->get();

            return response()->json([
                'success' => true,
                'operacionais' => $operacionais,
            ]);
        } catch (\Exception $e) {
            Log::channel('pis')->error('Erro ao buscar operacionais do cliente', [
                'cliente_id' => $cliente->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar operacionais: '.$e->getMessage(),
            ], 500);
        }
    }
}