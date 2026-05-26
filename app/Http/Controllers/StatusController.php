<?php

/* app/Http/Controllers/StatusController.php */

namespace App\Http\Controllers;

use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Log};
use Illuminate\Validation\Rule;

class StatusController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user->hasRole('admin')) {
            abort(403, 'Apenas administradores podem gerenciar status.');
        }

        $query = Status::withTrashed()->withCount(['pis', 'documentosEnviados']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nome', 'like', "%{$search}%")
                  ->orWhere('descricao', 'like', "%{$search}%");
            });
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->has('ativo') && $request->ativo !== '' && $request->ativo !== null) {
            $ativoValue = filter_var($request->ativo, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($ativoValue !== null) {
                $query->where('ativo', $ativoValue);
            }
        }

        if ($request->filled('status_exibicao')) {
            if ($request->status_exibicao === 'inativos') {
                $query->onlyTrashed();
            } elseif ($request->status_exibicao === 'ativos') {
                $query->withoutTrashed()->where('ativo', true);
            } else {
                $query->withoutTrashed();
            }
        }

        $status = $query->orderBy('tipo')->orderBy('nome')->paginate(15)->withQueryString();

        $totalStatus = Status::withTrashed()->count();
        $statusAtivos = Status::whereNull('deleted_at')->where('ativo', true)->count();
        $statusInativos = Status::onlyTrashed()->count();

        $statusPorTipo = Status::whereNull('deleted_at')
            ->selectRaw('tipo, COUNT(*) as total')
            ->groupBy('tipo')
            ->get();

        return view('status.index', compact(
            'status',
            'totalStatus',
            'statusAtivos',
            'statusInativos',
            'statusPorTipo'
        ));
    }

    public function create()
    {
        $user = Auth::user();

        if (!$user->hasRole('admin')) {
            abort(403, 'Apenas administradores podem criar status.');
        }

        return view('status.form');
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user->hasRole('admin')) {
            abort(403, 'Apenas administradores podem criar status.');
        }

        $validated = $request->validate([
            'nome' => 'required|string|max:255|unique:status,nome',
            'tipo' => 'required|in:pi,documento,geral',
            'cor' => 'required|string|max:7|regex:/^#[a-fA-F0-9]{6}$/',
            'ativo' => 'required|boolean',
            'descricao' => 'nullable|string|max:1000',
        ], [
            'nome.required' => 'O nome do status é obrigatório.',
            'nome.unique' => 'Já existe um status com este nome.',
            'tipo.required' => 'O tipo do status é obrigatório.',
            'tipo.in' => 'O tipo deve ser: pi, documento ou geral.',
            'cor.required' => 'A cor do status é obrigatória.',
            'cor.regex' => 'A cor deve estar no formato hexadecimal (#000000).',
            'ativo.required' => 'É necessário informar se o status está ativo.',
        ]);

        try {
            $status = Status::create($validated);

            return redirect()->route('status.index')
                ->with('success', 'Status criado com sucesso!');
        } catch (\Exception $e) {
            Log::channel('pis')->error('Erro ao criar status', [
                'user_id' => Auth::id(),
                'dados' => $validated ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withInput()
                ->with('error', 'Erro ao criar status: ' . $e->getMessage());
        }
    }

    public function edit(Status $status)
    {
        $user = Auth::user();

        if (!$user->hasRole('admin')) {
            abort(403, 'Apenas administradores podem editar status.');
        }

        return view('status.form', compact('status'));
    }

    public function update(Request $request, Status $status)
    {
        $user = Auth::user();

        if (!$user->hasRole('admin')) {
            abort(403, 'Apenas administradores podem editar status.');
        }

        $validated = $request->validate([
            'nome' => ['required', 'string', 'max:255', Rule::unique('status')->ignore($status->id)],
            'tipo' => 'required|in:pi,documento,geral',
            'cor' => 'required|string|max:7|regex:/^#[a-fA-F0-9]{6}$/',
            'ativo' => 'required|boolean',
            'descricao' => 'nullable|string|max:1000',
        ], [
            'nome.required' => 'O nome do status é obrigatório.',
            'nome.unique' => 'Já existe um status com este nome.',
            'tipo.required' => 'O tipo do status é obrigatório.',
            'tipo.in' => 'O tipo deve ser: pi, documento ou geral.',
            'cor.required' => 'A cor do status é obrigatória.',
            'cor.regex' => 'A cor deve estar no formato hexadecimal (#000000).',
            'ativo.required' => 'É necessário informar se o status está ativo.',
        ]);

        try {
            if ($status->tipo !== $validated['tipo']) {
                $pisCount = $status->pis()->count();
                $piDocumentosCount = $status->piDocumentos()->count();
                $documentosCount = $status->documentosEnviados()->count();
                $validacoesCount = $status->validacoes()->count();

                if ($pisCount > 0 || $piDocumentosCount > 0 || $documentosCount > 0 || $validacoesCount > 0) {
                    return back()->withInput()
                        ->with('warning', 'Não é possível alterar o tipo de um status que está sendo utilizado.');
                }
            }

            $status->update($validated);

            return redirect()->route('status.index')
                ->with('success', 'Status atualizado com sucesso!');
        } catch (\Exception $e) {
            Log::channel('pis')->error('Erro ao atualizar status', [
                'status_id' => $status->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withInput()
                ->with('error', 'Erro ao atualizar status: ' . $e->getMessage());
        }
    }

    public function destroy(Status $status)
    {
        $user = Auth::user();

        if (!$user->hasRole('admin')) {
            abort(403, 'Apenas administradores podem excluir status.');
        }

        try {
            $pisCount = $status->pis()->count();
            $piDocumentosCount = $status->piDocumentos()->count();
            $documentosCount = $status->documentosEnviados()->count();
            $validacoesCount = $status->validacoes()->count();

            $totalVinculos = $pisCount + $piDocumentosCount + $documentosCount + $validacoesCount;

            if ($totalVinculos > 0) {
                $mensagem = 'Este status está vinculado a ';
                $vinculos = [];

                if ($pisCount > 0) {
                    $vinculos[] = "{$pisCount} PI(s)";
                }
                if ($piDocumentosCount > 0) {
                    $vinculos[] = "{$piDocumentosCount} documento(s) de PI";
                }
                if ($documentosCount > 0) {
                    $vinculos[] = "{$documentosCount} documento(s) enviado(s)";
                }
                if ($validacoesCount > 0) {
                    $vinculos[] = "{$validacoesCount} validação(ões)";
                }

                $mensagem .= implode(', ', $vinculos) . ' e não pode ser excluído.';

                return back()->with('warning', $mensagem);
            }

            $status->delete();

            return redirect()->route('status.index')
                ->with('success', 'Status excluído com sucesso!');
        } catch (\Exception $e) {
            Log::channel('pis')->error('Erro ao excluir status', [
                'status_id' => $status->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Erro ao excluir status: ' . $e->getMessage());
        }
    }

    public function restore($id)
    {
        $user = Auth::user();

        if (!$user->hasRole('admin')) {
            abort(403, 'Apenas administradores podem restaurar status.');
        }

        try {
            $status = Status::withTrashed()->findOrFail($id);
            $status->restore();

            return redirect()->route('status.index')
                ->with('success', 'Status restaurado com sucesso!');
        } catch (\Exception $e) {
            Log::channel('pis')->error('Erro ao restaurar status', [
                'status_id' => $id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Erro ao restaurar status: ' . $e->getMessage());
        }
    }

    public function getStatusPorTipo(Request $request)
    {
        $validated = $request->validate([
            'tipo' => 'required|in:pi,documento,geral',
        ]);

        try {
            $statusList = Status::where('tipo', $validated['tipo'])
                ->where('ativo', true)
                ->whereNull('deleted_at')
                ->orderBy('nome')
                ->get()
                ->map(function($status) {
                    return [
                        'id' => $status->id,
                        'nome' => $status->nome,
                        'cor' => $status->cor,
                        'descricao' => $status->descricao,
                    ];
                });

            return response()->json([
                'success' => true,
                'status' => $statusList
            ]);
        } catch (\Exception $e) {
            Log::channel('pis')->error('Erro ao buscar status por tipo', [
                'tipo' => $validated['tipo'] ?? null,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar status: ' . $e->getMessage()
            ], 500);
        }
    }

    public function toggleAtivo(Status $status)
    {
        $user = Auth::user();

        if (!$user->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Apenas administradores podem alterar status.'
            ], 403);
        }

        try {
            if ($status->ativo) {
                $statusCriticos = [
                    'pi' => ['Gerado', 'Aguardando Documentos', 'Em Processo', 'Concluído', 'Reenvio de Documentos'],
                    'documento' => ['Pendente', 'Aprovado', 'Reprovado', 'Aguardando validação', 'Aguardando Reenvio', 'Atendido'],
                ];

                $tiposCriticos = $statusCriticos[$status->tipo] ?? [];

                if (in_array($status->nome, $tiposCriticos)) {
                    $pisCount = $status->pis()->count();
                    $piDocumentosCount = $status->piDocumentos()->count();
                    $documentosCount = $status->documentosEnviados()->count();
                    $totalVinculos = $pisCount + $piDocumentosCount + $documentosCount;

                    if ($totalVinculos > 0) {
                        return response()->json([
                            'success' => false,
                            'message' => "Este status é crítico para o funcionamento do sistema e está vinculado a {$totalVinculos} registro(s). Desativá-lo pode causar erros em múltiplos fluxos.",
                            'em_uso' => true,
                            'total_vinculos' => $totalVinculos,
                        ], 422);
                    }
                }
            }

            $status->update(['ativo' => !$status->ativo]);

            return response()->json([
                'success' => true,
                'message' => $status->ativo ? 'Status ativado com sucesso!' : 'Status desativado com sucesso!',
                'ativo' => $status->ativo
            ]);
        } catch (\Exception $e) {
            Log::channel('pis')->error('Erro ao alternar status ativo', [
                'status_id' => $status->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erro ao alterar status: ' . $e->getMessage()
            ], 500);
        }
    }

    public function verificarUso(Status $status)
    {
        $user = Auth::user();

        if (!$user->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso negado.'
            ], 403);
        }

        try {
            $pisCount = $status->pis()->count();
            $piDocumentosCount = $status->piDocumentos()->count();
            $documentosCount = $status->documentosEnviados()->count();
            $validacoesCount = $status->validacoes()->count();

            $emUso = ($pisCount + $piDocumentosCount + $documentosCount + $validacoesCount) > 0;

            return response()->json([
                'success' => true,
                'em_uso' => $emUso,
                'detalhes' => [
                    'pis' => $pisCount,
                    'pi_documentos' => $piDocumentosCount,
                    'documentos' => $documentosCount,
                    'validacoes' => $validacoesCount,
                ]
            ]);
        } catch (\Exception $e) {
            Log::channel('pis')->error('Erro ao verificar uso do status', [
                'status_id' => $status->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erro ao verificar uso: ' . $e->getMessage()
            ], 500);
        }
    }
}