<?php

/* app/Http/Controllers/DashboardController.php */

namespace App\Http\Controllers;

use App\Models\{Cliente, Conversa, DocumentoEnviado, Fornecedor, GenOp, Pi, PiDocumento, RepresentanteFornecedor, User};
use App\Services\CacheService;
use Carbon\Carbon;
use Illuminate\Support\Facades\{Auth, Cache, DB, Log};

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $role = $user->roles->first()?->name;

        try {
            return match ($role) {
                'admin' => $this->dashboardAdmin($user),
                'gerencial' => $this->dashboardGerencial($user),
                'operacional' => $this->dashboardOperacional($user),
                'fornecedor' => $this->dashboardFornecedor($user),
                'representante' => $this->dashboardRepresentante($user),
                default => abort(403, 'Acesso negado'),
            };
        } catch (\Exception $e) {
            Log::channel('pis')->error('Erro ao carregar dashboard', [
                'user_id' => $user->id,
                'role' => $role,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            abort(500, 'Erro ao carregar o painel. A equipe técnica foi notificada.');
        }
    }

    private function dashboardAdmin($user)
    {
        $hoje = Carbon::today();

        $userStats = User::select(
            DB::raw('COUNT(*) as total'),
            DB::raw('SUM(CASE WHEN deleted_at IS NULL THEN 1 ELSE 0 END) as ativos'),
            DB::raw('SUM(CASE WHEN deleted_at IS NOT NULL THEN 1 ELSE 0 END) as inativos')
        )->withTrashed()->first();

        $piStats = Pi::select(
            DB::raw('COUNT(*) as total'),
            DB::raw('SUM(CASE WHEN concluido = 1 THEN 1 ELSE 0 END) as concluidas'),
            DB::raw('SUM(CASE WHEN concluido = 0 THEN 1 ELSE 0 END) as pendentes')
        )->first();

        $clienteStats = Cliente::select(
            DB::raw('COUNT(*) as total'),
            DB::raw('SUM(CASE WHEN deleted_at IS NULL THEN 1 ELSE 0 END) as ativos')
        )->withTrashed()->first();

        $fornecedorStats = Fornecedor::select(
            DB::raw('COUNT(*) as total'),
            DB::raw('SUM(CASE WHEN deleted_at IS NULL THEN 1 ELSE 0 END) as ativos')
        )->withTrashed()->first();

        $totalUsuarios = (int) $userStats->total;
        $usuariosAtivos = (int) $userStats->ativos;
        $usuariosInativos = (int) $userStats->inativos;
        $totalPis = (int) $piStats->total;
        $pisConcluidas = (int) $piStats->concluidas;
        $pisPendentes = (int) $piStats->pendentes;
        $totalClientes = (int) $clienteStats->total;
        $clientesAtivos = (int) $clienteStats->ativos;
        $totalFornecedores = (int) $fornecedorStats->total;
        $fornecedoresAtivos = (int) $fornecedorStats->ativos;

        $docStats = DocumentoEnviado::select(
            DB::raw("SUM(CASE WHEN s.nome IN ('Pendente', 'Aguardando validação') THEN 1 ELSE 0 END) as pendentes"),
            DB::raw("SUM(CASE WHEN s.nome = 'Aprovado' AND documentos_enviados.valido = 1 AND (documentos_enviados.data_validade_documento IS NULL OR documentos_enviados.data_validade_documento >= ?) THEN 1 ELSE 0 END) as aprovados"),
            DB::raw("SUM(CASE WHEN s.nome IN ('Reprovado', 'Aguardando Reenvio') THEN 1 ELSE 0 END) as reprovados"),
            DB::raw("SUM(CASE WHEN documentos_enviados.data_validade_documento IS NOT NULL AND documentos_enviados.data_validade_documento < ? AND s.nome NOT IN ('Reprovado', 'Aguardando Reenvio') THEN 1 ELSE 0 END) as vencidos")
        )
            ->join('status as s', 'documentos_enviados.status_id', '=', 's.id')
            ->whereNull('documentos_enviados.deleted_at')
            ->setBindings([$hoje, $hoje])
            ->first();

        $documentosPendentes = (int) ($docStats->pendentes ?? 0);
        $documentosAprovados = (int) ($docStats->aprovados ?? 0);
        $documentosReprovados = (int) ($docStats->reprovados ?? 0);
        $documentosVencidos = (int) ($docStats->vencidos ?? 0);

        $pisRecentes = Pi::with(['cliente', 'fornecedor', 'status', 'gerencial.user', 'operacionais'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        $pisPorStatus = Pi::select('status_id', DB::raw('count(*) as total'))
            ->with('status')
            ->groupBy('status_id')
            ->get();

        $pisPorMes = Pi::select(
            DB::raw('MONTH(data_cadastro) as mes'),
            DB::raw('COUNT(*) as total')
        )
            ->whereYear('data_cadastro', Carbon::now()->year)
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();

        $documentosVencendo = DocumentoEnviado::with(['fornecedor', 'tipoDocumento'])
            ->where('valido', true)
            ->whereBetween('data_validade_documento', [$hoje, $hoje->copy()->addDays(30)])
            ->orderBy('data_validade_documento', 'asc')
            ->take(10)
            ->get();

        $vinculosPendentes = Cache::remember('pending_vinculos_count', 300, function () {
            return RepresentanteFornecedor::where('status_vinculo', 'pendente')
                ->where('ativo', true)
                ->count();
        });

        return view('dashboard', compact(
            'totalUsuarios',
            'usuariosAtivos',
            'usuariosInativos',
            'totalPis',
            'pisConcluidas',
            'pisPendentes',
            'totalClientes',
            'clientesAtivos',
            'totalFornecedores',
            'fornecedoresAtivos',
            'documentosPendentes',
            'documentosAprovados',
            'documentosReprovados',
            'documentosVencidos',
            'pisRecentes',
            'pisPorStatus',
            'pisPorMes',
            'documentosVencendo',
            'vinculosPendentes'
        ));
    }

    private function dashboardGerencial($user)
    {
        $genOp = $user->genOp;

        if (! $genOp) {
            abort(403, 'Perfil gerencial não encontrado');
        }

        $hoje = Carbon::today();

        $piStats = Pi::select(
            DB::raw('COUNT(*) as total'),
            DB::raw('SUM(CASE WHEN concluido = 1 THEN 1 ELSE 0 END) as concluidas'),
            DB::raw('SUM(CASE WHEN concluido = 0 THEN 1 ELSE 0 END) as pendentes')
        )->where('gerencial_id', $genOp->id)->first();

        $minhasPis = (int) $piStats->total;
        $minhasPisConcluidas = (int) $piStats->concluidas;
        $minhasPisPendentes = (int) $piStats->pendentes;

        $totalOperacionais = GenOp::whereHas('user.roles', function ($query) {
            $query->where('name', 'operacional');
        })->count();

        $operacionaisDisponiveis = GenOp::whereHas('user.roles', function ($query) {
            $query->where('name', 'operacional');
        })
            ->whereDoesntHave('pisAtribuidos', function ($query) {
                $query->where('concluido', false);
            })
            ->count();

        $conversasAbertas = Conversa::where('status_conversa', 'aberta')->count();

        $entityStats = DB::selectOne("
            SELECT
                (SELECT COUNT(*) FROM fornecedores WHERE deleted_at IS NULL) as fornecedores_ativos,
                (SELECT COUNT(*) FROM clientes WHERE deleted_at IS NULL) as clientes_ativos
        ");

        $fornecedoresAtivos = (int) $entityStats->fornecedores_ativos;
        $clientesAtivos = (int) $entityStats->clientes_ativos;

        $documentosPendentesValidacao = DocumentoEnviado::whereHas('status', function ($query) {
                $query->whereIn('nome', ['Pendente', 'Aguardando validação']);
            })
            ->count();

        $pisRecentes = Pi::with(['cliente', 'fornecedor', 'status', 'operacionais'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        $pisPorStatus = Pi::select('status_id', DB::raw('count(*) as total'))
            ->with('status')
            ->groupBy('status_id')
            ->get();

        $operacionaisComCarga = GenOp::whereHas('user.roles', function ($query) {
            $query->where('name', 'operacional');
        })
            ->withCount(['pisAtribuidos' => function ($query) {
                $query->where('concluido', false);
            }])
            ->with('user')
            ->get();

        $documentosVencendo = DocumentoEnviado::with(['fornecedor', 'tipoDocumento'])
            ->where('valido', true)
            ->whereBetween('data_validade_documento', [$hoje, $hoje->copy()->addDays(30)])
            ->orderBy('data_validade_documento', 'asc')
            ->take(10)
            ->get();

        $vinculosPendentes = Cache::remember('pending_vinculos_count', 300, function () {
            return RepresentanteFornecedor::where('status_vinculo', 'pendente')
                ->where('ativo', true)
                ->count();
        });

        return view('dashboard', compact(
            'minhasPis',
            'minhasPisConcluidas',
            'minhasPisPendentes',
            'totalOperacionais',
            'operacionaisDisponiveis',
            'fornecedoresAtivos',
            'clientesAtivos',
            'documentosPendentesValidacao',
            'pisRecentes',
            'pisPorStatus',
            'operacionaisComCarga',
            'documentosVencendo',
            'conversasAbertas',
            'vinculosPendentes'
        ));
    }

    private function dashboardOperacional($user)
    {
        $genOp = $user->genOp;

        if (! $genOp) {
            abort(403, 'Perfil operacional não encontrado');
        }

        $hoje = Carbon::today();

        $statusVisivelIds = CacheService::statusIdsPorNomes('pi', ['Em Processo', 'Reenvio de Documentos', 'Concluído']);

        $piScopeCallback = function ($q) use ($genOp, $statusVisivelIds) {
            $q->where(function ($mainQ) use ($genOp) {
                $mainQ->whereHas('operacionais', function ($sq) use ($genOp) {
                    $sq->where('gen_op.id', $genOp->id);
                })->orWhere('operacional_id', $genOp->id);
            })->whereIn('status_id', $statusVisivelIds);
        };

        $piStats = Pi::select(
            DB::raw('COUNT(*) as total'),
            DB::raw('SUM(CASE WHEN concluido = 1 THEN 1 ELSE 0 END) as concluidas'),
            DB::raw('SUM(CASE WHEN concluido = 0 THEN 1 ELSE 0 END) as pendentes')
        )->where($piScopeCallback)->first();

        $minhasPis = (int) $piStats->total;
        $minhasPisConcluidas = (int) $piStats->concluidas;
        $minhasPisPendentes = (int) $piStats->pendentes;

        $piDocScopeCallback = function ($q) use ($genOp, $statusVisivelIds) {
            $q->whereIn('status_id', $statusVisivelIds)
                ->where(function ($mainQ) use ($genOp) {
                    $mainQ->whereHas('operacionais', function ($sq) use ($genOp) {
                        $sq->where('gen_op.id', $genOp->id);
                    })->orWhere('operacional_id', $genOp->id);
                });
        };

        $documentosPendentesValidacao = DocumentoEnviado::whereHas('piDocumentos.pi', $piDocScopeCallback)
            ->whereHas('status', function ($query) {
                $query->whereIn('nome', ['Pendente', 'Aguardando validação']);
            })
            ->count();

        $documentosValidadosHoje = DocumentoEnviado::whereHas('piDocumentos.pi', $piDocScopeCallback)
            ->whereHas('validacoes', function ($query) use ($hoje) {
                $query->whereDate('data_validacao', $hoje);
            })
            ->count();

        $pisRecentes = Pi::with(['cliente', 'fornecedor', 'status', 'gerencial.user'])
            ->where($piScopeCallback)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        $documentosPendentesLista = DocumentoEnviado::with(['fornecedor', 'tipoDocumento', 'piDocumentos.pi', 'status'])
            ->whereHas('piDocumentos.pi', $piDocScopeCallback)
            ->whereHas('status', function ($query) {
                $query->whereIn('nome', ['Pendente', 'Aguardando validação']);
            })
            ->orderBy('data_envio', 'asc')
            ->take(10)
            ->get();

        $pisPorStatus = Pi::select('status_id', DB::raw('count(*) as total'))
            ->with('status')
            ->where($piScopeCallback)
            ->groupBy('status_id')
            ->get();

        $documentosVencendo = DocumentoEnviado::with(['fornecedor', 'tipoDocumento'])
            ->where('valido', true)
            ->whereHas('piDocumentos.pi', $piDocScopeCallback)
            ->whereBetween('data_validade_documento', [$hoje, $hoje->copy()->addDays(30)])
            ->orderBy('data_validade_documento', 'asc')
            ->take(10)
            ->get();

        return view('dashboard', compact(
            'minhasPis',
            'minhasPisConcluidas',
            'minhasPisPendentes',
            'documentosPendentesValidacao',
            'documentosValidadosHoje',
            'pisRecentes',
            'documentosPendentesLista',
            'pisPorStatus',
            'documentosVencendo'
        ));
    }

    private function dashboardFornecedor($user)
    {
        $fornecedor = $user->fornecedor;

        if (! $fornecedor) {
            abort(403, 'Perfil fornecedor não encontrado');
        }

        $hoje = Carbon::today();
        $fId = $fornecedor->id;

        $docStats = DocumentoEnviado::select(
            DB::raw('COUNT(*) as enviados'),
            DB::raw('SUM(CASE WHEN documentos_enviados.valido = 1 THEN 1 ELSE 0 END) as validos'),
            DB::raw("SUM(CASE WHEN s.nome = 'Aprovado' AND documentos_enviados.valido = 1 THEN 1 ELSE 0 END) as aprovados"),
            DB::raw("SUM(CASE WHEN s.nome IN ('Reprovado', 'Aguardando Reenvio') THEN 1 ELSE 0 END) as recusados"),
            DB::raw("SUM(CASE WHEN s.nome = 'Pendente' AND documentos_enviados.valido = 1 THEN 1 ELSE 0 END) as pendentes"),
            DB::raw("SUM(CASE WHEN documentos_enviados.data_validade_documento IS NOT NULL AND documentos_enviados.data_validade_documento < ? AND s.nome NOT IN ('Reprovado', 'Aguardando Reenvio') THEN 1 ELSE 0 END) as invalidos")
        )
            ->join('status as s', 'documentos_enviados.status_id', '=', 's.id')
            ->where('documentos_enviados.fornecedor_id', $fId)
            ->whereNull('documentos_enviados.deleted_at')
            ->setBindings([$hoje, $fId])
            ->first();

        $documentosEnviados = (int) ($docStats->enviados ?? 0);
        $documentosValidos = (int) ($docStats->validos ?? 0);
        $documentosInvalidos = (int) ($docStats->invalidos ?? 0);
        $documentosAprovados = (int) ($docStats->aprovados ?? 0);
        $documentosRecusados = (int) ($docStats->recusados ?? 0);
        $documentosPendentes = (int) ($docStats->pendentes ?? 0);

        $documentosVencendo = DocumentoEnviado::with(['tipoDocumento', 'status'])
            ->where('fornecedor_id', $fId)
            ->where('valido', true)
            ->whereBetween('data_validade_documento', [$hoje, $hoje->copy()->addDays(30)])
            ->orderBy('data_validade_documento', 'asc')
            ->get();

        $documentosVencendoCount = $documentosVencendo->count();
        $documentosVencendo = $documentosVencendo->take(10);

        $documentosVencidos = DocumentoEnviado::with(['tipoDocumento', 'status'])
            ->where('fornecedor_id', $fId)
            ->whereNotNull('data_validade_documento')
            ->where('data_validade_documento', '<', $hoje)
            ->whereHas('status', function ($query) {
                $query->whereNotIn('nome', ['Reprovado', 'Aguardando Reenvio']);
            })
            ->orderBy('data_validade_documento', 'desc')
            ->get();

        $documentosVencidosCount = $documentosVencidos->count();
        $documentosVencidos = $documentosVencidos->take(10);

        $documentosPorStatus = DocumentoEnviado::select('status_id', DB::raw('count(*) as total'))
            ->with('status')
            ->where('fornecedor_id', $fId)
            ->where('valido', true)
            ->groupBy('status_id')
            ->get();

        $piStats = Pi::select(
            DB::raw('COUNT(*) as vinculados'),
            DB::raw('SUM(CASE WHEN concluido = 0 THEN 1 ELSE 0 END) as ativos')
        )->where('fornecedor_id', $fId)->first();

        $pisVinculados = (int) $piStats->vinculados;
        $pisAtivos = (int) $piStats->ativos;

        $statusPendente = CacheService::statusPorNome('documento', 'Pendente');

        $documentoPorPIPendentes = PiDocumento::with(['tipoDocumento', 'pi'])
            ->whereNull('documento_enviado_id')
            ->when($statusPendente, function ($q) use ($statusPendente) {
                $q->where('status_id', $statusPendente->id);
            })
            ->whereHas('pi', function ($q) use ($fId) {
                $q->where('fornecedor_id', $fId);
            })
            ->get();

        return view('dashboard', compact(
            'documentosEnviados',
            'documentosValidos',
            'documentosInvalidos',
            'documentosAprovados',
            'documentosRecusados',
            'documentosPendentes',
            'documentosVencendoCount',
            'documentosVencendo',
            'documentosVencidosCount',
            'documentosVencidos',
            'documentosPorStatus',
            'documentoPorPIPendentes',
            'pisVinculados',
            'pisAtivos'
        ));
    }

    private function dashboardRepresentante($user)
    {
        $repUsuario = $user->representanteUsuario;

        if (! $repUsuario) {
            abort(403, 'Perfil de representante não encontrado');
        }

        $hoje = Carbon::today();
        $fornecedorIds = $repUsuario->getFornecedorIds();
        $totalFornecedores = count($fornecedorIds);

        $fornecedoresVinculados = Fornecedor::whereIn('id', $fornecedorIds)
            ->with('user')
            ->get();

        $piStats = Pi::select(
            DB::raw('SUM(CASE WHEN concluido = 0 THEN 1 ELSE 0 END) as ativos'),
            DB::raw('SUM(CASE WHEN concluido = 1 THEN 1 ELSE 0 END) as concluidos')
        )->whereIn('fornecedor_id', $fornecedorIds)->first();

        $totalPisAtivos = (int) ($piStats->ativos ?? 0);
        $totalPisConcluidos = (int) ($piStats->concluidos ?? 0);

        $documentosPendentesEnvio = PiDocumento::whereNull('documento_enviado_id')
            ->whereHas('pi', function ($q) use ($fornecedorIds) {
                $q->whereIn('fornecedor_id', $fornecedorIds)
                    ->where('concluido', false);
            })
            ->count();

        $documentosAguardandoValidacao = DocumentoEnviado::whereIn('fornecedor_id', $fornecedorIds)
            ->whereHas('status', function ($q) {
                $q->whereIn('nome', ['Pendente', 'Aguardando validação']);
            })
            ->count();

        $documentosReprovados = DocumentoEnviado::whereIn('fornecedor_id', $fornecedorIds)
            ->whereHas('status', function ($q) {
                $q->whereIn('nome', ['Reprovado', 'Aguardando Reenvio']);
            })
            ->count();

        $documentosVencendoCount = DocumentoEnviado::whereIn('fornecedor_id', $fornecedorIds)
            ->where('valido', true)
            ->whereBetween('data_validade_documento', [$hoje, $hoje->copy()->addDays(30)])
            ->count();

        $pisRecentes = Pi::with(['cliente', 'fornecedor', 'status'])
            ->whereIn('fornecedor_id', $fornecedorIds)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        $pisPorFornecedor = Pi::select('fornecedor_id', DB::raw('count(*) as total'), DB::raw('SUM(CASE WHEN concluido = 0 THEN 1 ELSE 0 END) as ativos'))
            ->whereIn('fornecedor_id', $fornecedorIds)
            ->groupBy('fornecedor_id')
            ->get()
            ->keyBy('fornecedor_id');

        $vinculosPendentes = RepresentanteFornecedor::where('representante_usuario_id', $repUsuario->id)
            ->where('status_vinculo', 'pendente')
            ->where('ativo', true)
            ->count();

        return view('dashboard', compact(
            'totalFornecedores',
            'fornecedoresVinculados',
            'totalPisAtivos',
            'totalPisConcluidos',
            'documentosPendentesEnvio',
            'documentosAguardandoValidacao',
            'documentosReprovados',
            'documentosVencendoCount',
            'pisRecentes',
            'pisPorFornecedor',
            'vinculosPendentes'
        ));
    }
}