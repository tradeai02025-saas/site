<?php

/* app/Http/Controllers/TipoDocumentoController.php */

namespace App\Http\Controllers;

use App\Models\{TipoDocumento, TipoVeiculo};
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Log};
use Illuminate\Validation\Rule;

class TipoDocumentoController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user->hasRole('admin');

        if ($isAdmin) {
            $query = TipoDocumento::withTrashed();
            $totalGeral = TipoDocumento::withTrashed()->count();
        } else {
            $query = TipoDocumento::whereNull('deleted_at');
            $totalGeral = TipoDocumento::whereNull('deleted_at')->count();
        }

        $query->with('tipoVeiculos');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nome', 'like', "%{$search}%")
                  ->orWhere('descricao', 'like', "%{$search}%");
            });
        }

        if ($request->has('validar') && $request->validar !== '' && $request->validar !== null) {
            $validarValue = filter_var($request->validar, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($validarValue !== null) {
                $query->where('validar', $validarValue);
            }
        }

        if ($request->has('ativo') && $request->ativo !== '' && $request->ativo !== null) {
            $ativoValue = filter_var($request->ativo, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($ativoValue !== null) {
                $query->where('ativo', $ativoValue);
            }
        }

        if ($request->filled('status') && $isAdmin) {
            if ($request->status === 'inativos') {
                $query->onlyTrashed();
            } else {
                $query->withoutTrashed();
            }
        }

        if ($request->filled('validade_tipo')) {
            if ($request->validade_tipo === 'indeterminada') {
                $query->where('validade_dias', 0);
            } elseif ($request->validade_tipo === 'com_prazo') {
                $query->where('validade_dias', '>', 0);
            }
        }

        if ($request->filled('validade_min')) {
            $query->where('validade_dias', '>=', $request->validade_min);
        }

        if ($request->filled('validade_max')) {
            $query->where('validade_dias', '<=', $request->validade_max);
        }

        if ($request->filled('tipo_veiculo_id')) {
            $query->whereHas('tipoVeiculos', function($q) use ($request) {
                $q->where('tipo_veiculos.id', $request->tipo_veiculo_id);
            });
        }

        $tipoDocumentos = $query->orderBy('nome')->paginate(15)->withQueryString();

        $totalAtivos = TipoDocumento::whereNull('deleted_at')->where('ativo', true)->count();
        $totalComControle = TipoDocumento::whereNull('deleted_at')->where('validar', true)->count();
        $tipoVeiculos = CacheService::tiposVeiculoAtivos();

        return view('tipo-documentos.index', compact(
            'tipoDocumentos',
            'totalGeral',
            'totalAtivos',
            'totalComControle',
            'tipoVeiculos'
        ));
    }

    public function create()
    {
        $user = Auth::user();

        if (!$user->hasRole(['admin', 'gerencial'])) {
            abort(403, 'Você não tem permissão para criar tipos de documentos.');
        }

        $tipoVeiculos = CacheService::tiposVeiculoAtivos();

        return view('tipo-documentos.form', compact('tipoVeiculos'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user->hasRole(['admin', 'gerencial'])) {
            abort(403, 'Você não tem permissão para criar tipos de documentos.');
        }

        $validadeIndeterminada = $request->boolean('validade_indeterminada');
        $validar = $request->boolean('validar');

        $validated = $request->validate([
            'nome' => 'required|string|max:255|unique:tipo_documentos,nome',
            'descricao' => 'nullable|string|max:1000',
            'validade_dias' => ($validar && !$validadeIndeterminada) ? 'required|integer|min:1|max:3650' : 'nullable',
            'validar' => 'nullable|boolean',
            'avulso' => 'nullable|boolean',
            'ativo' => 'nullable|boolean',
            'validade_indeterminada' => 'nullable|boolean',
            'tipo_veiculos' => 'nullable|array',
            'tipo_veiculos.*' => 'exists:tipo_veiculos,id',
        ], [
            'nome.required' => 'O nome do tipo de documento é obrigatório.',
            'nome.unique' => 'Já existe um tipo de documento com este nome.',
            'validade_dias.required' => 'O prazo de validade é obrigatório quando o controle está ativado.',
            'validade_dias.min' => 'O prazo de validade deve ser de pelo menos 1 dia.',
            'validade_dias.max' => 'O prazo de validade não pode exceder 3650 dias (10 anos).',
        ]);

        DB::beginTransaction();
        try {
            if (!$validar) {
                $validated['validade_dias'] = 0;
            } else {
                $validated['validade_dias'] = $validadeIndeterminada ? 0 : $validated['validade_dias'];
            }

            $validated['validar'] = $validar;
            $validated['ativo'] = $request->boolean('ativo', true);
            $validated['avulso'] = $request->boolean('avulso', false);

            unset($validated['validade_indeterminada']);
            unset($validated['tipo_veiculos']);

            $tipoDocumento = TipoDocumento::create($validated);

            if ($request->filled('tipo_veiculos')) {
                $tipoDocumento->tipoVeiculos()->sync($request->tipo_veiculos);
            }

            DB::commit();

            return redirect()
                ->route('tipo_documentos.index')
                ->with('success', 'Tipo de documento criado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('docs')->error('Erro ao criar tipo de documento', [
                'user_id' => Auth::id(),
                'nome' => $validated['nome'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()
                ->withInput()
                ->with('error', 'Erro ao criar tipo de documento: ' . $e->getMessage());
        }
    }

    public function edit(TipoDocumento $tipoDocumento)
    {
        $user = Auth::user();

        if (!$user->hasRole(['admin', 'gerencial'])) {
            abort(403, 'Você não tem permissão para editar tipos de documentos.');
        }

        $tipoDocumento->load('tipoVeiculos');
        $tipoVeiculos = CacheService::tiposVeiculoAtivos();

        return view('tipo-documentos.form', compact('tipoDocumento', 'tipoVeiculos'));
    }

    public function update(Request $request, TipoDocumento $tipoDocumento)
    {
        $user = Auth::user();

        if (!$user->hasRole(['admin', 'gerencial'])) {
            abort(403, 'Você não tem permissão para editar tipos de documentos.');
        }

        $validadeIndeterminada = $request->boolean('validade_indeterminada');
        $validar = $request->boolean('validar');

        $validated = $request->validate([
            'nome' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tipo_documentos')->ignore($tipoDocumento->id)
            ],
            'descricao' => 'nullable|string|max:1000',
            'validade_dias' => ($validar && !$validadeIndeterminada) ? 'required|integer|min:1|max:3650' : 'nullable',
            'validar' => 'nullable|boolean',
            'avulso' => 'nullable|boolean',
            'ativo' => 'nullable|boolean',
            'validade_indeterminada' => 'nullable|boolean',
            'tipo_veiculos' => 'nullable|array',
            'tipo_veiculos.*' => 'exists:tipo_veiculos,id',
        ], [
            'nome.required' => 'O nome do tipo de documento é obrigatório.',
            'nome.unique' => 'Já existe um tipo de documento com este nome.',
            'validade_dias.required' => 'O prazo de validade é obrigatório quando o controle está ativado.',
            'validade_dias.min' => 'O prazo de validade deve ser de pelo menos 1 dia.',
            'validade_dias.max' => 'O prazo de validade não pode exceder 3650 dias (10 anos).',
        ]);

        DB::beginTransaction();
        try {
            if (!$validar) {
                $validated['validade_dias'] = 0;
            } else {
                $validated['validade_dias'] = $validadeIndeterminada ? 0 : $validated['validade_dias'];
            }

            $validated['validar'] = $validar;
            $validated['ativo'] = $request->boolean('ativo');
            $validated['avulso'] = $request->boolean('avulso', false);

            unset($validated['validade_indeterminada']);
            unset($validated['tipo_veiculos']);

            $tipoDocumento->update($validated);

            $tipoDocumento->tipoVeiculos()->sync($request->tipo_veiculos ?? []);

            DB::commit();

            return redirect()
                ->route('tipo_documentos.index')
                ->with('success', 'Tipo de documento atualizado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('docs')->error('Erro ao atualizar tipo de documento', [
                'tipo_documento_id' => $tipoDocumento->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()
                ->withInput()
                ->with('error', 'Erro ao atualizar tipo de documento: ' . $e->getMessage());
        }
    }

    public function destroy(TipoDocumento $tipoDocumento)
    {
        $user = Auth::user();

        if (!$user->hasRole(['admin', 'gerencial'])) {
            abort(403, 'Você não tem permissão para excluir tipos de documentos.');
        }

        try {
            $usageCount = $tipoDocumento->clienteDocumentosRequeridos()->count()
                + $tipoDocumento->piDocumentos()->count()
                + $tipoDocumento->documentosEnviados()->count();

            if ($usageCount > 0) {
                return back()->with('warning', 'Este tipo de documento está sendo utilizado em ' . $usageCount . ' registro(s) e não pode ser excluído. Você pode desativá-lo.');
            }

            $tipoDocumento->tipoVeiculos()->detach();
            $tipoDocumento->delete();

            return redirect()
                ->route('tipo_documentos.index')
                ->with('success', 'Tipo de documento excluído com sucesso!');
        } catch (\Exception $e) {
            Log::channel('docs')->error('Erro ao excluir tipo de documento', [
                'tipo_documento_id' => $tipoDocumento->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Erro ao excluir tipo de documento: ' . $e->getMessage());
        }
    }

    public function restore($id)
    {
        $user = Auth::user();

        if (!$user->hasRole('admin')) {
            abort(403, 'Apenas administradores podem restaurar tipos de documentos.');
        }

        try {
            $tipoDocumento = TipoDocumento::withTrashed()->findOrFail($id);
            $tipoDocumento->restore();

            return redirect()
                ->route('tipo_documentos.index')
                ->with('success', 'Tipo de documento restaurado com sucesso!');
        } catch (\Exception $e) {
            Log::channel('docs')->error('Erro ao restaurar tipo de documento', [
                'tipo_documento_id' => $id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Erro ao restaurar tipo de documento: ' . $e->getMessage());
        }
    }

    public function toggleAtivo(TipoDocumento $tipoDocumento)
    {
        $user = Auth::user();

        if (!$user->hasRole(['admin', 'gerencial'])) {
            return response()->json([
                'success' => false,
                'message' => 'Você não tem permissão para alterar o status.'
            ], 403);
        }

        try {
            $tipoDocumento->update(['ativo' => !$tipoDocumento->ativo]);

            return response()->json([
                'success' => true,
                'message' => 'Status alterado com sucesso!',
                'ativo' => $tipoDocumento->ativo
            ]);
        } catch (\Exception $e) {
            Log::channel('docs')->error('Erro ao alternar ativo do tipo de documento', [
                'tipo_documento_id' => $tipoDocumento->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erro ao alterar status: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getAtivos()
    {
        try {
            $tipoDocumentos = CacheService::tiposDocumentoComVeiculos()
                ->map(function($tipo) {
                    return [
                        'id' => $tipo->id,
                        'nome' => $tipo->nome,
                        'descricao' => $tipo->descricao,
                        'validade_dias' => $tipo->validade_dias,
                        'validade_indeterminada' => $tipo->validade_dias === 0,
                        'validar' => $tipo->validar,
                        'avulso' => $tipo->avulso,
                        'tipo_veiculos' => $tipo->tipoVeiculos->pluck('id')->toArray(),
                        'tipo_veiculos_nomes' => $tipo->tipoVeiculos->pluck('nome')->toArray(),
                        'is_universal' => $tipo->isUniversal(),
                        'is_geral' => $tipo->isGeral(),
                        'is_todos_veiculos' => $tipo->isTodosVeiculos(),
                    ];
                });

            return response()->json([
                'success' => true,
                'tipos_documentos' => $tipoDocumentos
            ]);
        } catch (\Exception $e) {
            Log::channel('docs')->error('Erro ao buscar tipos de documentos ativos', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar tipos de documentos: ' . $e->getMessage()
            ], 500);
        }
    }

    public function verificarUsage(TipoDocumento $tipoDocumento)
    {
        try {
            $usage = [
                'clientes_requerem' => $tipoDocumento->clienteDocumentosRequeridos()->count(),
                'pis_vinculados' => $tipoDocumento->piDocumentos()->count(),
                'documentos_enviados' => $tipoDocumento->documentosEnviados()->count(),
            ];

            $usage['total'] = array_sum($usage);
            $usage['pode_excluir'] = $usage['total'] === 0;

            return response()->json([
                'success' => true,
                'usage' => $usage
            ]);
        } catch (\Exception $e) {
            Log::channel('docs')->error('Erro ao verificar uso do tipo de documento', [
                'tipo_documento_id' => $tipoDocumento->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erro ao verificar uso: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getByTipoVeiculo(Request $request)
    {
        try {
            $tipoVeiculoId = $request->tipo_veiculo_id;

            $colecao = CacheService::tiposDocumentoComVeiculos();

            if ($tipoVeiculoId) {
                $colecao = $colecao->filter(function ($tipo) use ($tipoVeiculoId) {
                    return $tipo->tipoVeiculos->isEmpty()
                        || $tipo->tipoVeiculos->contains('id', (int) $tipoVeiculoId);
                });
            }

            $tipoDocumentos = $colecao->values()->map(function($tipo) {
                return [
                    'id' => $tipo->id,
                    'nome' => $tipo->nome,
                    'descricao' => $tipo->descricao,
                    'validade_dias' => $tipo->validade_dias,
                    'validar' => $tipo->validar,
                    'avulso' => $tipo->avulso,
                    'is_universal' => $tipo->isUniversal(),
                    'is_geral' => $tipo->isGeral(),
                    'is_todos_veiculos' => $tipo->isTodosVeiculos(),
                ];
            });

            return response()->json([
                'success' => true,
                'tipos_documentos' => $tipoDocumentos
            ]);
        } catch (\Exception $e) {
            Log::channel('docs')->error('Erro ao buscar tipos de documentos por veículo', [
                'tipo_veiculo_id' => $request->tipo_veiculo_id ?? null,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar tipos de documentos: ' . $e->getMessage()
            ], 500);
        }
    }
}