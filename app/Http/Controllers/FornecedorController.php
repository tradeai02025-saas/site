<?php

/* app/Http/Controllers/FornecedorController.php */

namespace App\Http\Controllers;

use App\Http\Requests\FornecedorRequest;
use App\Mail\{FornecedorMail, ReenvioCredenciaisMail};
use App\Models\{Cliente, Fornecedor, User};
use App\Services\MailRotator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Hash, Log};
use Illuminate\Validation\Rule;

class FornecedorController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user->hasRole('admin');

        if ($isAdmin) {
            $totalFornecedores = Fornecedor::withTrashed()->count();
            $fornecedoresAtivos = Fornecedor::whereNull('deleted_at')->count();
            $fornecedoresInativos = Fornecedor::onlyTrashed()->count();
            $query = Fornecedor::with(['user', 'dadosBancarios'])->withTrashed();
        } else {
            $totalFornecedores = Fornecedor::whereNull('deleted_at')->count();
            $fornecedoresAtivos = $totalFornecedores;
            $fornecedoresInativos = 0;
            $query = Fornecedor::with(['user', 'dadosBancarios'])->whereNull('deleted_at');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $searchClean = preg_replace('/[.\-\/]/', '', $search);
                $q->where('razao_social', 'like', "%{$search}%")
                    ->orWhere('nome_fantasia', 'like', "%{$search}%")
                    ->orWhere('nome_veiculo', 'like', "%{$search}%")
                    ->orWhere('cnpj', 'like', "%{$search}%")
                    ->orWhere('email_contato', 'like', "%{$search}%")
                    ->orWhere(DB::raw("REPLACE(REPLACE(REPLACE(cnpj, '.', ''), '/', ''), '-', '')"), 'like', "%{$searchClean}%");
            });
        }

        if ($request->filled('status') && $isAdmin) {
            if ($request->status === 'inativos') {
                $query->onlyTrashed();
            } elseif ($request->status === 'incompletos') {
                $query->withoutTrashed()
                    ->where(function ($q) {
                        $q->whereNull('telefone')
                            ->orWhere('telefone', '')
                            ->orWhereDoesntHave('dadosBancarios');
                    });
            } else {
                $query->withoutTrashed();
            }
        }

        $fornecedores = $query->orderBy('razao_social')->paginate(15)->withQueryString();

        $fornecedoresIncompletos = Fornecedor::whereNull('deleted_at')
            ->where(function ($q) {
                $q->whereNull('telefone')
                    ->orWhere('telefone', '')
                    ->orWhereDoesntHave('dadosBancarios');
            })
            ->count();

        return view('fornecedores.index', compact('fornecedores', 'totalFornecedores', 'fornecedoresAtivos', 'fornecedoresInativos', 'fornecedoresIncompletos'));
    }

    public function create()
    {
        return view('fornecedores.form');
    }

    public function store(FornecedorRequest $request)
    {
        $validated = $request->validated();

        if (!$this->validarCnpjUnico($validated['cnpj'])) {
            return back()->withInput()->withErrors(['cnpj' => 'Este CNPJ já está cadastrado no sistema.']);
        }

        $senhaGerada = $this->gerarSenhaForte();

        DB::beginTransaction();
        try {
            $userExcluido = User::onlyTrashed()->where('email', $validated['email_contato'])->first();

            if ($userExcluido) {
                $userExcluido->restore();
                $userExcluido->update([
                    'name' => $validated['razao_social'],
                    'password' => Hash::make($senhaGerada),
                ]);
                $user = $userExcluido;

                $user->syncRoles(['fornecedor']);

                if ($user->genOp) {
                    $user->genOp->delete();
                }
                if ($user->representanteUsuario) {
                    $user->representanteUsuario->delete();
                }

                $fornecedorExcluido = Fornecedor::onlyTrashed()->where('user_id', $user->id)->first();
                if ($fornecedorExcluido) {
                    $fornecedorExcluido->restore();
                    $fornecedorExcluido->representantes()->each(fn($r) => $r->forceDelete());
                    $fornecedorExcluido->dadosBancarios()->each(fn($d) => $d->forceDelete());
                    $fornecedorExcluido->enderecos()->each(fn($e) => $e->forceDelete());
                }
            } else {
                $user = User::create([
                    'name' => $validated['razao_social'],
                    'email' => $validated['email_contato'],
                    'password' => Hash::make($senhaGerada),
                ]);

                $user->assignRole('fornecedor');
            }

            $cnpjLimpo = preg_replace('/[^0-9]/', '', $validated['cnpj']);

            $dadosFornecedor = [
                'razao_social' => $validated['razao_social'],
                'nome_fantasia' => $validated['nome_fantasia'] ?? null,
                'nome_veiculo' => $validated['nome_veiculo'] ?? null,
                'cnpj' => $cnpjLimpo,
                'telefone' => $validated['telefone'] ?? null,
                'fax' => $validated['fax'] ?? null,
                'praca' => $validated['praca'] ?? null,
                'email_contato' => $validated['email_contato'] ?? null,
                'site' => $validated['site'] ?? null,
                'observacoes' => $validated['observacoes'] ?? null,
            ];

            if (isset($fornecedorExcluido) && $fornecedorExcluido) {
                $fornecedorExcluido->update($dadosFornecedor);
                $fornecedor = $fornecedorExcluido;
            } else {
                $fornecedor = Fornecedor::create(array_merge(['user_id' => $user->id], $dadosFornecedor));
            }

            if ($request->filled('cep') || $request->filled('logradouro') || $request->filled('cidade')) {
                $fornecedor->enderecos()->create([
                    'cep' => preg_replace('/[^0-9]/', '', $validated['cep'] ?? ''),
                    'logradouro' => $validated['logradouro'] ?? null,
                    'numero' => $validated['numero'] ?? null,
                    'complemento' => $validated['complemento'] ?? null,
                    'bairro' => $validated['bairro'] ?? null,
                    'cidade' => $validated['cidade'] ?? null,
                    'estado' => $validated['estado'] ?? null,
                ]);
            }

            if ($request->has('representantes') && !empty($request->representantes)) {
                foreach ($request->representantes as $repData) {
                    $fornecedor->representantes()->create([
                        'nome_completo' => $repData['nome_completo'],
                        'cpf' => isset($repData['cpf']) ? preg_replace('/[^0-9]/', '', $repData['cpf']) : null,
                        'telefone_1' => $repData['telefone_1'] ?? null,
                        'telefone_2' => $repData['telefone_2'] ?? null,
                        'email_1' => $repData['email_1'] ?? null,
                        'email_2' => $repData['email_2'] ?? null,
                    ]);
                }
            }

            if ($request->has('dados_bancarios') && !empty($request->dados_bancarios)) {
                foreach ($request->dados_bancarios as $dadosData) {
                    $fornecedor->dadosBancarios()->create([
                        'banco' => $dadosData['banco'],
                        'agencia' => $dadosData['agencia'],
                        'digito_agencia' => $dadosData['digito_agencia'] ?? null,
                        'conta' => $dadosData['conta'],
                        'digito_conta' => $dadosData['digito_conta'] ?? null,
                        'tipo_conta' => $dadosData['tipo_conta'],
                        'pix_tipo' => $dadosData['pix_tipo'] ?? null,
                        'pix_chave' => $dadosData['pix_chave'] ?? null,
                        'titular' => $dadosData['titular'],
                        'cpf_cnpj_titular' => preg_replace('/[^0-9]/', '', $dadosData['cpf_cnpj_titular']),
                    ]);
                }
            }

            activity()
                ->causedBy(Auth::user())
                ->performedOn($fornecedor)
                ->withProperties([
                    'senha_temporaria' => true,
                ])
                ->log('Fornecedor criado com senha temporária');

            DB::commit();

            try {
                MailRotator::send(
                    new FornecedorMail($user, $validated['email_contato'], $senhaGerada, $validated['razao_social']),
                    $validated['email_contato']
                );
            } catch (\Exception $mailException) {
                Log::channel('pis')->warning('Erro ao enviar email de boas-vindas para fornecedor', [
                    'fornecedor_id' => $fornecedor->id,
                    'email' => $validated['email_contato'],
                    'error' => $mailException->getMessage(),
                ]);
            }

            return redirect()
                ->route('fornecedores.index')
                ->with('success', 'Fornecedor cadastrado com sucesso! Credenciais de acesso foram enviadas por e-mail.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('pis')->error('Erro ao cadastrar fornecedor', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withInput()->with('error', 'Erro ao cadastrar fornecedor: ' . $e->getMessage());
        }
    }

    public function detalhes(Fornecedor $fornecedor)
    {
        $fornecedor->load([
            'user',
            'enderecos',
            'representantes',
            'dadosBancarios',
            'pis.cliente',
            'pis.status',
            'pis.gerencial.user',
            'pis.operacional.user',        
            'pis.operacionais',             
            'documentosEnviados.tipoDocumento',
            'documentosEnviados.status'
        ]);

        $clientes = $fornecedor->pis()
            ->with('cliente')
            ->get()
            ->pluck('cliente')
            ->unique('id')
            ->values();

        $totalDocumentosEnviados = $fornecedor->documentosEnviados()->count();
        $documentosValidos = $fornecedor->documentosEnviados()->where('valido', true)->count();
        $documentosExpirados = $fornecedor->documentosEnviados()->where('valido', false)->count();

        $cadastroIncompleto = $fornecedor->precisaCompletarCadastro();
        $camposFaltantes = $fornecedor->getCamposFaltantes();
        $progressoCadastro = $fornecedor->getProgressoCadastro();

        return response()->json([
            'success' => true,
            'fornecedor' => $fornecedor,
            'clientes' => $clientes,
            'total_documentos_enviados' => $totalDocumentosEnviados,
            'documentos_validos' => $documentosValidos,
            'documentos_expirados' => $documentosExpirados,
            'cadastro_incompleto' => $cadastroIncompleto,
            'campos_faltantes' => $camposFaltantes,
            'progresso_cadastro' => $progressoCadastro,
        ]);
    }

    public function edit(Fornecedor $fornecedor)
    {
        $fornecedor->load([
            'user',
            'enderecos',
            'representantes',
            'dadosBancarios'
        ]);

        return view('fornecedores.form', compact('fornecedor'));
    }

    public function update(FornecedorRequest $request, Fornecedor $fornecedor)
    {
        $validated = $request->validated();

        $cnpjAtual = $fornecedor->cnpj;
        $cnpjNovo = preg_replace('/[^0-9]/', '', $validated['cnpj']);
        
        if ($cnpjAtual !== $cnpjNovo) {
            if (!$this->validarCnpjUnico($validated['cnpj'], $fornecedor->id)) {
                return back()->withInput()->withErrors(['cnpj' => 'Este CNPJ já está cadastrado no sistema.']);
            }
        }

        DB::beginTransaction();
        try {
            $fornecedor->update([
                'razao_social' => $validated['razao_social'],
                'nome_fantasia' => $validated['nome_fantasia'] ?? null,
                'nome_veiculo' => $validated['nome_veiculo'] ?? null,
                'cnpj' => $cnpjNovo,
                'telefone' => $validated['telefone'] ?? null,
                'fax' => $validated['fax'] ?? null,
                'praca' => $validated['praca'] ?? null,
                'email_contato' => $validated['email_contato'],
                'site' => $validated['site'] ?? null,
                'observacoes' => $validated['observacoes'] ?? null,
            ]);

            $fornecedor->user->update([
                'name' => $validated['razao_social'],
                'email' => $validated['email_contato'],
            ]);

            $endereco = $fornecedor->enderecos()->first();
            if ($endereco) {
                $endereco->update([
                    'cep' => preg_replace('/[^0-9]/', '', $validated['cep'] ?? ''),
                    'logradouro' => $validated['logradouro'] ?? null,
                    'numero' => $validated['numero'] ?? null,
                    'complemento' => $validated['complemento'] ?? null,
                    'bairro' => $validated['bairro'] ?? null,
                    'cidade' => $validated['cidade'] ?? null,
                    'estado' => $validated['estado'] ?? null,
                ]);
            } elseif ($request->filled('cep') || $request->filled('logradouro') || $request->filled('cidade')) {
                $fornecedor->enderecos()->create([
                    'cep' => preg_replace('/[^0-9]/', '', $validated['cep'] ?? ''),
                    'logradouro' => $validated['logradouro'] ?? null,
                    'numero' => $validated['numero'] ?? null,
                    'complemento' => $validated['complemento'] ?? null,
                    'bairro' => $validated['bairro'] ?? null,
                    'cidade' => $validated['cidade'] ?? null,
                    'estado' => $validated['estado'] ?? null,
                ]);
            }

            if ($request->has('representantes')) {
                $representanteIdsExistentes = $fornecedor->representantes()->pluck('id')->toArray();
                $representanteIdsRecebidos = [];

                foreach ($request->representantes as $repData) {
                    if (!empty($repData['id'])) {
                        $representante = $fornecedor->representantes()->find($repData['id']);
                        if ($representante) {
                            $representante->update([
                                'nome_completo' => $repData['nome_completo'],
                                'cpf' => isset($repData['cpf']) ? preg_replace('/[^0-9]/', '', $repData['cpf']) : null,
                                'telefone_1' => $repData['telefone_1'] ?? null,
                                'telefone_2' => $repData['telefone_2'] ?? null,
                                'email_1' => $repData['email_1'] ?? null,
                                'email_2' => $repData['email_2'] ?? null,
                            ]);
                            $representanteIdsRecebidos[] = $representante->id;
                        }
                    } else {
                        $novoRep = $fornecedor->representantes()->create([
                            'nome_completo' => $repData['nome_completo'],
                            'cpf' => isset($repData['cpf']) ? preg_replace('/[^0-9]/', '', $repData['cpf']) : null,
                            'telefone_1' => $repData['telefone_1'] ?? null,
                            'telefone_2' => $repData['telefone_2'] ?? null,
                            'email_1' => $repData['email_1'] ?? null,
                            'email_2' => $repData['email_2'] ?? null,
                        ]);
                        $representanteIdsRecebidos[] = $novoRep->id;
                    }
                }

                $idsParaRemover = array_diff($representanteIdsExistentes, $representanteIdsRecebidos);
                if (!empty($idsParaRemover)) {
                    $fornecedor->representantes()->whereIn('id', $idsParaRemover)->delete();
                }
            }

            if ($request->has('dados_bancarios')) {
                $bancoIdsExistentes = $fornecedor->dadosBancarios()->pluck('id')->toArray();
                $bancoIdsRecebidos = [];

                foreach ($request->dados_bancarios as $dadosData) {
                    $dadosBancariosArray = [
                        'banco' => $dadosData['banco'],
                        'agencia' => $dadosData['agencia'],
                        'digito_agencia' => $dadosData['digito_agencia'] ?? null,
                        'conta' => $dadosData['conta'],
                        'digito_conta' => $dadosData['digito_conta'] ?? null,
                        'tipo_conta' => $dadosData['tipo_conta'],
                        'pix_tipo' => $dadosData['pix_tipo'] ?? null,
                        'pix_chave' => $dadosData['pix_chave'] ?? null,
                        'titular' => $dadosData['titular'],
                        'cpf_cnpj_titular' => preg_replace('/[^0-9]/', '', $dadosData['cpf_cnpj_titular']),
                    ];

                    if (!empty($dadosData['id'])) {
                        $banco = $fornecedor->dadosBancarios()->find($dadosData['id']);
                        if ($banco) {
                            $banco->update($dadosBancariosArray);
                            $bancoIdsRecebidos[] = $banco->id;
                        }
                    } else {
                        $novoBanco = $fornecedor->dadosBancarios()->create($dadosBancariosArray);
                        $bancoIdsRecebidos[] = $novoBanco->id;
                    }
                }

                $idsParaRemover = array_diff($bancoIdsExistentes, $bancoIdsRecebidos);
                if (!empty($idsParaRemover)) {
                    $fornecedor->dadosBancarios()->whereIn('id', $idsParaRemover)->delete();
                }
            }

            DB::commit();
            return redirect()
                ->route('fornecedores.index')
                ->with('success', 'Fornecedor atualizado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('pis')->error('Erro ao atualizar fornecedor', [
                'fornecedor_id' => $fornecedor->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withInput()->with('error', 'Erro ao atualizar fornecedor: ' . $e->getMessage());
        }
    }

    public function destroy(Fornecedor $fornecedor)
    {
        $pisAtivos = $fornecedor->pis()
            ->where('concluido', false)
            ->whereNull('deleted_at')
            ->count();

        if ($pisAtivos > 0) {
            return redirect()
                ->route('fornecedores.index')
                ->with('error', "Não é possível inativar este fornecedor. Existem {$pisAtivos} PI(s) em andamento vinculado(s) a ele. Conclua ou transfira os PIs antes de inativar.");
        }

        DB::beginTransaction();
        try {
            $fornecedor->user->delete();
            $fornecedor->delete();
            DB::commit();
            return redirect()
                ->route('fornecedores.index')
                ->with('success', 'Fornecedor inativado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('pis')->error('Erro ao excluir fornecedor', [
                'fornecedor_id' => $fornecedor->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()
                ->route('fornecedores.index')
                ->with('error', 'Erro ao excluir fornecedor: ' . $e->getMessage());
        }
    }

    public function restore($id)
    {
        DB::beginTransaction();
        try {
            $fornecedor = Fornecedor::withTrashed()->findOrFail($id);

            $fornecedor->restore();

            if ($fornecedor->user && $fornecedor->user->trashed()) {
                $fornecedor->user->restore();
            }

            DB::commit();
            return redirect()
                ->route('fornecedores.index')
                ->with('success', 'Fornecedor restaurado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('pis')->error('Erro ao restaurar fornecedor', [
                'fornecedor_id' => $id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Erro ao restaurar fornecedor: ' . $e->getMessage());
        }
    }

    public function buscarUsuarios(Request $request)
    {
        $search = $request->input('search', '');
        $excludeIds = $request->input('exclude', []);
        $user = Auth::user();
        $userRole = $user->roles->first()->name;

        $niveisPermitidos = $this->obterNiveisPermitidos($userRole);

        $usuarios = User::where('id', '!=', $user->id)
            ->whereNotIn('id', $excludeIds)
            ->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->whereHas('roles', function ($query) use ($niveisPermitidos) {
                $query->whereIn('name', $niveisPermitidos);
            })
            ->with('roles')
            ->limit(10)
            ->get()
            ->map(function ($usuario) {
                return [
                    'id' => $usuario->id,
                    'name' => $usuario->name,
                    'email' => $usuario->email,
                    'iniciais' => mb_strtoupper(mb_substr($usuario->name, 0, 2, 'UTF-8')),
                ];
            });

        return response()->json([
            'success' => true,
            'usuarios' => $usuarios
        ]);
    }

    private function obterNiveisPermitidos($roleAtual)
    {
        $permissoes = [
            'fornecedor' => ['operacional'],
            'operacional' => ['fornecedor', 'operacional', 'gerencial'],
            'gerencial' => ['fornecedor', 'operacional', 'gerencial', 'admin'],
            'admin' => ['fornecedor', 'operacional', 'gerencial', 'admin'],
        ];

        return $permissoes[$roleAtual] ?? [];
    }

    public function reenviarCredenciais(Fornecedor $fornecedor)
    {
        $user = Auth::user();

        if (!$user->hasAnyRole(['admin', 'gerencial'])) {
            return response()->json(['success' => false, 'message' => 'Acesso negado.'], 403);
        }

        if (!$fornecedor->user) {
            return response()->json(['success' => false, 'message' => 'Fornecedor não possui usuário vinculado.'], 404);
        }

        try {
            $novaSenha = $this->gerarSenhaForte();
            $fornecedor->user->update([
                'password' => Hash::make($novaSenha),
            ]);

            activity()
                ->causedBy($fornecedor->user)
                ->withProperties(['tipo' => 'senha_temporaria'])
                ->log('Senha temporária enviada - requer troca obrigatória');

            MailRotator::send(
                new ReenvioCredenciaisMail(
                    $fornecedor->user,
                    $fornecedor->user->email,
                    $novaSenha,
                    $fornecedor->razao_social,
                    'fornecedor'
                ),
                $fornecedor->user->email
            );

            activity()
                ->causedBy($user)
                ->performedOn($fornecedor)
                ->log('Credenciais reenviadas para fornecedor');

            return response()->json([
                'success' => true,
                'message' => 'Nova senha gerada e enviada para ' . $fornecedor->user->email . '!',
            ]);
        } catch (\Exception $e) {
            Log::channel('pis')->error('Erro ao reenviar credenciais do fornecedor', [
                'fornecedor_id' => $fornecedor->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'message' => 'Erro ao reenviar credenciais: ' . $e->getMessage()], 500);
        }
    }

    private function gerarSenhaForte()
    {
        $maiusculas = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $minusculas = 'abcdefghijkmnopqrstuvwxyz';
        $numeros = '0123456789';
        $especiais = '!@#$%&*-_=+[]{}|;:,.<>?';

        $todosCaracteres = $maiusculas . $minusculas . $numeros . $especiais;
        $tamanhoPool = strlen($todosCaracteres);
        $senha = '';

        $randomBytes = random_bytes(16);

        for ($i = 0; $i < 16; $i++) {
            $indice = ord($randomBytes[$i]) % $tamanhoPool;
            $senha .= $todosCaracteres[$indice];
        }

        if (
            !preg_match('/[A-Z]/', $senha) ||
            !preg_match('/[a-z]/', $senha) ||
            !preg_match('/[0-9]/', $senha) ||
            !preg_match('/[^A-Za-z0-9]/', $senha)
        ) {
            return $this->gerarSenhaForte();
        }

        return $senha;
    }

    public function destroyRepresentante(Fornecedor $fornecedor, $id)
    {
        try {
            $representante = $fornecedor->representantes()->findOrFail($id);
            
            $nomeRepresentante = $representante->nome_completo;
            $representante->delete();
            
            activity()
                ->causedBy(Auth::user())
                ->performedOn($representante)
                ->log('Representante removido: ' . $nomeRepresentante);
            
            return response()->json([
                'success' => true,
                'message' => 'Representante removido com sucesso!'
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::channel('pis')->warning('Representante não encontrado ao tentar remover', [
                'fornecedor_id' => $fornecedor->id,
                'representante_id' => $id,
                'user_id' => Auth::id(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Representante não encontrado'
            ], 404);
            
        } catch (\Exception $e) {
            Log::channel('pis')->error('Erro ao remover representante', [
                'fornecedor_id' => $fornecedor->id,
                'representante_id' => $id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover representante: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroyDadosBancarios(Fornecedor $fornecedor, $id)
    {
        try {
            $dadosBancarios = $fornecedor->dadosBancarios()->findOrFail($id);
            
            $bancoNome = $dadosBancarios->banco;
            $dadosBancarios->delete();
            
            activity()
                ->causedBy(Auth::user())
                ->performedOn($dadosBancarios)
                ->log('Dados bancários removidos: ' . $bancoNome);
                    
            return response()->json([
                'success' => true,
                'message' => 'Dados bancários removidos com sucesso!'
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::channel('pis')->warning('Dados bancários não encontrados ao tentar remover', [
                'fornecedor_id' => $fornecedor->id,
                'dados_bancarios_id' => $id,
                'user_id' => Auth::id(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Dados bancários não encontrados'
            ], 404);
            
        } catch (\Exception $e) {
            Log::channel('pis')->error('Erro ao remover dados bancários', [
                'fornecedor_id' => $fornecedor->id,
                'dados_bancarios_id' => $id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover dados bancários: ' . $e->getMessage()
            ], 500);
        }
    }

    private function validarCnpjUnico($cnpj, $fornecedorIdIgnorar = null)
    {
        $cnpjLimpo = preg_replace('/[^0-9]/', '', $cnpj);
        
        $queryFornecedor = Fornecedor::where('cnpj', $cnpjLimpo);
        if ($fornecedorIdIgnorar) {
            $queryFornecedor->where('id', '!=', $fornecedorIdIgnorar);
        }
        
        $queryCliente = Cliente::where('cnpj', $cnpjLimpo);

        $queryRepresentante = \App\Models\RepresentanteUsuario::where('cnpj', $cnpjLimpo);
        
        return !$queryFornecedor->exists() && !$queryCliente->exists() && !$queryRepresentante->exists();
    }
}