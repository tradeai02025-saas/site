<?php

/* app/Http/Controllers/RepresentanteController.php */

namespace App\Http\Controllers;

use App\Mail\{ReenvioCredenciaisMail, RepresentanteMail};
use App\Models\{Cliente, DocumentoEnviado, Fornecedor, Pi, PiDocumento, RepresentanteFornecedor, RepresentanteUsuario, User};
use App\Services\MailRotator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Hash, Log};
use Illuminate\Validation\Rule;

class RepresentanteController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        if (! $user->hasAnyRole(['admin', 'gerencial'])) {
            abort(403, 'Acesso negado.');
        }

        $query = RepresentanteUsuario::with(['user'])
            ->withCount([
                'vinculos as vinculos_ativos_count' => function ($q) {
                    $q->where('status_vinculo', 'aprovado')
                        ->where('ativo', true)
                        ->whereNull('data_desvinculo');
                },
                'vinculos as vinculos_pendentes_count' => function ($q) {
                    $q->where('status_vinculo', 'pendente')
                        ->where('ativo', true);
                },
            ]);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nome_empresa', 'like', "%{$search}%")
                    ->orWhere('cnpj', 'like', "%{$search}%")
                    ->orWhere('telefone', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status_filtro')) {
            switch ($request->status_filtro) {
                case 'com_pendentes':
                    $query->whereHas('vinculos', function ($q) {
                        $q->where('status_vinculo', 'pendente')->where('ativo', true);
                    });
                    break;
                case 'ativos':
                    $query->where('ativo', true);
                    break;
                case 'inativos':
                    $query->where('ativo', false);
                    break;
                case 'sem_vinculos':
                    $query->where('ativo', true)
                        ->whereDoesntHave('vinculos', function ($q) {
                            $q->where('status_vinculo', 'aprovado')
                                ->where('ativo', true)
                                ->whereNull('data_desvinculo');
                        });
                    break;
            }
        }

        $representantes = $query->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        $totalRepresentantes = RepresentanteUsuario::count();
        $totalAtivos = RepresentanteUsuario::where('ativo', true)->count();
        $repComPendentes = RepresentanteUsuario::whereHas('vinculos', function ($q) {
            $q->where('status_vinculo', 'pendente')->where('ativo', true);
        })->count();
        $totalInativos = RepresentanteUsuario::where('ativo', false)->count();
        $semVinculos = RepresentanteUsuario::where('ativo', true)
            ->whereDoesntHave('vinculos', function ($q) {
                $q->where('status_vinculo', 'aprovado')
                    ->where('ativo', true)
                    ->whereNull('data_desvinculo');
            })->count();

        return view('representantes.index', compact(
            'representantes',
            'totalRepresentantes',
            'totalAtivos',
            'repComPendentes',
            'totalInativos',
            'semVinculos'
        ));
    }

    public function create()
    {
        $user = Auth::user();

        if (! $user->hasAnyRole(['admin', 'gerencial'])) {
            abort(403, 'Acesso negado.');
        }

        return view('representantes.form');
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        if (! $user->hasAnyRole(['admin', 'gerencial'])) {
            abort(403, 'Acesso negado.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->whereNull('deleted_at')],
            'nome_empresa' => 'nullable|string|max:255',
            'cnpj' => 'nullable|string|max:18',
            'telefone' => 'nullable|string|max:20',
            'observacoes' => 'nullable|string|max:1000',
        ], [
            'name.required' => 'O nome é obrigatório.',
            'email.required' => 'O e-mail é obrigatório.',
            'email.email' => 'Informe um e-mail válido.',
            'email.unique' => 'Este e-mail já está cadastrado no sistema.',
        ]);

        $cnpjLimpo = null;
        if (! empty($validated['cnpj'])) {
            $cnpjLimpo = preg_replace('/[^0-9]/', '', $validated['cnpj']);
            if (strlen($cnpjLimpo) === 14) {
                $cnpjDuplicado = Fornecedor::where('cnpj', $cnpjLimpo)->exists()
                    || Cliente::where('cnpj', $cnpjLimpo)->exists()
                    || RepresentanteUsuario::where('cnpj', $cnpjLimpo)->exists();

                if ($cnpjDuplicado) {
                    return back()->withInput()->with('error', 'Este CNPJ já está cadastrado no sistema.');
                }
            }
        }

        $senhaPlainText = $this->gerarSenhaSegura();

        DB::beginTransaction();
        try {
            $userExcluido = User::onlyTrashed()->where('email', $validated['email'])->first();

            if ($userExcluido) {
                $userExcluido->restore();
                $userExcluido->update([
                    'name' => $validated['name'],
                    'password' => Hash::make($senhaPlainText),
                ]);
                $newUser = $userExcluido;

                $newUser->syncRoles(['representante']);

                if ($newUser->genOp) {
                    $newUser->genOp->forceDelete();
                }
                if ($newUser->fornecedor) {
                    $newUser->fornecedor->forceDelete();
                }
                $repExistente = RepresentanteUsuario::withTrashed()->where('user_id', $newUser->id)->first();
                if ($repExistente) {
                    $repExistente->forceDelete();
                }
            } else {
                $newUser = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'password' => Hash::make($senhaPlainText),
                ]);

                $newUser->assignRole('representante');
            }

            RepresentanteUsuario::create([
                'user_id' => $newUser->id,
                'nome_empresa' => $validated['nome_empresa'] ?? null,
                'cnpj' => $cnpjLimpo,
                'telefone' => $validated['telefone'] ?? null,
                'observacoes' => $validated['observacoes'] ?? null,
                'ativo' => true,
            ]);

            MailRotator::send(
                new RepresentanteMail($newUser, $validated['email'], $senhaPlainText, $validated['name']),
                $validated['email']
            );

            activity()
                ->causedBy($newUser)
                ->withProperties(['tipo' => 'senha_temporaria'])
                ->log('Senha temporária enviada - requer troca obrigatória');

            DB::commit();

            return redirect()->route('representantes.index')
                ->with('success', 'Representante criado com sucesso! Credenciais enviadas para ' . $validated['email'] . '.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('security')->error('Erro ao criar representante', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withInput()
                ->with('error', 'Erro ao criar representante: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $user = Auth::user();

        if (! $user->hasAnyRole(['admin', 'gerencial'])) {
            abort(403, 'Acesso negado.');
        }

        $representante = RepresentanteUsuario::with(['user'])->findOrFail($id);

        return view('representantes.form', compact('representante'));
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();

        if (! $user->hasAnyRole(['admin', 'gerencial'])) {
            abort(403, 'Acesso negado.');
        }

        $representante = RepresentanteUsuario::with('user')->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $representante->user_id,
            'nome_empresa' => 'nullable|string|max:255',
            'cnpj' => 'nullable|string|max:18',
            'telefone' => 'nullable|string|max:20',
            'observacoes' => 'nullable|string|max:1000',
            'ativo' => 'nullable|boolean',
        ], [
            'name.required' => 'O nome é obrigatório.',
            'email.required' => 'O e-mail é obrigatório.',
            'email.email' => 'Informe um e-mail válido.',
            'email.unique' => 'Este e-mail já está cadastrado no sistema.',
        ]);

        $cnpjLimpo = null;
        if (! empty($validated['cnpj'])) {
            $cnpjLimpo = preg_replace('/[^0-9]/', '', $validated['cnpj']);
            if (strlen($cnpjLimpo) === 14) {
                $cnpjDuplicado = Fornecedor::where('cnpj', $cnpjLimpo)->exists()
                    || Cliente::where('cnpj', $cnpjLimpo)->exists()
                    || RepresentanteUsuario::where('cnpj', $cnpjLimpo)->where('id', '!=', $representante->id)->exists();

                if ($cnpjDuplicado) {
                    return back()->withInput()->with('error', 'Este CNPJ já está cadastrado no sistema.');
                }
            }
        }

        DB::beginTransaction();
        try {
            if ($representante->user) {
                $representante->user->update([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                ]);
            }

            $novoAtivo = $request->has('ativo');
            $desativando = $representante->ativo && ! $novoAtivo;

            $representante->update([
                'nome_empresa' => $validated['nome_empresa'] ?? null,
                'cnpj' => $cnpjLimpo,
                'telefone' => $validated['telefone'] ?? null,
                'observacoes' => $validated['observacoes'] ?? null,
                'ativo' => $novoAtivo,
            ]);

            if ($desativando) {
                $this->desativarVinculosAtivos($representante);
            }

            DB::commit();

            return redirect()->route('representantes.index')
                ->with('success', 'Representante atualizado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('security')->error('Erro ao atualizar representante', [
                'rep_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()
                ->with('error', 'Erro ao atualizar representante: ' . $e->getMessage());
        }
    }

    public function toggleAtivo($id)
    {
        $user = Auth::user();

        if (! $user->hasAnyRole(['admin', 'gerencial'])) {
            return response()->json(['success' => false, 'message' => 'Acesso negado.'], 403);
        }

        $representante = RepresentanteUsuario::find($id);

        if (! $representante) {
            return response()->json(['success' => false, 'message' => 'Representante não encontrado.'], 404);
        }

        try {
            $novoStatus = ! $representante->ativo;
            $representante->update(['ativo' => $novoStatus]);

            if (! $novoStatus) {
                $this->desativarVinculosAtivos($representante);
            }

            $label = $novoStatus ? 'ativado' : 'desativado';

            activity()
                ->causedBy($user)
                ->performedOn($representante)
                ->log("Representante {$label}");

            return response()->json([
                'success' => true,
                'message' => "Representante {$label} com sucesso!",
                'ativo' => $novoStatus,
            ]);
        } catch (\Exception $e) {
            Log::channel('security')->error('Erro ao alterar status do representante', [
                'rep_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'message' => 'Erro ao alterar status.'], 500);
        }
    }

    public function destroy($id)
    {
        $user = Auth::user();

        if (! $user->hasAnyRole(['admin', 'gerencial'])) {
            return response()->json(['success' => false, 'message' => 'Acesso negado.'], 403);
        }

        $representante = RepresentanteUsuario::with('user')->find($id);

        if (! $representante) {
            return response()->json(['success' => false, 'message' => 'Representante não encontrado.'], 404);
        }

        $vinculosAtivos = $representante->vinculosAtivos()->count();
        if ($vinculosAtivos > 0) {
            return response()->json([
                'success' => false,
                'message' => "Este representante possui {$vinculosAtivos} vínculo(s) ativo(s). Desvincule todos os fornecedores antes de excluir.",
            ], 422);
        }

        DB::beginTransaction();
        try {
            $nome = $representante->user ? $representante->user->name : 'N/A';

            RepresentanteFornecedor::where('representante_usuario_id', $representante->id)
                ->whereNull('deleted_at')
                ->delete();

            $representante->delete();

            if ($representante->user) {
                $representante->user->delete();
            }

            activity()
                ->causedBy($user)
                ->log("Representante excluído: {$nome} (ID: {$id})");

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Representante excluído com sucesso!']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('security')->error('Erro ao excluir representante', [
                'rep_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'message' => 'Erro ao excluir representante.'], 500);
        }
    }

    public function detalhes($id)
    {
        $user = Auth::user();

        if (! $user->hasAnyRole(['admin', 'gerencial'])) {
            return response()->json(['success' => false, 'message' => 'Acesso negado.'], 403);
        }

        $rep = RepresentanteUsuario::with(['user'])->find($id);

        if (! $rep) {
            return response()->json(['success' => false, 'message' => 'Representante não encontrado.'], 404);
        }

        $fornecedorIds = $rep->getFornecedorIds();

        $vinculos = RepresentanteFornecedor::where('representante_usuario_id', $rep->id)
            ->with(['fornecedor', 'aprovadoPor'])
            ->orderByRaw("FIELD(status_vinculo, 'pendente', 'aprovado', 'rejeitado')")
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($v) {
                return [
                    'id' => $v->id,
                    'fornecedor_id' => $v->fornecedor_id,
                    'fornecedor_razao' => $v->fornecedor->razao_social ?? 'N/A',
                    'fornecedor_fantasia' => $v->fornecedor->nome_fantasia,
                    'fornecedor_cnpj' => $v->fornecedor->cnpj ?? '',
                    'status_vinculo' => $v->status_vinculo,
                    'ativo' => $v->ativo,
                    'data_vinculo' => $v->data_vinculo ? $v->data_vinculo->format('d/m/Y H:i') : ($v->created_at ? $v->created_at->format('d/m/Y H:i') : 'N/A'),
                    'data_aprovacao' => $v->data_aprovacao ? $v->data_aprovacao->format('d/m/Y H:i') : null,
                    'data_desvinculo' => $v->data_desvinculo ? $v->data_desvinculo->format('d/m/Y H:i') : null,
                    'aprovado_por' => $v->aprovadoPor ? $v->aprovadoPor->name : null,
                    'motivo_rejeicao' => $v->motivo_rejeicao,
                    'is_aprovado' => $v->isAprovado(),
                    'is_pendente' => $v->isPendente(),
                    'is_rejeitado' => $v->isRejeitado(),
                ];
            });

        $pisAtivos = Pi::whereIn('fornecedor_id', $fornecedorIds)->where('concluido', false)->count();
        $pisConcluidos = Pi::whereIn('fornecedor_id', $fornecedorIds)->where('concluido', true)->count();
        $totalPis = $pisAtivos + $pisConcluidos;

        $docsPendentesEnvio = PiDocumento::whereNull('documento_enviado_id')
            ->whereHas('pi', function ($q) use ($fornecedorIds) {
                $q->whereIn('fornecedor_id', $fornecedorIds)->where('concluido', false);
            })->count();

        $docsEnviados = DocumentoEnviado::whereIn('fornecedor_id', $fornecedorIds)->count();

        $docsAprovados = DocumentoEnviado::whereIn('fornecedor_id', $fornecedorIds)
            ->whereHas('status', function ($q) {
                $q->where('nome', 'Aprovado');
            })->count();

        $docsReprovados = DocumentoEnviado::whereIn('fornecedor_id', $fornecedorIds)
            ->whereHas('status', function ($q) {
                $q->whereIn('nome', ['Reprovado', 'Aguardando Reenvio']);
            })->count();

        $docsAguardandoValidacao = DocumentoEnviado::whereIn('fornecedor_id', $fornecedorIds)
            ->whereHas('status', function ($q) {
                $q->whereIn('nome', ['Pendente', 'Aguardando validação']);
            })->count();

        return response()->json([
            'success' => true,
            'representante' => [
                'id' => $rep->id,
                'user_id' => $rep->user_id,
                'nome' => $rep->user ? $rep->user->name : 'N/A',
                'email' => $rep->user ? $rep->user->email : 'N/A',
                'empresa' => $rep->nome_empresa,
                'cnpj' => $rep->cnpj,
                'telefone' => $rep->telefone,
                'observacoes' => $rep->observacoes,
                'ativo' => $rep->ativo,
                'cadastro_incompleto' => $rep->precisaCompletarCadastro(),
                'campos_faltantes' => $rep->getCamposFaltantes(),
                'progresso_cadastro' => $rep->getProgressoCadastro(),
                'created_at' => $rep->created_at ? $rep->created_at->format('d/m/Y H:i') : 'N/A',
            ],
            'vinculos' => $vinculos,
            'metricas' => [
                'fornecedores_ativos' => count($fornecedorIds),
                'total_pis' => $totalPis,
                'pis_ativos' => $pisAtivos,
                'pis_concluidos' => $pisConcluidos,
                'docs_enviados' => $docsEnviados,
                'docs_aprovados' => $docsAprovados,
                'docs_reprovados' => $docsReprovados,
                'docs_aguardando_validacao' => $docsAguardandoValidacao,
                'docs_pendentes_envio' => $docsPendentesEnvio,
            ],
        ]);
    }

    public function desvincular($id)
    {
        $user = Auth::user();

        if (! $user->hasAnyRole(['admin', 'gerencial'])) {
            return response()->json(['success' => false, 'message' => 'Acesso negado.'], 403);
        }

        $vinculo = RepresentanteFornecedor::where('id', $id)
            ->where('status_vinculo', 'aprovado')
            ->where('ativo', true)
            ->whereNull('data_desvinculo')
            ->first();

        if (! $vinculo) {
            return response()->json(['success' => false, 'message' => 'Vínculo não encontrado ou já desvinculado.'], 404);
        }

        try {
            $vinculo->desvincular();

            activity()
                ->causedBy($user)
                ->performedOn($vinculo)
                ->log('Vínculo de representante desfeito');

            return response()->json(['success' => true, 'message' => 'Vínculo desfeito com sucesso!']);
        } catch (\Exception $e) {
            Log::channel('security')->error('Erro ao desvincular representante', [
                'vinculo_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'message' => 'Erro ao desfazer vínculo.'], 500);
        }
    }

    public function reenviarCredenciais($id)
    {
        $user = Auth::user();

        if (! $user->hasAnyRole(['admin', 'gerencial'])) {
            return response()->json(['success' => false, 'message' => 'Acesso negado.'], 403);
        }

        $representante = RepresentanteUsuario::with('user')->find($id);

        if (! $representante || ! $representante->user) {
            return response()->json(['success' => false, 'message' => 'Representante não encontrado.'], 404);
        }

        try {
            $novaSenha = $this->gerarSenhaSegura();
            $representante->user->update([
                'password' => Hash::make($novaSenha),
            ]);

            activity()
                ->causedBy($representante->user)
                ->withProperties(['tipo' => 'senha_temporaria'])
                ->log('Senha temporária enviada - requer troca obrigatória');

            MailRotator::send(
                new ReenvioCredenciaisMail(
                    $representante->user,
                    $representante->user->email,
                    $novaSenha,
                    $representante->user->name,
                    'representante'
                ),
                $representante->user->email
            );

            activity()
                ->causedBy($user)
                ->performedOn($representante)
                ->log('Credenciais reenviadas para representante');

            return response()->json([
                'success' => true,
                'message' => 'Nova senha gerada e enviada para ' . $representante->user->email . '!',
            ]);
        } catch (\Exception $e) {
            Log::channel('security')->error('Erro ao reenviar credenciais', [
                'rep_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'message' => 'Erro ao reenviar credenciais: ' . $e->getMessage()], 500);
        }
    }

    public function validarEmail(Request $request)
    {
        $email = $request->input('email', '');

        if (empty($email)) {
            return response()->json(['disponivel' => false, 'mensagem' => 'E-mail não informado.']);
        }

        $query = User::where('email', $email);

        if ($request->filled('user_id')) {
            $query->where('id', '!=', $request->user_id);
        }

        $existe = $query->exists();

        return response()->json([
            'disponivel' => ! $existe,
            'mensagem' => $existe ? 'Este e-mail já está cadastrado no sistema.' : 'E-mail disponível',
        ]);
    }

    private function desativarVinculosAtivos(RepresentanteUsuario $representante): void
    {
        $vinculosAtivos = RepresentanteFornecedor::where('representante_usuario_id', $representante->id)
            ->where('status_vinculo', 'aprovado')
            ->where('ativo', true)
            ->whereNull('data_desvinculo')
            ->get();

        foreach ($vinculosAtivos as $vinculo) {
            $vinculo->desvincular();
        }

        RepresentanteFornecedor::where('representante_usuario_id', $representante->id)
            ->where('status_vinculo', 'pendente')
            ->where('ativo', true)
            ->update(['ativo' => false]);
    }

    private function gerarSenhaSegura(): string
    {
        $upper = 'ABCDEFGHJKMNPQRSTUVWXYZ';
        $lower = 'abcdefghjkmnpqrstuvwxyz';
        $numbers = '23456789';
        $special = '!@#$%&*';

        $senha = '';
        $senha .= $upper[random_int(0, strlen($upper) - 1)];
        $senha .= $lower[random_int(0, strlen($lower) - 1)];
        $senha .= $numbers[random_int(0, strlen($numbers) - 1)];
        $senha .= $special[random_int(0, strlen($special) - 1)];

        $all = $upper . $lower . $numbers . $special;
        for ($i = strlen($senha); $i < 16; $i++) {
            $senha .= $all[random_int(0, strlen($all) - 1)];
        }

        return str_shuffle($senha);
    }
}