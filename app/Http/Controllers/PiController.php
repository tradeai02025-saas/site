<?php

namespace App\Http\Controllers;

use App\Mail\NotificacaoConclusaoPiMail;
use App\Models\{Arquivo, Cliente, ClienteDocumentoRequerido, Conversa, ConversaParticipante, DocumentoEnviado, Fornecedor, GenOp, Mensagem, Pi, PiAtribuicao, PiDocumento, RepresentanteUsuario, Status, TipoDocumento, User, ValidacaoDocumento};
use App\Services\{CacheService, MailRotator};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Cache, DB, Log};
use Illuminate\Validation\Rule;

class PiController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Pi::with(['cliente', 'fornecedor', 'gerencial.user', 'status', 'valores', 'operacionais', 'operacionalPrincipalAtribuido'])->withTrashed();
        $query->withCount(['piDocumentos as documentos_atendidos_count' => function ($q) {
            $q->whereHas('documentoEnviado', function ($sq) {
                $sq->whereHas('status', function ($ssq) {
                    $ssq->where('nome', 'Aprovado');
                });
            });
        }]);

        if ($user->hasRole('fornecedor')) {
            if (! $user->fornecedor) {
                abort(403, 'Fornecedor não encontrado.');
            }
            $query->withoutTrashed();
            $query->where('fornecedor_id', $user->fornecedor->id);

            $query->withCount([
                'piDocumentos as documentos_requeridos_count',

                'piDocumentos as documentos_atendidos_count' => function ($q) {
                    $q->whereHas('documentoEnviado', function ($sq) {
                        $sq->whereHas('status', function ($ssq) {
                            $ssq->where('nome', 'Aprovado');
                        });
                    });
                },

                'piDocumentos as docs_reprovados_count' => function ($q) {
                    $q->whereHas('documentoEnviado', function ($sq) {
                        $sq->whereHas('status', function ($ssq) {
                            $ssq->whereIn('nome', ['Reprovado', 'Aguardando Reenvio']);
                        });
                    });
                },

                'piDocumentos as docs_pendentes_validacao_count' => function ($q) {
                    $q->whereHas('documentoEnviado', function ($sq) {
                        $sq->whereHas('status', function ($ssq) {
                            $ssq->whereIn('nome', ['Pendente', 'Aguardando validação']);
                        });
                    });
                },

                'piDocumentos as docs_nao_enviados_count' => function ($q) {
                    $q->whereNull('documento_enviado_id');
                },

                'conversas',
            ]);
        }

        if ($user->hasRole('operacional')) {
            if (! $user->genOp) {
                abort(403, 'Usuário operacional sem perfil GenOp vinculado.');
            }

            $query->withoutTrashed();

            $query->where(function ($mainQ) use ($user) {
                $mainQ->whereHas('atribuicoesOperacionais', function ($q) use ($user) {
                    $q->where('operacional_id', $user->genOp->id)
                        ->where('ativo', true)
                        ->whereNull('deleted_at');
                })
                    ->orWhere('operacional_id', $user->genOp->id);
            });

            $statusVisivelOp = CacheService::statusIdsPorNomes('pi', ['Em Processo', 'Reenvio de Documentos', 'Concluído', 'Pago']);

            if (! empty($statusVisivelOp)) {
                $query->whereIn('status_id', $statusVisivelOp);
            }

            $query->withCount([
                'piDocumentos as docs_para_validar_count' => function ($q) {
                    $q->whereNotNull('documento_enviado_id')
                        ->whereHas('documentoEnviado', function ($sq) {
                            $sq->whereHas('status', function ($ssq) {
                                $ssq->whereIn('nome', ['Pendente', 'Aguardando validação']);
                            });
                        });
                },
                'piDocumentos as docs_reprovados_count' => function ($q) {
                    $q->whereNotNull('documento_enviado_id')
                        ->whereHas('documentoEnviado', function ($sq) {
                            $sq->whereHas('status', function ($ssq) {
                                $ssq->whereIn('nome', ['Reprovado', 'Aguardando Reenvio']);
                            });
                        });
                },
                'piDocumentos as docs_aprovados_count' => function ($q) {
                    $q->whereNotNull('documento_enviado_id')
                        ->whereHas('documentoEnviado', function ($sq) {
                            $sq->whereHas('status', function ($ssq) {
                                $ssq->where('nome', 'Aprovado');
                            });
                        });
                },
                'piDocumentos as docs_total_count',
            ]);
        }

        if ($user->hasRole('representante')) {
            $repUsuario = $user->representanteUsuario;
            if (! $repUsuario) {
                abort(403, 'Perfil de representante não encontrado.');
            }

            $fornecedorIds = $repUsuario->getFornecedorIds();

            $query->withoutTrashed();
            $query->whereIn('fornecedor_id', $fornecedorIds);

            $query->withCount([
                'piDocumentos as documentos_requeridos_count',

                'piDocumentos as documentos_atendidos_count' => function ($q) {
                    $q->whereHas('documentoEnviado', function ($sq) {
                        $sq->whereHas('status', function ($ssq) {
                            $ssq->where('nome', 'Aprovado');
                        });
                    });
                },

                'piDocumentos as docs_reprovados_count' => function ($q) {
                    $q->whereHas('documentoEnviado', function ($sq) {
                        $sq->whereHas('status', function ($ssq) {
                            $ssq->whereIn('nome', ['Reprovado', 'Aguardando Reenvio']);
                        });
                    });
                },

                'piDocumentos as docs_pendentes_validacao_count' => function ($q) {
                    $q->whereHas('documentoEnviado', function ($sq) {
                        $sq->whereHas('status', function ($ssq) {
                            $ssq->whereIn('nome', ['Pendente', 'Aguardando validação']);
                        });
                    });
                },

                'piDocumentos as docs_nao_enviados_count' => function ($q) {
                    $q->whereNull('documento_enviado_id');
                },

                'conversas',
            ]);
        }

        if ($user->hasRole('gerencial') && $user->genOp && $request->filled('apenas_meus')) {
            $query->where('gerencial_id', $user->genOp->id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('numero_pi', 'like', "%{$search}%")
                    ->orWhere('observacoes', 'like', "%{$search}%")
                    ->orWhere('periodo', 'like', "%{$search}%")
                    ->orWhere('PIT_n', 'like', "%{$search}%")
                    ->orWhere('plano_n', 'like', "%{$search}%")
                    ->orWhere('planilha_n', 'like', "%{$search}%")
                    ->orWhereHas('cliente', function ($sq) use ($search) {
                        $sq->where('razao_social', 'like', "%{$search}%")
                            ->orWhere('nome_fantasia', 'like', "%{$search}%");
                    })
                    ->orWhereHas('fornecedor', function ($sq) use ($search) {
                        $sq->where('razao_social', 'like', "%{$search}%")
                            ->orWhere('nome_fantasia', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status_id')) {
            $query->where('status_id', $request->status_id);
        }

        if ($request->filled('grau_prioridade')) {
            $query->where('grau_prioridade', $request->grau_prioridade);
        }

        if ($request->filled('data_inicio')) {
            $query->whereDate('data_cadastro', '>=', $request->data_inicio);
        }

        if ($request->filled('data_fim')) {
            $query->whereDate('data_cadastro', '<=', $request->data_fim);
        }

        if ($user->hasRole(['fornecedor', 'operacional', 'representante'])) {
            if ($request->filled('status_filtro')) {
                if ($request->status_filtro === 'concluidos') {
                    $query->where('concluido', true);
                } elseif ($request->status_filtro === 'andamento') {
                    $query->where('concluido', false);
                }
            }
        } elseif ($request->filled('status_filtro')) {
            if ($request->status_filtro === 'inativos') {
                $query->onlyTrashed();
            } elseif ($request->status_filtro === 'concluidos') {
                $query->withoutTrashed()->where('concluido', true);
            } elseif ($request->status_filtro === 'andamento') {
                $query->withoutTrashed()->where('concluido', false);
            } else {
                $query->withoutTrashed();
            }
        } else {
            $query->withoutTrashed();
        }

        if ($user->hasRole('operacional')) {
            $statusOrdem = DB::raw("CASE 
                WHEN status.nome = 'Em Processo' THEN 1
                WHEN status.nome = 'Reenvio de Documentos' THEN 2
                WHEN status.nome = 'Concluído' THEN 3
                WHEN status.nome = 'Pago' THEN 4
                ELSE 5
            END");

            $pis = $query->leftJoin('status', 'pis.status_id', '=', 'status.id')
                ->select('pis.*')
                ->orderBy($statusOrdem)
                ->orderBy('pis.grau_prioridade', 'desc')
                ->orderBy('pis.created_at', 'desc')
                ->paginate(15)
                ->withQueryString();
        } else {
            $pis = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();
        }

        $pis->loadMissing(['piDocumentos.documentoEnviado.status', 'piDocumentos.documentoEnviado.validacoes']);

        $statusAprovadoId = CacheService::statusId('documento', 'Aprovado');

        $pis->getCollection()->transform(function ($pi) use ($statusAprovadoId) {
            $docsComEnvio = $pi->piDocumentos->filter(fn($pd) => $pd->documentoEnviado);
            $totalDocs = $pi->piDocumentos->count();
            $totalEnviados = $docsComEnvio->count();

            $pi->total_docs = $totalDocs;
            $pi->total_docs_enviados = $totalEnviados;

            if ($totalDocs > 0 && $totalEnviados === $totalDocs) {
                $pi->data_todos_enviados = $docsComEnvio
                    ->map(fn($pd) => $pd->documentoEnviado->data_envio)
                    ->filter()
                    ->max();
            } else {
                $pi->data_todos_enviados = null;
            }

            $pi->data_primeiro_envio = $docsComEnvio->isNotEmpty()
                ? $docsComEnvio->map(fn($pd) => $pd->documentoEnviado->data_envio)->filter()->filter(fn($d) => $d >= $pi->created_at)->min()
                    ?? $pi->created_at
                : null;

            $pi->data_docs_aprovados = null;
            if ($statusAprovadoId && $docsComEnvio->isNotEmpty() && $totalEnviados === $totalDocs) {
                $todosAprovados = $docsComEnvio->every(fn($pd) => $pd->documentoEnviado->status_id === $statusAprovadoId);
                if ($todosAprovados) {
                    $pi->data_docs_aprovados = $docsComEnvio
                        ->flatMap(fn($pd) => $pd->documentoEnviado->validacoes)
                        ->filter(fn($v) => $v->status_id === $statusAprovadoId)
                        ->max('data_validacao');
                }
            }

            return $pi;
        });

        $statusList = CacheService::statusPorTipo('pi');

        $genOpList = GenOp::with('user')
            ->whereHas('user', function ($q) {
                $q->whereNull('deleted_at')
                    ->whereHas('roles', function ($rq) {
                        $rq->whereIn('name', ['gerencial', 'operacional']);
                    });
            })
            ->orderBy('nome_completo')
            ->get();

        if ($user->hasRole(['admin', 'gerencial'])) {
            $totalPis = Pi::count();
            $pisEmAndamento = Pi::where('concluido', false)->count();
            $pisConcluidos = Pi::where('concluido', true)->count();
            $pisInativos = Pi::onlyTrashed()->count();

            return view('pis.index', compact('pis', 'genOpList', 'statusList', 'totalPis', 'pisEmAndamento', 'pisConcluidos', 'pisInativos'));
        } elseif ($user->hasRole('operacional')) {
            $statusVisivelOpNomes = ['Em Processo', 'Reenvio de Documentos', 'Concluído', 'Pago'];
            $statusVisivelOpIds = CacheService::statusIdsPorNomes('pi', $statusVisivelOpNomes);

            $meusPis = Pi::whereHas('atribuicoesOperacionais', function ($q) use ($user) {
                $q->where('operacional_id', $user->genOp->id)
                    ->where('ativo', true);
            })->whereIn('status_id', $statusVisivelOpIds)->count();

            $docsParaValidar = PiDocumento::whereHas('pi', function ($q) use ($user, $statusVisivelOpIds) {
                $q->whereIn('status_id', $statusVisivelOpIds)
                    ->where('concluido', false)
                    ->whereHas('atribuicoesOperacionais', function ($sq) use ($user) {
                        $sq->where('operacional_id', $user->genOp->id)
                            ->where('ativo', true);
                    });
            })->whereNotNull('documento_enviado_id')
                ->whereHas('documentoEnviado', function ($q) {
                    $q->whereHas('status', function ($sq) {
                        $sq->whereIn('nome', ['Pendente', 'Aguardando validação']);
                    });
                })->count();

            $docsComProblema = PiDocumento::whereHas('pi', function ($q) use ($user, $statusVisivelOpIds) {
                $q->whereIn('status_id', $statusVisivelOpIds)
                    ->where('concluido', false)
                    ->whereHas('atribuicoesOperacionais', function ($sq) use ($user) {
                        $sq->where('operacional_id', $user->genOp->id)
                            ->where('ativo', true);
                    });
            })->whereNotNull('documento_enviado_id')
                ->whereHas('documentoEnviado', function ($q) {
                    $q->whereHas('status', function ($sq) {
                        $sq->whereIn('nome', ['Reprovado', 'Aguardando Reenvio']);
                    });
                })->count();

            $mensagensNaoLidas = $user->totalMensagensNaoLidas();

            $statusList = CacheService::statusPorTipo('pi')->whereIn('nome', $statusVisivelOpNomes)->values();

            return view('pis.index', compact('pis', 'genOpList', 'statusList', 'meusPis', 'docsParaValidar', 'docsComProblema', 'mensagensNaoLidas'));
        } elseif ($user->hasRole('fornecedor')) {
            $meusPis = Pi::where('fornecedor_id', $user->fornecedor->id)->count();

            $docsRequeridos = PIDocumento::whereIn('pi_id', function ($q) use ($user) {
                $q->select('id')
                    ->from('pis')
                    ->where('fornecedor_id', $user->fornecedor->id)
                    ->whereNull('deleted_at');
            })
                ->count();

            $docsAtendidos = PiDocumento::whereNotNull('documento_enviado_id')
                ->whereHas('documentoEnviado', function ($q) {
                    $q->whereHas('status', function ($sq) {
                        $sq->where('nome', 'Aprovado')
                            ->where('tipo', 'documento');
                    });
                })
                ->whereHas('pi', function ($q) use ($user) {
                    $q->where('fornecedor_id', $user->fornecedor->id)
                        ->whereNull('deleted_at');
                })
                ->count();

            $conversasAbertas = Conversa::whereHas('pi', function ($q) use ($user) {
                $q->where('fornecedor_id', $user->fornecedor->id)
                    ->whereNull('deleted_at');
            })
                ->where('status_conversa', 'aberta')
                ->count();

            return view('pis.index', compact('pis', 'genOpList', 'meusPis', 'docsRequeridos', 'docsAtendidos', 'conversasAbertas', 'statusList'));
        }

        if ($user->hasRole('representante')) {
            $repUsuario = $user->representanteUsuario;
            $fornecedorIds = $repUsuario ? $repUsuario->getFornecedorIds() : [];

            $meusPis = Pi::whereIn('fornecedor_id', $fornecedorIds)->count();

            $docsRequeridos = PiDocumento::whereIn('pi_id', function ($q) use ($fornecedorIds) {
                $q->select('id')
                    ->from('pis')
                    ->whereIn('fornecedor_id', $fornecedorIds)
                    ->whereNull('deleted_at');
            })->count();

            $docsAtendidos = PiDocumento::whereNotNull('documento_enviado_id')
                ->whereHas('documentoEnviado', function ($q) {
                    $q->whereHas('status', function ($sq) {
                        $sq->where('nome', 'Aprovado')
                            ->where('tipo', 'documento');
                    });
                })
                ->whereHas('pi', function ($q) use ($fornecedorIds) {
                    $q->whereIn('fornecedor_id', $fornecedorIds)
                        ->whereNull('deleted_at');
                })
                ->count();

            $conversasAbertas = 0;

            return view('pis.index', compact('pis', 'genOpList', 'meusPis', 'docsRequeridos', 'docsAtendidos', 'conversasAbertas', 'statusList'));
        }

        abort(403, 'Acesso negado.');
    }

    public function create()
    {
        $user = Auth::user();

        $clientes = Cliente::whereNull('deleted_at')->orderBy('razao_social')->get();

        $fornecedores = collect();

        if ($user->hasRole('fornecedor')) {
            $fornecedores = collect();
        } elseif ($user->hasRole('representante') && $user->representanteUsuario) {
            $fornecedores = $user->representanteUsuario->fornecedores()
                ->whereNull('fornecedores.deleted_at')
                ->orderBy('razao_social')
                ->get();
        } else {
            $fornecedores = Fornecedor::with('user')
                ->whereNull('deleted_at')
                ->orderBy('razao_social')
                ->get();
        }

        $operacionais = GenOp::with('user')
            ->whereHas('user', function ($q) {
                $q->whereNull('deleted_at')
                    ->whereHas('roles', function ($rq) {
                        $rq->where('name', 'operacional');
                    });
            })
            ->orderBy('nome_completo')
            ->get();

        $statusList = CacheService::statusPorTipo('pi');

        $tipoVeiculos = CacheService::tiposVeiculoAtivos();

        return view('pis.form', compact(
            'clientes',
            'fornecedores',
            'operacionais',
            'statusList',
            'tipoVeiculos'
        ));
    }

    public function detalhes(Pi $pi)
    {
        $user = Auth::user();

        if ($user->hasRole('fornecedor')) {
            if (! $user->fornecedor || $pi->fornecedor_id !== $user->fornecedor->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para visualizar este PI.',
                ], 403);
            }
        }

        if ($user->hasRole('operacional')) {
            if (! $user->genOp || ! $pi->operacionalTemAcesso($user->genOp->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para visualizar este PI.',
                ], 403);
            }
        }

        if ($user->hasRole('representante')) {
            if (! $this->representanteTemAcessoAoPi($user, $pi)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para visualizar este PI.',
                ], 403);
            }
        }

        try {
            $pi->load([
                'cliente',
                'fornecedor.user',
                'gerencial.user',
                'operacionais.user',
                'status',
                'tipoVeiculo',
                'valores',
                'piDocumentos.tipoDocumento',
                'piDocumentos.documentoEnviado.arquivos',
                'piDocumentos.documentoEnviado.status',
                'piDocumentos.documentoEnviado.validacoes' => function ($query) {
                    $query->with(['operacional.user', 'status'])->orderBy('created_at', 'desc');
                },
                'piDocumentos.status',
                'atribuicoesOperacionais.operacional.user',
                'conversas' => function ($query) {
                    $query->with(['ultimaMensagem', 'participantesAtivos'])
                        ->withCount('mensagens');
                },
            ]);

            $conversasComMensagensNaoLidas = 0;
            if ($user->hasRole('fornecedor') && $user->fornecedor) {
                foreach ($pi->conversas as $conversa) {
                    $naoLidas = $conversa->mensagens()
                        ->where('lida', false)
                        ->where(function ($q) use ($user) {
                            $q->where('remetente_type', '!=', get_class($user->fornecedor))
                                ->orWhere('remetente_id', '!=', $user->fornecedor->id);
                        })
                        ->count();
                    if ($naoLidas > 0) {
                        $conversasComMensagensNaoLidas++;
                    }
                }
            }

            $dataPrimeiroEnvio = null;
            $dataTodosEnviados = null;
            $dataUltimaAprovacao = null;
            $totalDocs = $pi->piDocumentos->count();

            $documentosComEnvio = $pi->piDocumentos->filter(fn($pd) => $pd->documentoEnviado);
            $totalEnviados = $documentosComEnvio->count();

            if ($documentosComEnvio->isNotEmpty()) {
                $dataPrimeiroEnvio = $documentosComEnvio
                    ->map(fn($pd) => $pd->documentoEnviado->data_envio)
                    ->filter()
                    ->filter(fn($d) => $d >= $pi->created_at)
                    ->min() ?? $pi->created_at;

                if ($totalDocs > 0 && $totalEnviados === $totalDocs) {
                    $dataTodosEnviados = $documentosComEnvio
                        ->map(fn($pd) => $pd->documentoEnviado->data_envio)
                        ->filter()
                        ->max();
                }

                $statusAprovado = CacheService::statusPorNome('documento', 'Aprovado');

                if ($statusAprovado) {
                    $todosAprovados = $documentosComEnvio->every(
                        fn($pd) => $pd->documentoEnviado->status_id === $statusAprovado->id
                    );

                    if ($todosAprovados && $totalEnviados === $totalDocs) {
                        $dataUltimaAprovacao = $documentosComEnvio
                            ->flatMap(fn($pd) => $pd->documentoEnviado->validacoes)
                            ->filter(fn($v) => $v->status_id === $statusAprovado->id)
                            ->max('data_validacao');
                    }
                }
            }

            return response()->json([
                'success' => true,
                'pi' => $pi,
                'conversas_com_mensagens_nao_lidas' => $conversasComMensagensNaoLidas,
                'data_primeiro_envio' => $dataPrimeiroEnvio,
                'data_todos_enviados' => $dataTodosEnviados,
                'data_ultima_aprovacao' => $dataUltimaAprovacao,
                'total_docs' => $totalDocs,
                'total_docs_enviados' => $totalEnviados,
            ]);
        } catch (\Exception $e) {
            Log::channel('pis')->error('Erro ao carregar detalhes do PI', [
                'pi_id' => $pi->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar os dados do PI: '.$e->getMessage(),
            ], 500);
        }
    }

    public function edit(Pi $pi)
    {
        $user = Auth::user();

        if ($user->hasRole('operacional')) {
            $temAcesso = $user->genOp && $pi->operacionalTemAcesso($user->genOp->id);
            if (! $temAcesso) {
                abort(403, 'Você não tem permissão para editar este PI.');
            }
        }

        if ($user->hasRole('fornecedor')) {
            if (! $user->fornecedor || $pi->fornecedor_id !== $user->fornecedor->id) {
                abort(403, 'Você não tem permissão para editar este PI.');
            }
        }

        if ($user->hasRole('representante')) {
            if (! $this->representanteTemAcessoAoPi($user, $pi)) {
                abort(403, 'Você não tem permissão para acessar este PI.');
            }
        }

        $pi->load(['piDocumentos', 'valores', 'operacionais', 'cliente', 'tipoVeiculo']);

        $periodoInput = $this->periodoParaInput($pi->periodo);

        $clientes = Cliente::whereNull('deleted_at')->orderBy('razao_social')->get();

        if ($user->hasRole('fornecedor')) {
            $fornecedores = $user->fornecedor ? collect([$user->fornecedor->load('user')]) : collect();
        } elseif ($user->hasRole('representante') && $user->representanteUsuario) {
            $fornecedores = $user->representanteUsuario->fornecedores()
                ->whereNull('fornecedores.deleted_at')
                ->orderBy('razao_social')
                ->get();
        } else {
            $fornecedores = Fornecedor::with('user')->whereNull('deleted_at')->orderBy('razao_social')->get();
        }

        $operacionais = GenOp::with('user')->whereHas('user', function ($q) {
            $q->whereNull('deleted_at')->whereHas('roles', function ($rq) {
                $rq->where('name', 'operacional');
            });
        })->orderBy('nome_completo')->get();
        $statusList = CacheService::statusPorTipo('pi');
        $tipoVeiculos = CacheService::tiposVeiculoAtivos();

        return view('pis.form', compact('pi', 'clientes', 'fornecedores', 'operacionais', 'statusList', 'periodoInput', 'tipoVeiculos'));
    }

    public function destroy(Request $request, Pi $pi)
    {
        $user = Auth::user();

        if ($user->hasRole('fornecedor')) {
            if (!$user->fornecedor || $pi->fornecedor_id !== $user->fornecedor->id) {
                abort(403, 'Você não tem permissão para excluir este PI.');
            }
            if ($pi->concluido) {
                return back()->with('error', 'PIs concluídos não podem ser excluídos pelo fornecedor.');
            }
        }

        if ($user->hasRole('representante')) {
            if (!$this->representanteTemAcessoAoPi($user, $pi)) {
                abort(403, 'Você não tem permissão para excluir este PI.');
            }
            if ($pi->concluido) {
                return back()->with('error', 'PIs concluídos não podem ser excluídos pelo representante.');
            }
        }

        if ($user->hasRole('operacional')) {
            abort(403, 'Operacionais não podem excluir PIs.');
        }

        if ($user->hasAnyRole(['fornecedor', 'representante'])) {
            DB::beginTransaction();
            try {
                $temDocsEnviados = $pi->piDocumentos()->withTrashed()
                    ->whereNotNull('documento_enviado_id')
                    ->exists();

                $temConversas = $pi->conversas()
                    ->whereNull('deleted_at')
                    ->exists();

                if ($temDocsEnviados || $temConversas) {
                    $pi->delete();
                    DB::commit();

                    return redirect()->route('pis.index')->with('success', 'PI inativado com sucesso.');
                }

                $pi->piDocumentos()->withTrashed()->forceDelete();
                $pi->atribuicoesOperacionais()->withTrashed()->forceDelete();
                $pi->valores()->withTrashed()->forceDelete();
                if ($pi->file_path) {
                    $fullPath = $this->getPrivateFilePath($pi->file_path);
                    if (file_exists($fullPath)) {
                        unlink($fullPath);
                    }
                }
                $pi->forceDelete();
                DB::commit();

                return redirect()->route('pis.index')->with('success', 'PI excluído com sucesso.');
            } catch (\Exception $e) {
                DB::rollBack();
                Log::channel('pis')->error('Erro ao excluir PI (fornecedor/representante)', [
                    'pi_id' => $pi->id,
                    'user_id' => $user->id,
                    'error_message' => $e->getMessage(),
                ]);

                return back()->with('error', 'Erro ao excluir PI: '.$e->getMessage());
            }
        }

        $forceDelete = $request->boolean('force', false);

        if ($forceDelete && ! $user->hasRole('admin')) {
            return back()->with('error', 'Apenas administradores podem excluir permanentemente.');
        }

        if ($user->hasAnyRole(['admin', 'gerencial']) && ! $forceDelete) {
            DB::beginTransaction();
            try {
                $pi->delete();

                DB::commit();

                return redirect()->route('pis.index')->with('success', 'PI inativado com sucesso! Pode ser restaurado se necessário.');
            } catch (\Exception $e) {
                DB::rollBack();

                Log::channel('pis')->error('Erro ao inativar PI', [
                    'pi_id' => $pi->id,
                    'error_message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return back()->with('error', 'Erro ao inativar PI: '.$e->getMessage());
            }
        }

        DB::beginTransaction();
        try {
            $pi->load(['conversas.mensagens.anexos', 'conversas.participantes', 'conversas.transferencias']);

            foreach ($pi->conversas as $conversa) {
                foreach ($conversa->mensagens as $mensagem) {
                    $mensagem->anexos()->forceDelete();
                    $mensagem->forceDelete();
                }

                $conversa->participantes()->forceDelete();
                $conversa->transferencias()->forceDelete();
                $conversa->forceDelete();
            }

            foreach ($pi->piDocumentos as $piDoc) {
                if ($piDoc->documentoEnviado) {
                    $documentoEnviado = $piDoc->documentoEnviado;

                    $outrosVinculos = PiDocumento::where('documento_enviado_id', $documentoEnviado->id)
                        ->where('pi_id', '!=', $pi->id)
                        ->whereNull('deleted_at')
                        ->count();

                    if ($outrosVinculos === 0) {
                        $documentoEnviado->validacoes()->forceDelete();
                        foreach ($documentoEnviado->arquivos as $arquivo) {
                            $caminhos = [
                                storage_path('app/private/' . $arquivo->file_path),
                                storage_path('app/' . $arquivo->file_path),
                            ];
                            foreach ($caminhos as $caminho) {
                                if (file_exists($caminho)) {
                                    unlink($caminho);
                                    break;
                                }
                            }
                        }
                        $documentoEnviado->arquivos()->forceDelete();
                        $documentoEnviado->forceDelete();
                    }
                }

                $piDoc->forceDelete();
            }

            $pi->atribuicoesOperacionais()->forceDelete();

            if ($pi->valores) {
                $pi->valores->forceDelete();
            }

            $pi->forceDelete();

            DB::commit();

            return redirect()->route('pis.index')->with('success', 'PI excluído permanentemente com sucesso!');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::channel('pis')->error('Erro ao excluir PI', [
                'pi_id' => $pi->id,
                'error_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Erro ao excluir PI: '.$e->getMessage());
        }
    }

    private function copiarOperacionaisDoCliente(Pi $pi)
    {
        if (! $pi->cliente_id) {
            Log::channel('pis')->warning('PI criado sem cliente_id, impossível copiar operacionais.', [
                'pi_id' => $pi->id,
            ]);

            return false;
        }

        $operacionalPrincipal = PiAtribuicao::where('cliente_id', $pi->cliente_id)
            ->whereNull('pi_id')
            ->where('ativo', true)
            ->where('principal', true)
            ->whereNull('deleted_at')
            ->whereHas('operacional', function ($q) {
                $q->whereNull('deleted_at')
                    ->whereHas('user', function ($uq) {
                        $uq->whereNull('deleted_at')
                            ->whereHas('roles', function ($rq) {
                                $rq->where('name', 'operacional');
                            });
                    });
            })
            ->first();

        if (! $operacionalPrincipal) {
            $operacionalPrincipal = PiAtribuicao::where('cliente_id', $pi->cliente_id)
                ->whereNull('pi_id')
                ->where('ativo', true)
                ->whereNull('deleted_at')
                ->whereHas('operacional', function ($q) {
                    $q->whereNull('deleted_at')
                        ->whereHas('user', function ($uq) {
                            $uq->whereNull('deleted_at')
                                ->whereHas('roles', function ($rq) {
                                    $rq->where('name', 'operacional');
                                });
                        });
                })
                ->orderBy('created_at', 'asc')
                ->first();
        }

        if (! $operacionalPrincipal) {
            Log::channel('pis')->warning('Cliente não possui operacionais ativos vinculados.', [
                'pi_id' => $pi->id,
                'cliente_id' => $pi->cliente_id,
            ]);

            return false;
        }

        try {
            $atribuicaoExistente = PiAtribuicao::withTrashed()
                ->where('pi_id', $pi->id)
                ->where('operacional_id', $operacionalPrincipal->operacional_id)
                ->first();

            if ($atribuicaoExistente) {
                if ($atribuicaoExistente->trashed()) {
                    $atribuicaoExistente->restore();
                }
                $atribuicaoExistente->update([
                    'cliente_id' => $pi->cliente_id,
                    'data_atribuicao' => now(),
                    'ativo' => true,
                    'principal' => true,
                    'data_desatribuicao' => null,
                ]);
            } else {
                PiAtribuicao::create([
                    'pi_id' => $pi->id,
                    'cliente_id' => $pi->cliente_id,
                    'operacional_id' => $operacionalPrincipal->operacional_id,
                    'data_atribuicao' => now(),
                    'ativo' => true,
                    'principal' => true,
                ]);
            }

            $pi->update(['operacional_id' => $operacionalPrincipal->operacional_id]);

            return true;

        } catch (\Exception $e) {
            Log::channel('pis')->error('Erro ao criar atribuição de operacional para PI.', [
                'pi_id' => $pi->id,
                'operacional_id' => $operacionalPrincipal->operacional_id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function atribuirResponsavelCustom(Pi $pi, $responsavelId, $tipo)
    {
        $operacional = GenOp::whereNull('deleted_at')
            ->whereHas('user', function ($q) use ($tipo) {
                $q->whereNull('deleted_at')
                    ->whereHas('roles', function ($rq) use ($tipo) {
                        $rq->where('name', $tipo);
                    });
            })
            ->find($responsavelId);

        if (! $operacional) {
            Log::channel('pis')->error('Responsável selecionado não encontrado, inativo ou sem a role adequada.', [
                'pi_id' => $pi->id,
                'responsavel_id' => $responsavelId,
                'tipo' => $tipo,
            ]);

            return false;
        }

        $atribuicaoExistente = PiAtribuicao::withTrashed()
            ->where('pi_id', $pi->id)
            ->where('operacional_id', $responsavelId)
            ->first();

        if ($atribuicaoExistente) {
            if ($atribuicaoExistente->trashed()) {
                $atribuicaoExistente->restore();
            }
            $atribuicaoExistente->update([
                'cliente_id' => $pi->cliente_id,
                'data_atribuicao' => now(),
                'ativo' => true,
                'principal' => true,
            ]);
        } else {
            PiAtribuicao::create([
                'pi_id' => $pi->id,
                'cliente_id' => $pi->cliente_id,
                'operacional_id' => $responsavelId,
                'data_atribuicao' => now(),
                'ativo' => true,
                'principal' => true,
            ]);
        }

        $atribuicaoConfirmada = PiAtribuicao::where('pi_id', $pi->id)
            ->where('operacional_id', $responsavelId)
            ->where('ativo', true)
            ->whereNull('deleted_at')
            ->first();

        if (! $atribuicaoConfirmada) {
            Log::channel('pis')->error('Falha ao criar atribuição customizada para PI.', [
                'pi_id' => $pi->id,
                'responsavel_id' => $responsavelId,
                'tipo' => $tipo,
            ]);

            return false;
        }

        $pi->update(['operacional_id' => $responsavelId]);

        return true;
    }

    public function sugerirOperacional(Request $request)
    {
        $clienteId = $request->input('cliente_id');

        if (! $clienteId) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente não informado',
            ]);
        }

        try {
            $operacionalPrincipal = PiAtribuicao::where('cliente_id', $clienteId)
                ->whereNull('pi_id')
                ->where('ativo', true)
                ->where('principal', true)
                ->with('operacional.user')
                ->first();

            if (! $operacionalPrincipal) {
                $operacionalPrincipal = PiAtribuicao::where('cliente_id', $clienteId)
                    ->whereNull('pi_id')
                    ->where('ativo', true)
                    ->with('operacional.user')
                    ->first();
            }

            $operacionaisVinculadosIds = PiAtribuicao::where('cliente_id', $clienteId)
                ->whereNull('pi_id')
                ->where('ativo', true)
                ->pluck('operacional_id')
                ->toArray();

            $todosOperacionais = GenOp::with('user')
                ->whereNull('deleted_at')
                ->whereHas('user', function ($q) {
                    $q->whereNull('deleted_at')
                        ->whereHas('roles', function ($rq) {
                            $rq->where('name', 'operacional');
                        });
                })
                ->withCount(['atribuicoesPis as total_pis_ativos' => function ($q) {
                    $q->where('ativo', true);
                }])
                ->orderBy('nome_completo')
                ->get();

            $todosFormatados = $todosOperacionais->map(function ($op) use ($operacionaisVinculadosIds) {
                return [
                    'id' => $op->id,
                    'nome' => $op->nome_completo,
                    'email' => $op->user->email,
                    'total_pis' => $op->total_pis_ativos,
                    'vinculado_cliente' => in_array($op->id, $operacionaisVinculadosIds),
                ];
            });

            $sugerido = null;
            if ($operacionalPrincipal && $operacionalPrincipal->operacional) {
                $totalPis = PiAtribuicao::where('operacional_id', $operacionalPrincipal->operacional_id)
                    ->whereNotNull('pi_id')
                    ->where('ativo', true)
                    ->count();

                $sugerido = [
                    'id' => $operacionalPrincipal->operacional->id,
                    'nome' => $operacionalPrincipal->operacional->nome_completo,
                    'email' => $operacionalPrincipal->operacional->user->email,
                    'total_pis' => $totalPis,
                    'principal' => true,
                    'vinculado_cliente' => true,
                ];
            }

            if (! $sugerido) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cliente não possui operacionais cadastrados',
                    'todos_operacionais' => $todosFormatados->values()->all(),
                ]);
            }

            return response()->json([
                'success' => true,
                'sugerido' => $sugerido,
                'todos_operacionais' => $todosFormatados->values()->all(),
            ]);

        } catch (\Exception $e) {
            Log::channel('pis')->error('Erro ao sugerir operacional', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erro ao sugerir operacional: '.$e->getMessage(),
            ], 500);
        }
    }

    public function getGerenciaisDisponiveis()
    {
        try {
            $gerenciais = GenOp::with('user')
                ->whereHas('user', function ($q) {
                    $q->whereNull('deleted_at')
                        ->whereHas('roles', function ($rq) {
                            $rq->where('name', 'gerencial');
                        });
                })
                ->withCount(['pisComoGerencial as total_pis' => function ($q) {
                    $q->where('concluido', false);
                }])
                ->orderBy('nome_completo')
                ->get()
                ->map(function ($genOp) {
                    return [
                        'id' => $genOp->id,
                        'nome' => $genOp->nome_completo,
                        'email' => $genOp->user->email,
                        'total_pis' => $genOp->total_pis,
                    ];
                });

            return response()->json([
                'success' => true,
                'gerenciais' => $gerenciais,
            ]);

        } catch (\Exception $e) {
            Log::channel('pis')->error('Erro ao buscar gerenciais disponíveis', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar gerenciais: '.$e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        if ($user->hasRole('representante')) {
            if (!$user->representanteUsuario) {
                return back()->withInput()->with('error', 'Perfil de representante não encontrado.');
            }

            $fornecedorId = $request->input('fornecedor_id');
            if ($fornecedorId && !$user->representanteUsuario->representaFornecedor((int) $fornecedorId)) {
                return back()->withInput()->with('error', 'Você não tem permissão para criar PIs para este fornecedor.');
            }
        }

        if ($request->hasFile('documento_pi')) {
            $file = $request->file('documento_pi');
            $validacao = $this->validarArquivo($file);

            if (! $validacao['valido']) {
                return back()->withInput()->with('error', $validacao['mensagem']);
            }
        }

        $this->convertMoneyFields($request);
        $this->convertPercentField($request);
        $this->convertDaysField($request);

        try {
            $validated = $request->validate([
                'cliente_id' => 'required|exists:clientes,id',
                'fornecedor_id' => 'nullable|exists:fornecedores,id',
                'responsavel_id' => 'nullable|exists:gen_op,id',
                'responsavel_tipo' => 'nullable|in:operacional,gerencial',
                'status_id' => 'nullable|exists:status,id',
                'tipo_veiculo_id' => 'nullable|exists:tipo_veiculos,id',
                'numero_pi' => [
                    'required',
                    'string',
                    'max:100',
                    Rule::unique('pis', 'numero_pi')->whereNull('deleted_at'),
                ],
                'grau_prioridade' => 'nullable|integer|min:1|max:3',
                'periodo' => 'nullable|string|max:255',
                'PIT_n' => 'nullable|string|max:255',
                'plano_n' => 'nullable|string|max:255',
                'planilha_n' => 'nullable|string|max:255',
                'data_cadastro' => 'nullable|date',
                'data_conclusao' => 'nullable|date|after_or_equal:data_cadastro',
                'observacoes' => 'nullable|string',
                'total_bruto' => 'nullable|numeric|min:0',
                'comissao' => 'nullable|numeric|min:0',
                'liquido' => 'nullable|numeric|min:0',
                'custo_unitario' => 'nullable|numeric|min:0',
                'desconto' => 'nullable|numeric|min:0|max:100',
                'custo_total' => 'nullable|numeric|min:0',
                'local_cobranca' => 'nullable|string|max:255',
                'prazo_pagamento' => 'nullable|string|max:255',
                'documento_pi' => 'nullable|file|max:46080',
            ], [
                'cliente_id.required' => 'Por favor, selecione um cliente.',
                'cliente_id.exists' => 'O cliente selecionado não existe.',
                'fornecedor_id.exists' => 'O fornecedor selecionado não existe.',
                'responsavel_id.exists' => 'O responsável selecionado não existe.',
                'responsavel_tipo.in' => 'Tipo de responsável inválido.',
                'tipo_veiculo_id.exists' => 'O tipo de veículo selecionado não existe.',
                'numero_pi.required' => 'O número do PI é obrigatório.',
                'numero_pi.unique' => 'Este número de PI já está cadastrado no sistema.',
                'numero_pi.max' => 'O número do PI não pode ter mais de 100 caracteres.',
                'grau_prioridade.integer' => 'O grau de prioridade deve ser um número.',
                'grau_prioridade.min' => 'O grau de prioridade deve ser no mínimo 1.',
                'grau_prioridade.max' => 'O grau de prioridade deve ser no máximo 3.',
                'data_cadastro.date' => 'A data de cadastro deve ser uma data válida.',
                'data_conclusao.date' => 'A data de conclusão deve ser uma data válida.',
                'data_conclusao.after_or_equal' => 'A data de conclusão não pode ser anterior à data de cadastro.',
                'total_bruto.numeric' => 'O total bruto deve ser um valor numérico.',
                'total_bruto.min' => 'O total bruto não pode ser negativo.',
                'comissao.numeric' => 'A comissão deve ser um valor numérico.',
                'comissao.min' => 'A comissão não pode ser negativa.',
                'liquido.numeric' => 'O líquido deve ser um valor numérico.',
                'liquido.min' => 'O líquido não pode ser negativo.',
                'custo_unitario.numeric' => 'O custo unitário deve ser um valor numérico.',
                'custo_unitario.min' => 'O custo unitário não pode ser negativo.',
                'desconto.numeric' => 'O desconto deve ser um valor numérico.',
                'desconto.min' => 'O desconto não pode ser negativo.',
                'desconto.max' => 'O desconto não pode ser maior que 100%.',
                'custo_total.numeric' => 'O custo total deve ser um valor numérico.',
                'custo_total.min' => 'O custo total não pode ser negativo.',
                'documento_pi.file' => 'O documento deve ser um arquivo válido.',
                'documento_pi.max' => 'O documento não pode ser maior que 45MB.',
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        }

        $cliente = Cliente::find($validated['cliente_id']);
        if ($cliente && $cliente->exige_documento_por_veiculo && empty($validated['tipo_veiculo_id'])) {
            return back()->withInput()->withErrors([
                'tipo_veiculo_id' => 'Este cliente exige a seleção do tipo de veículo para a PI.',
            ]);
        }

        if ($user->hasRole('fornecedor') && $user->fornecedor) {
            $validated['fornecedor_id'] = $user->fornecedor->id;

            if (! $user->fornecedor->dadosBancarios || $user->fornecedor->dadosBancarios->count() == 0) {
                return back()
                    ->withInput()
                    ->with('error', 'O fornecedor não possui dados bancários cadastrados.');
            }
        }

        if (! isset($validated['data_cadastro']) || empty($validated['data_cadastro'])) {
            $validated['data_cadastro'] = today();
        }

        if (! isset($validated['status_id']) || empty($validated['status_id'])) {
            $statusGerado = CacheService::statusPorNome('pi', 'Gerado');
            if (! $statusGerado) {
                return back()->withInput()->with('error', 'Status "Gerado" não encontrado no sistema. Verifique a configuração de status.');
            }
            $validated['status_id'] = $statusGerado->id;
        }

        DB::beginTransaction();
        try {
            $gerencialId = null;
            if ($user->hasAnyRole(['gerencial', 'admin']) && $user->genOp) {
                $gerencialId = $user->genOp->id;
            }

            $validated['gerencial_id'] = $gerencialId;
            $validated['concluido'] = false;

            if ($request->hasFile('documento_pi')) {
                $filePath = $this->handleDocumentUpload($request->file('documento_pi'));
                $validated['file_path'] = $filePath;
            }

            $pi = Pi::create($validated);

            $this->copiarDocumentosDoCliente($pi);

            $operacionalAtribuido = false;

            if ($user->hasAnyRole(['admin', 'gerencial'])) {
                if ($request->filled('responsavel_id')) {
                    $responsavelTipo = $request->input('responsavel_tipo') ?: 'operacional';
                    $operacionalAtribuido = $this->atribuirResponsavelCustom(
                        $pi,
                        $request->responsavel_id,
                        $responsavelTipo
                    );
                } else {
                    $operacionalAtribuido = $this->copiarOperacionaisDoCliente($pi);
                }
            } else {
                $operacionalAtribuido = $this->copiarOperacionaisDoCliente($pi);
            }

            if (! $operacionalAtribuido) {
                Log::channel('pis')->error('PI não pôde ser criado: nenhum operacional atribuído.', [
                    'pi_id' => $pi->id,
                    'numero_pi' => $pi->numero_pi,
                    'cliente_id' => $pi->cliente_id,
                    'user_id' => $user->id,
                ]);

                DB::rollBack();

                if (isset($validated['file_path'])) {
                    $fullPath = $this->getPrivateFilePath($validated['file_path']);
                    if (file_exists($fullPath)) {
                        unlink($fullPath);
                    }
                }

                return back()->withInput()->with('error',
                    'Não foi possível criar o PI: nenhum operacional disponível para este cliente. '
                    .'Por favor, cadastre operacionais no cliente antes de criar PIs, ou selecione um responsável manualmente.'
                );
            }

            if ($pi->fornecedor_id) {
                $this->vincularDocumentosDoFornecedor($pi);
            }

            $valoresData = $this->prepareValoresData($request);
            if (! empty($valoresData)) {
                $pi->valores()->create($valoresData);
            }

            $pi->refresh();

            if (! $user->hasRole('fornecedor') && $pi->fornecedor_id) {
                $docsPendentes = $pi->piDocumentos()->whereNull('documento_enviado_id')->count();
                if ($docsPendentes === 0) {
                    $pi->update(['envio_confirmado' => true]);
                }
            }

            $pi->atualizarStatusAutomaticamente();

            DB::commit();

            if ($user->hasRole('fornecedor')) {
                return redirect()->route('pis.enviar_documentos', $pi)->with('success', 'PI criado com sucesso! Agora você pode enviar os documentos necessários.');
            }

            return redirect()->route('pis.index')->with('success', 'PI criado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();

            if (isset($validated['file_path'])) {
                $fullPath = $this->getPrivateFilePath($validated['file_path']);
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }
            }

            $errorMessage = 'Não foi possível registrar o PI. ';

            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                $errorMessage .= 'Este número de PI já existe no sistema.';
            } elseif (str_contains($e->getMessage(), 'foreign key constraint')) {
                $errorMessage .= 'Há um problema com os dados vinculados. Verifique se o cliente e fornecedor existem.';
            } elseif (str_contains($e->getMessage(), 'disk space')) {
                $errorMessage .= 'Não há espaço suficiente para salvar o documento.';
            } else {
                $errorMessage .= 'Erro: '.$e->getMessage();
            }

            Log::channel('pis')->error('Erro ao criar PI', [
                'user_id' => $user->id,
                'numero_pi' => $validated['numero_pi'] ?? null,
                'cliente_id' => $validated['cliente_id'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withInput()->with('error', $errorMessage);
        }
    }

    public function update(Request $request, Pi $pi)
    {
        $user = Auth::user();

        if ($user->hasRole('operacional')) {
            $temAcesso = $user->genOp && $pi->operacionalTemAcesso($user->genOp->id);
            if (! $temAcesso) {
                abort(403, 'Você não tem permissão para editar este PI.');
            }
        }

        if ($user->hasRole('fornecedor')) {
            if (! $user->fornecedor || $pi->fornecedor_id !== $user->fornecedor->id) {
                abort(403, 'Você não tem permissão para editar este PI.');
            }
        }

        if ($user->hasRole('representante')) {
            if (! $this->representanteTemAcessoAoPi($user, $pi)) {
                abort(403, 'Você não tem permissão para editar este PI.');
            }
        }

        if ($pi->concluido) {
            if (! $user->hasAnyRole(['admin', 'gerencial'])) {
                return back()->with('error', 'PIs concluídos não podem ser editados.');
            }

            $camposCriticos = ['cliente_id', 'fornecedor_id', 'tipo_veiculo_id'];
            foreach ($camposCriticos as $campo) {
                if ($request->filled($campo) && $request->input($campo) != $pi->{$campo}) {
                    return back()->withInput()->with('error', 'Não é possível alterar cliente, fornecedor ou tipo de veículo de um PI concluído.');
                }
            }
        }

        $this->convertMoneyFields($request);
        $this->convertPercentField($request);
        $this->convertDaysField($request);

        try {
            $validated = $request->validate([
                'cliente_id' => 'required|exists:clientes,id',
                'fornecedor_id' => 'nullable|exists:fornecedores,id',
                'responsavel_id' => 'nullable|exists:gen_op,id',
                'responsavel_tipo' => 'nullable|in:operacional,gerencial',
                'status_id' => 'nullable|exists:status,id',
                'tipo_veiculo_id' => 'nullable|exists:tipo_veiculos,id',
                'numero_pi' => [
                    'required',
                    'string',
                    'max:100',
                    Rule::unique('pis', 'numero_pi')->ignore($pi->id)->whereNull('deleted_at'),
                ],
                'grau_prioridade' => 'nullable|integer|min:1|max:3',
                'periodo' => 'nullable|string|max:255',
                'PIT_n' => 'nullable|string|max:255',
                'plano_n' => 'nullable|string|max:255',
                'planilha_n' => 'nullable|string|max:255',
                'data_cadastro' => 'nullable|date',
                'data_conclusao' => 'nullable|date|after_or_equal:data_cadastro',
                'observacoes' => 'nullable|string',
                'concluido' => 'nullable|boolean',
                'total_bruto' => 'nullable|numeric|min:0',
                'comissao' => 'nullable|numeric|min:0',
                'liquido' => 'nullable|numeric|min:0',
                'custo_unitario' => 'nullable|numeric|min:0',
                'desconto' => 'nullable|numeric|min:0|max:100',
                'custo_total' => 'nullable|numeric|min:0',
                'local_cobranca' => 'nullable|string|max:255',
                'prazo_pagamento' => 'nullable|string|max:255',
                'remove_current_file' => 'nullable|boolean',
            ], [
                'cliente_id.required' => 'Por favor, selecione um cliente.',
                'cliente_id.exists' => 'O cliente selecionado não existe.',
                'fornecedor_id.exists' => 'O fornecedor selecionado não existe.',
                'responsavel_id.exists' => 'O responsável selecionado não existe.',
                'responsavel_tipo.in' => 'Tipo de responsável inválido.',
                'tipo_veiculo_id.exists' => 'O tipo de veículo selecionado não existe.',
                'numero_pi.required' => 'O número do PI é obrigatório.',
                'numero_pi.unique' => 'Este número de PI já está cadastrado no sistema.',
                'numero_pi.max' => 'O número do PI não pode ter mais de 100 caracteres.',
                'grau_prioridade.integer' => 'O grau de prioridade deve ser um número.',
                'grau_prioridade.min' => 'O grau de prioridade deve ser no mínimo 1.',
                'grau_prioridade.max' => 'O grau de prioridade deve ser no máximo 3.',
                'data_cadastro.required' => 'A data de cadastro é obrigatória.',
                'data_cadastro.date' => 'A data de cadastro deve ser uma data válida.',
                'data_conclusao.date' => 'A data de conclusão deve ser uma data válida.',
                'data_conclusao.after_or_equal' => 'A data de conclusão não pode ser anterior à data de cadastro.',
                'total_bruto.numeric' => 'O total bruto deve ser um valor numérico.',
                'total_bruto.min' => 'O total bruto não pode ser negativo.',
                'comissao.numeric' => 'A comissão deve ser um valor numérico.',
                'comissao.min' => 'A comissão não pode ser negativa.',
                'liquido.numeric' => 'O líquido deve ser um valor numérico.',
                'liquido.min' => 'O líquido não pode ser negativo.',
                'custo_unitario.numeric' => 'O custo unitário deve ser um valor numérico.',
                'custo_unitario.min' => 'O custo unitário não pode ser negativo.',
                'desconto.numeric' => 'O desconto deve ser um valor numérico.',
                'desconto.min' => 'O desconto não pode ser negativo.',
                'desconto.max' => 'O desconto não pode ser maior que 100%.',
                'custo_total.numeric' => 'O custo total deve ser um valor numérico.',
                'custo_total.min' => 'O custo total não pode ser negativo.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        }

        $cliente = Cliente::find($validated['cliente_id']);
        if ($cliente && $cliente->exige_documento_por_veiculo && empty($validated['tipo_veiculo_id'])) {
            return back()->withInput()->withErrors([
                'tipo_veiculo_id' => 'Este cliente exige a seleção do tipo de veículo para a PI.',
            ]);
        }

        if ($user->hasRole('fornecedor') && $user->fornecedor) {
            $validated = [
                'observacoes' => $validated['observacoes'] ?? $pi->observacoes,
                'cliente_id' => $pi->cliente_id,
                'fornecedor_id' => $pi->fornecedor_id,
                'numero_pi' => $pi->numero_pi,
                'status_id' => $pi->status_id,
                'tipo_veiculo_id' => $pi->tipo_veiculo_id,
                'grau_prioridade' => $pi->grau_prioridade,
                'periodo' => $pi->periodo,
                'PIT_n' => $pi->PIT_n,
                'plano_n' => $pi->plano_n,
                'planilha_n' => $pi->planilha_n,
                'data_cadastro' => $pi->data_cadastro,
                'data_conclusao' => $pi->data_conclusao,
                'concluido' => $pi->concluido,
            ];
        }

        if ($user->hasRole('representante')) {
            $validated = [
                'observacoes' => $validated['observacoes'] ?? $pi->observacoes,
                'cliente_id' => $pi->cliente_id,
                'fornecedor_id' => $pi->fornecedor_id,
                'numero_pi' => $pi->numero_pi,
                'status_id' => $pi->status_id,
                'tipo_veiculo_id' => $pi->tipo_veiculo_id,
                'grau_prioridade' => $pi->grau_prioridade,
                'periodo' => $pi->periodo,
                'PIT_n' => $pi->PIT_n,
                'plano_n' => $pi->plano_n,
                'planilha_n' => $pi->planilha_n,
                'data_cadastro' => $pi->data_cadastro,
                'data_conclusao' => $pi->data_conclusao,
                'concluido' => $pi->concluido,
            ];
        }

        DB::beginTransaction();
        try {
            $clienteAlterado = $pi->cliente_id != $validated['cliente_id'];
            $fornecedorAlterado = $pi->fornecedor_id != ($validated['fornecedor_id'] ?? null);
            $fornecedorAntigoId = $pi->fornecedor_id;
            $tipoVeiculoAlterado = $pi->tipo_veiculo_id != ($validated['tipo_veiculo_id'] ?? null);

            if ($request->input('remove_current_file') == '1' && $pi->file_path && ! $user->hasRole('fornecedor') && ! $user->hasRole('representante')) {
                $fullPath = $this->getPrivateFilePath($pi->file_path);
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }
                $validated['file_path'] = null;
            }

            if ($request->hasFile('documento_pi') && ! $user->hasRole('fornecedor') && ! $user->hasRole('representante')) {
                if ($pi->file_path) {
                    $oldFullPath = $this->getPrivateFilePath($pi->file_path);
                    if (file_exists($oldFullPath)) {
                        unlink($oldFullPath);
                    }
                }

                $filePath = $this->handleDocumentUpload($request->file('documento_pi'));
                $validated['file_path'] = $filePath;
            }

            $pi->update($validated);

            $operacionalAtribuido = true;

            if ($clienteAlterado) {
                $pi->piDocumentos()->delete();
                $this->copiarDocumentosDoCliente($pi);

                $pi->atribuicoesOperacionais()->delete();

                if ($user->hasAnyRole(['admin', 'gerencial'])) {
                    if ($request->filled('responsavel_id')) {
                        $responsavelTipo = $request->input('responsavel_tipo') ?: 'operacional';
                        $operacionalAtribuido = $this->atribuirResponsavelCustom(
                            $pi,
                            $request->responsavel_id,
                            $responsavelTipo
                        );
                    } else {
                        $operacionalAtribuido = $this->copiarOperacionaisDoCliente($pi);
                    }
                } else {
                    $operacionalAtribuido = $this->copiarOperacionaisDoCliente($pi);
                }
            } else {
                if ($tipoVeiculoAlterado && $cliente && $cliente->exige_documento_por_veiculo) {
                    $pi->piDocumentos()->delete();
                    $this->copiarDocumentosDoCliente($pi);

                    if ($pi->fornecedor_id) {
                        $this->vincularDocumentosDoFornecedor($pi);
                    }
                }

                if ($user->hasAnyRole(['admin', 'gerencial'])) {
                    if ($request->filled('responsavel_id')) {
                        $responsavelTipo = $request->input('responsavel_tipo') ?: 'operacional';
                        $pi->atribuicoesOperacionais()->delete();
                        $operacionalAtribuido = $this->atribuirResponsavelCustom(
                            $pi,
                            $request->responsavel_id,
                            $responsavelTipo
                        );
                    }
                }
            }

            if (! $operacionalAtribuido) {
                Log::channel('pis')->warning('PI atualizado sem operacional atribuído.', [
                    'pi_id' => $pi->id,
                    'numero_pi' => $pi->numero_pi,
                    'cliente_id' => $pi->cliente_id,
                    'user_id' => $user->id,
                ]);
            }

            if ($fornecedorAlterado && $pi->fornecedor_id) {
                if ($fornecedorAntigoId) {
                    $statusPendente = CacheService::statusPorNome('documento', 'Pendente');

                    if ($statusPendente) {
                        $piDocsDoAntigoFornecedor = $pi->piDocumentos()
                            ->whereNotNull('documento_enviado_id')
                            ->whereHas('documentoEnviado', function ($q) use ($fornecedorAntigoId) {
                                $q->where('fornecedor_id', $fornecedorAntigoId);
                            })
                            ->get();

                        foreach ($piDocsDoAntigoFornecedor as $piDoc) {
                            $piDoc->update([
                                'documento_enviado_id' => null,
                                'status_id' => $statusPendente->id,
                            ]);
                        }
                    }

                    $pi->update(['envio_confirmado' => false]);
                }

                $this->vincularDocumentosDoFornecedor($pi);
            }

            $pi->refresh();

            if (! $user->hasRole('fornecedor') && ! $user->hasRole('representante') && $pi->fornecedor_id) {
                $docsPendentes = $pi->piDocumentos()->whereNull('documento_enviado_id')->count();
                if ($docsPendentes === 0 && ! $pi->envio_confirmado) {
                    $pi->update(['envio_confirmado' => true]);
                }
            }

            $pi->atualizarStatusAutomaticamente();

            if (! $user->hasRole('fornecedor') && ! $user->hasRole('representante')) {
                $valoresData = $this->prepareValoresData($request);
                if (! empty($valoresData)) {
                    $pi->valores()->updateOrCreate(
                        ['pi_id' => $pi->id],
                        $valoresData
                    );
                }
            }

            DB::commit();

            if (! $operacionalAtribuido) {
                return redirect()->route('pis.index')->with('warning', 'PI atualizado com sucesso, porém nenhum operacional foi atribuído automaticamente. Por favor, vincule um operacional manualmente.');
            }

            return redirect()->route('pis.index')->with('success', 'PI atualizado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();

            if (isset($validated['file_path']) && $request->hasFile('documento_pi')) {
                $fullPath = $this->getPrivateFilePath($validated['file_path']);
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }
            }

            Log::channel('pis')->error('Erro ao atualizar PI', [
                'pi_id' => $pi->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withInput()->with('error', 'Erro ao atualizar PI: '.$e->getMessage());
        }
    }

    public function downloadDocumento(Pi $pi)
    {
        $user = Auth::user();

        if ($user->hasRole('fornecedor')) {
            if (! $user->fornecedor || $pi->fornecedor_id !== $user->fornecedor->id) {
                abort(403, 'Você não tem permissão para baixar este documento.');
            }
        }

        if ($user->hasRole('operacional')) {
            if (! $user->genOp || ! $pi->operacionalTemAcesso($user->genOp->id)) {
                abort(403, 'Você não tem permissão para baixar este documento.');
            }
        }

        if ($user->hasRole('representante')) {
            if (! $this->representanteTemAcessoAoPi($user, $pi)) {
                abort(403, 'Você não tem permissão para baixar este documento.');
            }
        }

        if (! $pi->file_path) {
            return back()->with('error', 'Este PI não possui documento anexado.');
        }

        $filePath = $this->getPrivateFilePath($pi->file_path);

        if (! file_exists($filePath)) {
            return back()->with('error', 'Arquivo não encontrado no servidor.');
        }

        $fileName = 'PI_'.$pi->numero_pi.'_'.now()->format('Y-m-d').'.pdf';

        return response()->download($filePath, $fileName, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function vincularOperacional(Request $request, Pi $pi)
    {
        $user = Auth::user();

        if ($user->hasRole('operacional')) {
            $temAcesso = $user->genOp && $pi->operacionalTemAcesso($user->genOp->id);
            if (! $temAcesso) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para vincular operacionais a este PI.',
                ], 403);
            }
        }

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

            if (! $operacional->user->hasRole('operacional')) {
                return response()->json([
                    'success' => false,
                    'message' => 'O usuário selecionado não é um operacional.',
                ], 422);
            }

            $jaVinculado = PiAtribuicao::where('pi_id', $pi->id)
                ->where('operacional_id', $validated['operacional_id'])
                ->where('ativo', true)
                ->whereNull('deleted_at')
                ->exists();

            if ($jaVinculado) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este operacional já está vinculado a este PI.',
                ], 422);
            }

            $temPrincipal = PiAtribuicao::where('pi_id', $pi->id)
                ->where('ativo', true)
                ->where('principal', true)
                ->whereNull('deleted_at')
                ->exists();

            $ehPrincipal = ! $temPrincipal;

            PiAtribuicao::create([
                'pi_id' => $pi->id,
                'cliente_id' => $pi->cliente_id,
                'operacional_id' => $validated['operacional_id'],
                'data_atribuicao' => now(),
                'ativo' => true,
                'principal' => $ehPrincipal,
            ]);

            if ($ehPrincipal || ! $pi->operacional_id) {
                $pi->update(['operacional_id' => $validated['operacional_id']]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Operacional vinculado com sucesso!',
                'operacional' => [
                    'id' => $operacional->id,
                    'nome' => $operacional->nome_completo,
                    'email' => $operacional->user->email,
                ],
            ]);
        } catch (\Exception $e) {
            Log::channel('pis')->error('Erro ao vincular operacional ao PI.', [
                'pi_id' => $pi->id,
                'operacional_id' => $validated['operacional_id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao vincular operacional: '.$e->getMessage(),
            ], 500);
        }
    }

    public function alterarStatus(Request $request, Pi $pi)
    {
        $user = Auth::user();

        if ($user->hasRole('operacional')) {
            $temAcesso = $user->genOp && $pi->operacionalTemAcesso($user->genOp->id);
            if (! $temAcesso) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para alterar o status deste PI.',
                ], 403);
            }
        }

        $validated = $request->validate([
            'status_id' => 'required|exists:status,id',
        ]);

        try {
            $status = Status::findOrFail($validated['status_id']);

            if ($status->tipo !== 'pi') {
                return response()->json([
                    'success' => false,
                    'message' => 'O status selecionado não é válido para PIs.',
                ], 422);
            }

            if ($status->nome === 'Concluído') {
                return response()->json([
                    'success' => false,
                    'message' => 'Para concluir um PI, utilize o botão de conclusão. Todos os documentos precisam estar aprovados.',
                ], 422);
            }

            if ($pi->concluido) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este PI já está concluído e não pode ter o status alterado manualmente.',
                ], 422);
            }

            $pi->update(['status_id' => $validated['status_id']]);

            return response()->json([
                'success' => true,
                'message' => 'Status alterado com sucesso!',
                'status' => [
                    'id' => $status->id,
                    'nome' => $status->nome,
                    'cor' => $status->cor,
                ],
            ]);
        } catch (\Exception $e) {
            Log::channel('pis')->error('Erro ao alterar status do PI', [
                'pi_id' => $pi->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erro ao alterar status: '.$e->getMessage(),
            ], 500);
        }
    }

    public function getOperacionaisDisponiveis()
    {
        try {
            $operacionais = GenOp::with('user')
                ->whereHas('user', function ($q) {
                    $q->whereNull('deleted_at')
                        ->whereHas('roles', function ($rq) {
                            $rq->where('name', 'operacional');
                        });
                })
                ->orderBy('nome_completo')
                ->get()
                ->map(function ($genOp) {
                    return [
                        'id' => $genOp->id,
                        'nome' => $genOp->nome_completo,
                        'email' => $genOp->user->email,
                        'departamento' => $genOp->departamento,
                        'telefone' => $genOp->telefone,
                    ];
                });

            return response()->json([
                'success' => true,
                'operacionais' => $operacionais,
            ]);
        } catch (\Exception $e) {
            Log::channel('pis')->error('Erro ao buscar operacionais disponíveis', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar operacionais: '.$e->getMessage(),
            ], 500);
        }
    }

    public function getOperacionaisCliente($clienteId)
    {
        $user = Auth::user();

        if (! $user->hasAnyRole(['admin', 'gerencial', 'operacional'])) {
            return response()->json([
                'success' => false,
                'message' => 'Sem permissão.',
            ], 403);
        }

        try {
            $operacionais = Cliente::findOrFail($clienteId)
                ->operacionais()
                ->with('user')
                ->get();

            return response()->json([
                'success' => true,
                'operacionais' => $operacionais,
            ]);
        } catch (\Exception $e) {
            Log::channel('pis')->error('Erro ao buscar operacionais do cliente', [
                'cliente_id' => $clienteId,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar operacionais: '.$e->getMessage(),
            ], 500);
        }
    }

    public function concluir(Request $request, Pi $pi)
    {
        $user = Auth::user();

        if ($user->hasRole('fornecedor')) {
            return response()->json([
                'success' => false,
                'message' => 'Fornecedores não podem concluir PIs.',
            ], 403);
        }

        if ($user->hasRole('representante')) {
            return response()->json([
                'success' => false,
                'message' => 'Representantes não podem concluir PIs.',
            ], 403);
        }

        if ($user->hasRole('operacional')) {
            $temAcesso = $user->genOp && $pi->operacionalTemAcesso($user->genOp->id);
            if (! $temAcesso) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para concluir este PI.',
                ], 403);
            }
        }

        if ($pi->concluido) {
            return response()->json([
                'success' => true,
                'message' => 'Este PI já foi concluído anteriormente.',
                'data_conclusao' => $pi->data_conclusao ? $pi->data_conclusao->format('d/m/Y H:i') : null,
            ]);
        }

        $forcar = $request->boolean('forcar');
        $podeForcar = $user->hasAnyRole(['admin', 'gerencial']);

        if ($forcar && ! $podeForcar) {
            return response()->json([
                'success' => false,
                'message' => 'Apenas administradores e gerenciais podem forçar a conclusão de um PI.',
            ], 403);
        }

        if (! $forcar) {
            $pi->invalidarDocumentosVencidos();

            if (! $pi->todosDocumentosAprovados()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Todos os documentos precisam estar aprovados para concluir o PI.',
                    'pode_forcar' => $podeForcar,
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            $piLocked = Pi::lockForUpdate()->find($pi->id);

            if ($piLocked->concluido) {
                DB::rollBack();

                return response()->json([
                    'success' => true,
                    'message' => 'Este PI já foi concluído anteriormente.',
                    'data_conclusao' => $piLocked->data_conclusao ? $piLocked->data_conclusao->format('d/m/Y H:i') : null,
                ]);
            }

            $statusConcluido = CacheService::statusPorNome('pi', 'Concluído');

            $piLocked->update([
                'concluido' => true,
                'data_conclusao' => now(),
                'status_id' => $statusConcluido ? $statusConcluido->id : $piLocked->status_id,
            ]);

            $this->resolverConversasDoPi($piLocked);

            DB::commit();

            if ($forcar) {
                Log::channel('pis')->warning('PI concluído forçadamente', [
                    'pi_id' => $pi->id,
                    'numero_pi' => $pi->numero_pi,
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                ]);
            }

            $pi->refresh();

            $this->notificarConclusaoPi($pi);

            $mensagem = $forcar
                ? 'PI concluído com sucesso! (conclusão forçada — documentos pendentes foram ignorados)'
                : 'PI concluído com sucesso!';

            return response()->json([
                'success' => true,
                'message' => $mensagem,
                'data_conclusao' => $pi->data_conclusao->format('d/m/Y H:i'),
                'forcado' => $forcar,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::channel('pis')->error('Erro ao concluir PI', [
                'pi_id' => $pi->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao concluir PI: '.$e->getMessage(),
            ], 500);
        }
    }

    public function pagarPi(Request $request, Pi $pi)
    {
        $user = Auth::user();

        if (! $user->hasAnyRole(['admin', 'gerencial', 'operacional'])) {
            return response()->json([
                'success' => false,
                'message' => 'Você não tem permissão para pagar PIs.',
            ], 403);
        }

        if ($user->hasRole('operacional')) {
            $temAcesso = $user->genOp && $pi->operacionalTemAcesso($user->genOp->id);
            if (! $temAcesso) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para pagar este PI.',
                ], 403);
            }
        }

        if ($request->input('remover')) {
            if (! $pi->pago) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este PI não possui registro de pagamento.',
                ], 422);
            }

            try {
                $statusConcluido = CacheService::statusPorNome('pi', 'Concluído');

                $dadosAtualizar = [
                    'pago' => false,
                    'data_pagamento' => null,
                ];

                if ($statusConcluido) {
                    $dadosAtualizar['status_id'] = $statusConcluido->id;
                }

                $pi->update($dadosAtualizar);

                Log::channel('pis')->info('Pagamento removido do PI', [
                    'pi_id' => $pi->id,
                    'pi_numero' => $pi->numero_pi,
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Pagamento removido com sucesso!',
                ]);
            } catch (\Exception $e) {
                Log::channel('pis')->error('Erro ao remover pagamento do PI', [
                    'pi_id' => $pi->id,
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao remover pagamento: ' . $e->getMessage(),
                ], 500);
            }
        }

        if (! $pi->concluido) {
            return response()->json([
                'success' => false,
                'message' => 'O PI precisa estar concluído antes de ser pago.',
            ], 422);
        }

        $validated = $request->validate([
            'data_pagamento' => 'required|date',
        ], [
            'data_pagamento.required' => 'Informe a data de pagamento.',
            'data_pagamento.date' => 'Data de pagamento inválida.',
        ]);

        try {
            $acao = $pi->pago ? 'atualizado' : 'registrado';

            $statusPago = CacheService::statusPorNome('pi', 'Pago');

            $dadosAtualizar = [
                'pago' => true,
                'data_pagamento' => \Carbon\Carbon::parse($validated['data_pagamento']),
            ];

            if ($statusPago) {
                $dadosAtualizar['status_id'] = $statusPago->id;
            }

            $pi->update($dadosAtualizar);

            Log::channel('pis')->info("Pagamento {$acao} no PI", [
                'pi_id' => $pi->id,
                'pi_numero' => $pi->numero_pi,
                'data_pagamento' => $validated['data_pagamento'],
                'user_id' => $user->id,
                'user_name' => $user->name,
            ]);

            $mensagem = $acao === 'atualizado'
                ? 'Data de pagamento atualizada com sucesso!'
                : 'PI pago com sucesso!';

            return response()->json([
                'success' => true,
                'message' => $mensagem,
            ]);
        } catch (\Exception $e) {
            Log::channel('pis')->error('Erro ao faturar PI', [
                'pi_id' => $pi->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao pagar PI: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function baixarTodosDocumentos(Pi $pi)
    {
        $user = Auth::user();

        if ($user->hasRole('fornecedor')) {
            if (! $user->fornecedor || $pi->fornecedor_id !== $user->fornecedor->id) {
                abort(403, 'Você não tem permissão para baixar os documentos deste PI.');
            }
        }

        if ($user->hasRole('operacional')) {
            if (! $user->genOp || ! $pi->operacionalTemAcesso($user->genOp->id)) {
                abort(403, 'Você não tem permissão para baixar os documentos deste PI.');
            }
        }

        if ($user->hasRole('representante')) {
            if (! $this->representanteTemAcessoAoPi($user, $pi)) {
                abort(403, 'Você não tem permissão para baixar os documentos deste PI.');
            }
        }

        try {
            $zip = new \ZipArchive;
            $zipFileName = 'PI_'.$pi->numero_pi.'_Documentos_'.now()->format('Y-m-d_His').'.zip';
            $zipPath = storage_path('app/temp/'.$zipFileName);

            if (! file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }

            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                throw new \Exception('não foi possível criar o arquivo ZIP.');
            }

            $arquivosAdicionados = 0;
            $nomesUsados = [];

            if ($pi->file_path) {
                $piDocPath = $this->getPrivateFilePath($pi->file_path);
                if (file_exists($piDocPath)) {
                    $nomeArquivo = 'PI_'.$pi->numero_pi.'.pdf';
                    $nomesUsados[] = $nomeArquivo;
                    $zip->addFile($piDocPath, $nomeArquivo);
                    $arquivosAdicionados++;
                }
            }

            $statusAprovado = CacheService::statusPorNome('documento', 'Aprovado');

            $statusAtendido = CacheService::statusPorNome('documento', 'Atendido');

            $statusIdsPermitidos = collect([$statusAprovado, $statusAtendido])
                ->filter()
                ->pluck('id')
                ->toArray();

            $piDocumentos = $pi->piDocumentos()
                ->with(['documentoEnviado.arquivos', 'tipoDocumento'])
                ->whereNotNull('documento_enviado_id')
                ->whereIn('status_id', $statusIdsPermitidos)
                ->get();

            foreach ($piDocumentos as $piDoc) {
                if ($piDoc->documentoEnviado && $piDoc->documentoEnviado->arquivos->count() > 0) {
                    $pastaDoc = 'Documentos_PI/';

                    foreach ($piDoc->documentoEnviado->arquivos as $arquivo) {
                        $arquivoPath = $this->encontrarArquivo($arquivo->file_path);

                        if ($arquivoPath) {
                            $nomeArquivo = $pastaDoc.$this->gerarNomeUnico($arquivo->nome_original, $nomesUsados);
                            $nomesUsados[] = $nomeArquivo;
                            $zip->addFile($arquivoPath, $nomeArquivo);
                            $arquivosAdicionados++;
                        }
                    }
                }
            }

            $zip->close();

            if ($arquivosAdicionados === 0) {
                if (file_exists($zipPath)) {
                    unlink($zipPath);
                }

                return back()->with('error', 'Nenhum documento encontrado para este PI.');
            }

            return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::channel('pis')->error('Erro ao baixar documentos do PI', [
                'pi_id' => $pi->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Erro ao baixar documentos: '.$e->getMessage());
        }
    }

    private function encontrarArquivo(string $filePath): ?string
    {
        $caminhosPossiveis = [
            storage_path('app/private/'.$filePath),
            storage_path('app/'.$filePath),
        ];

        foreach ($caminhosPossiveis as $caminho) {
            if (file_exists($caminho)) {
                return $caminho;
            }
        }

        return null;
    }

    private function gerarNomeUnico(string $nomeOriginal, array $nomesUsados): string
    {
        $nomeArquivo = $nomeOriginal;
        $contador = 1;
        $nomeBase = pathinfo($nomeArquivo, PATHINFO_FILENAME);
        $extensao = pathinfo($nomeArquivo, PATHINFO_EXTENSION);

        while (in_array($nomeArquivo, $nomesUsados)) {
            $nomeArquivo = $nomeBase.'_'.$contador.'.'.$extensao;
            $contador++;
        }

        return $nomeArquivo;
    }

    public function showEnviarDocumentos(Pi $pi)
    {
        $user = Auth::user();

        if ($user->hasRole('operacional')) {
            $temAcesso = $user->genOp && $pi->operacionalTemAcesso($user->genOp->id);
            if (! $temAcesso) {
                abort(403, 'Você não tem permissão para enviar documentos deste PI.');
            }
        }

        if ($user->hasRole('fornecedor')) {
            if (! $user->fornecedor || $pi->fornecedor_id !== $user->fornecedor->id) {
                abort(403, 'Você não tem permissão para acessar este PI.');
            }
        }

        if ($user->hasRole('representante')) {
            if (! $this->representanteTemAcessoAoPi($user, $pi)) {
                abort(403, 'Você não tem permissão para acessar este PI.');
            }
        }

        $pi->load([
            'cliente',
            'fornecedor',
            'piDocumentos.tipoDocumento',
            'piDocumentos.documentoEnviado.arquivos',
            'piDocumentos.documentoEnviado.status',
            'piDocumentos.status',
        ]);

        $documentosPendentes = $pi->piDocumentos()
            ->whereNull('documento_enviado_id')
            ->with('tipoDocumento')
            ->get();

        $documentosParaReenviar = $pi->piDocumentos()
            ->whereNotNull('documento_enviado_id')
            ->whereHas('documentoEnviado', function ($q) {
                $q->whereHas('status', function ($sq) {
                    $sq->where('tipo', 'documento')
                        ->where(function ($statusQuery) {
                            $statusQuery->whereRaw('LOWER(nome) = ?', ['reprovado'])
                                ->orWhereRaw('LOWER(nome) LIKE ?', ['%aguardando%reenvio%']);
                        });
                });
            })
            ->with([
                'tipoDocumento',
                'documentoEnviado.arquivos',
                'documentoEnviado.status',
                'documentoEnviado.validacoes' => function ($q) {
                    $q->with(['operacional.user', 'status'])
                        ->orderBy('created_at', 'desc')
                        ->limit(1);
                },
            ])
            ->get();

        $documentosAguardandoValidacao = $pi->piDocumentos()
            ->whereNotNull('documento_enviado_id')
            ->whereHas('documentoEnviado', function ($q) {
                $q->whereHas('status', function ($sq) {
                    $sq->where('tipo', 'documento')
                        ->where(function ($statusQuery) {
                            $statusQuery->whereRaw('LOWER(nome) = ?', ['pendente'])
                                ->orWhereRaw('LOWER(nome) = ?', ['aguardando validação'])
                                ->orWhereRaw('LOWER(nome) LIKE ?', ['%aguardando%validação%']);
                        });
                });
            })
            ->with([
                'tipoDocumento',
                'documentoEnviado.arquivos',
                'documentoEnviado.status',
            ])
            ->get();

        $documentosAtendidos = $pi->piDocumentos()
            ->whereNotNull('documento_enviado_id')
            ->whereHas('documentoEnviado', function ($q) {
                $q->whereHas('status', function ($sq) {
                    $sq->where('nome', 'Aprovado')
                        ->where('tipo', 'documento');
                });
            })
            ->with(['tipoDocumento', 'documentoEnviado.arquivos', 'documentoEnviado.status'])
            ->get();

        return view('pis.enviar-documentos', compact(
            'pi',
            'documentosPendentes',
            'documentosParaReenviar',
            'documentosAguardandoValidacao',
            'documentosAtendidos'
        ));
    }

    public function storeDocumentosPi(Request $request, Pi $pi)
    {
        $user = Auth::user();

        if ($user->hasRole('operacional')) {
            $temAcesso = $user->genOp && $pi->operacionalTemAcesso($user->genOp->id);
            if (! $temAcesso) {
                abort(403, 'Você não tem permissão para enviar documentos deste PI.');
            }
        }

        if ($user->hasRole('fornecedor')) {
            if (! $user->fornecedor || $pi->fornecedor_id !== $user->fornecedor->id) {
                abort(403, 'Você não tem permissão para enviar documentos neste PI.');
            }
        }

        if ($user->hasRole('representante')) {
            if (! $this->representanteTemAcessoAoPi($user, $pi)) {
                abort(403, 'Você não tem permissão para enviar documentos neste PI.');
            }
        }

        if ($request->hasFile('arquivo')) {
            $file = $request->file('arquivo');
            $validacao = $this->validarArquivo($file);

            if (! $validacao['valido']) {
                Log::channel('docs')->error('Arquivo rejeitado pela validação custom', ['pi_id' => $pi->id, 'user_id' => $user->id]);

                return back()->withInput()->with('error', $validacao['mensagem']);
            }
        }

        $tipoDocumento = TipoDocumento::findOrFail($request->tipo_documento_id);
        $dataValidadeObrigatoria = $tipoDocumento->validar == 1 && $tipoDocumento->validade_dias > 0;

        $regrasValidacao = [
            'tipo_documento_id' => 'required|exists:tipo_documentos,id',
            'arquivo' => 'required|file|max:46080',
        ];

        if ($dataValidadeObrigatoria) {
            $regrasValidacao['data_validade_documento'] = 'required|date|after_or_equal:today';
        } else {
            $regrasValidacao['data_validade_documento'] = 'nullable|date|after_or_equal:today';
        }

        $validated = $request->validate($regrasValidacao, [
            'tipo_documento_id.required' => 'Selecione o tipo de documento',
            'tipo_documento_id.exists' => 'Tipo de documento inválido',
            'data_validade_documento.required' => 'Informe a data de validade do documento',
            'data_validade_documento.date' => 'Data de validade inválida',
            'data_validade_documento.after_or_equal' => 'A data de validade não pode ser anterior a hoje',
            'arquivo.required' => 'Selecione um arquivo para enviar',
            'arquivo.file' => 'Arquivo inválido',
            'arquivo.max' => 'O arquivo não pode exceder 45MB',
        ]);

        DB::beginTransaction();

        try {
            if (! $pi->fornecedor_id) {
                DB::rollBack();

                return back()->withInput()->with('error', 'Este PI não possui fornecedor vinculado.');
            }

            $piDocumento = PiDocumento::where('pi_id', $pi->id)
                ->where('tipo_documento_id', $validated['tipo_documento_id'])
                ->first();

            if (! $piDocumento) {
                DB::rollBack();

                return back()->withInput()->with('error', 'Documento não encontrado para este PI.');
            }

            $ehReenvio = ! is_null($piDocumento->documento_enviado_id);

            if ($ehReenvio) {
                $statusInicial = CacheService::statusPorNome('documento', 'Aguardando validação');
            } else {
                $statusInicial = CacheService::statusPorNome('documento', 'Pendente');
            }

            if (! $statusInicial) {
                $statusInicial = CacheService::statusPorNome('documento', 'Pendente');
            }

            if (! $statusInicial) {
                throw new \Exception('Status inicial não encontrado.');
            }

            $dataValidade = null;
            $valido = true;

            if (! empty($validated['data_validade_documento'])) {
                $dataValidade = \Carbon\Carbon::parse($validated['data_validade_documento']);
                $valido = $dataValidade->endOfDay()->isFuture() || $dataValidade->isToday();
            }

            if ($ehReenvio) {
                $documentoEnviado = DocumentoEnviado::find($piDocumento->documento_enviado_id);

                if (! $documentoEnviado) {
                    throw new \Exception('Documento anterior não encontrado.');
                }

                foreach ($documentoEnviado->arquivos as $arquivoAntigo) {
                    $caminhos = [
                        storage_path('app/private/'.$arquivoAntigo->file_path),
                        storage_path('app/'.$arquivoAntigo->file_path),
                    ];
                    foreach ($caminhos as $caminho) {
                        if (file_exists($caminho)) {
                            unlink($caminho);
                            break;
                        }
                    }
                    $arquivoAntigo->delete();
                }

                $documentoEnviado->update([
                    'status_id' => $statusInicial->id,
                    'data_envio' => now(),
                    'data_validade_documento' => $dataValidade,
                    'valido' => $valido,
                ]);

                $piDocumento->update([
                    'status_id' => $statusInicial->id,
                ]);

            } else {
                $documentoEnviado = DocumentoEnviado::create([
                    'fornecedor_id' => $pi->fornecedor_id,
                    'tipo_documento_id' => $validated['tipo_documento_id'],
                    'status_id' => $statusInicial->id,
                    'data_envio' => now(),
                    'data_validade_documento' => $dataValidade,
                    'valido' => $valido,
                ]);

                $piDocumento->update([
                    'documento_enviado_id' => $documentoEnviado->id,
                    'status_id' => $statusInicial->id,
                ]);
            }

            if ($request->hasFile('arquivo')) {
                $file = $request->file('arquivo');

                if (! $file->isValid()) {
                    throw new \Exception('Arquivo inválido: '.$file->getErrorMessage());
                }

                $fileOriginalName = $file->getClientOriginalName();
                $fileExtension = $file->getClientOriginalExtension();
                $fileSize = $file->getSize();
                $fileMimeType = $file->getMimeType();

                $directory = storage_path('app/private/fornecedor_documentos/'.$pi->fornecedor_id);

                if (! file_exists($directory)) {
                    $created = mkdir($directory, 0755, true);
                    if (! $created) {
                        throw new \Exception('Não foi possível criar o diretório: '.$directory);
                    }
                }

                if (! is_writable($directory)) {
                    throw new \Exception('Diretório não tem permissão de escrita: '.$directory);
                }

                $fileName = time().'_'.uniqid().'_'.preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileOriginalName);
                $fullPath = $directory.'/'.$fileName;

                $saved = $file->move($directory, $fileName);

                if (! $saved || ! file_exists($fullPath)) {
                    throw new \Exception('Falha ao mover arquivo para o destino');
                }

                $relativePath = 'fornecedor_documentos/'.$pi->fornecedor_id.'/'.$fileName;

                Arquivo::create([
                    'documento_enviado_id' => $documentoEnviado->id,
                    'nome_original' => $fileOriginalName,
                    'nome_arquivo' => $fileName,
                    'file_path' => $relativePath,
                    'extensao' => $fileExtension,
                    'tamanho' => $fileSize,
                    'mime_type' => $fileMimeType,
                ]);
            }

            $pi->refresh();

            $resultado = $pi->atualizarStatusAutomaticamente();

            DB::commit();

            $mensagemSucesso = $ehReenvio
                ? 'Documento reenviado com sucesso! Aguardando validação da equipe.'
                : 'Documento enviado com sucesso!';

            if ($resultado === 'Em Processo') {
                $mensagemSucesso = 'Todos os documentos foram enviados! A PI está em processo de avaliação.';
            }

            return redirect()->route('pis.enviar_documentos', $pi)->with('success', $mensagemSucesso);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::channel('docs')->error('Erro ao enviar/reenviar documento PI', [
                'pi_id' => $pi->id,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Erro ao enviar documento: '.$e->getMessage());
        }
    }

    public function validarDataPdf(Request $request, Pi $pi)
    {
        $user = Auth::user();

        try {
            $request->validate([
                'arquivo' => 'required|file|max:46080',
                'data_validade' => 'required|date|after_or_equal:today',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::channel('docs')->warning('Validação de data PDF com dados inválidos', [
                'pi_id' => $pi->id,
                'user_id' => $user->id,
                'errors' => $e->errors(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
            ], 422);
        }

        if (! $request->hasFile('arquivo')) {
            return response()->json([
                'success' => false,
                'message' => 'Nenhum arquivo foi enviado',
            ], 422);
        }

        $file = $request->file('arquivo');

        if (! $file->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'Arquivo corrompido',
            ], 422);
        }

        if (strtolower($file->getClientOriginalExtension()) !== 'pdf') {
            return response()->json([
                'success' => false,
                'message' => 'Apenas arquivos PDF são permitidos',
            ], 422);
        }

        try {
            $dataInformada = \Carbon\Carbon::parse($request->data_validade);

            $resultadoBinario = $this->buscarDataNoBinarioPdf($file, $dataInformada);

            if ($resultadoBinario['encontrada']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Documento validado com sucesso',
                ]);
            }

            $resultadoTexto = $this->extrairEValidarTextoPdf($file, $dataInformada);

            if ($resultadoTexto['encontrada']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Documento validado com sucesso',
                ]);
            }

            if ($resultadoTexto['eh_imagem_escaneada']) {
                Log::channel('docs')->info('PDF escaneado - validação aceita para revisão manual', [
                    'arquivo' => $file->getClientOriginalName(),
                    'data_informada' => $dataInformada->format('d/m/Y'),
                ]);

                return response()->json([
                    'success' => true,
                    'warning' => true,
                    'message' => 'Documento aceito. Por ser uma imagem escaneada, a data será verificada manualmente pela equipe.',
                ]);
            }

            Log::channel('docs')->info('Data não encontrada no PDF - aceito para revisão manual', [
                'arquivo' => $file->getClientOriginalName(),
                'data_procurada' => $dataInformada->format('d/m/Y'),
            ]);

            return response()->json([
                'success' => true,
                'warning' => true,
                'message' => 'Documento aceito. A data de validade será verificada manualmente pela equipe.',
            ]);

        } catch (\Exception $e) {
            Log::channel('docs')->error('Erro ao processar PDF', [
                'erro' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar o documento. Verifique se o arquivo estão correto e tente novamente.',
            ], 500);
        }
    }

    public function showValidarDocumentos(Pi $pi)
    {
        $user = Auth::user();

        if ($user->hasRole('operacional')) {
            $temAcesso = $user->genOp && $pi->operacionalTemAcesso($user->genOp->id);
            if (! $temAcesso) {
                abort(403, 'Você não tem permissão para validar documentos deste PI.');
            }
        }

        $pi->invalidarDocumentosVencidos();

        $pi->load([
            'cliente',
            'fornecedor.user',
            'gerencial.user',
            'operacionais.user',
            'status',
            'piDocumentos' => function ($query) {
                $query->with([
                    'tipoDocumento',
                    'documentoEnviado' => function ($q) {
                        $q->with(['arquivos', 'status', 'validacoes' => function ($vq) {
                            $vq->with(['operacional.user', 'status'])->orderBy('created_at', 'desc');
                        }]);
                    },
                    'status',
                ]);
            },
        ]);

        $statusAprovado = CacheService::statusPorNome('documento', 'Aprovado');
        $statusReprovado = CacheService::statusPorNome('documento', 'Reprovado');
        $statusPendente = CacheService::statusPorNome('documento', 'Pendente');
        $statusAguardandoValidacao = CacheService::statusPorNome('documento', 'Aguardando validação');
        $statusReenvio = CacheService::statusPorNome('documento', 'Aguardando Reenvio');

        $documentosPendentes = $pi->piDocumentos->filter(function ($piDoc) use ($statusPendente, $statusAguardandoValidacao) {
            if (! $piDoc->documentoEnviado) {
                return false;
            }

            return ($statusPendente && $piDoc->documentoEnviado->status_id === $statusPendente->id) ||
                   ($statusAguardandoValidacao && $piDoc->documentoEnviado->status_id === $statusAguardandoValidacao->id);
        });

        $documentosAprovados = $pi->piDocumentos->filter(function ($piDoc) use ($statusAprovado) {
            if (! $piDoc->documentoEnviado) {
                return false;
            }

            return $statusAprovado && $piDoc->documentoEnviado->status_id === $statusAprovado->id;
        });

        $documentosReprovados = $pi->piDocumentos->filter(function ($piDoc) use ($statusReprovado, $statusReenvio) {
            if (! $piDoc->documentoEnviado) {
                return false;
            }

            return ($statusReprovado && $piDoc->documentoEnviado->status_id === $statusReprovado->id) ||
                   ($statusReenvio && $piDoc->documentoEnviado->status_id === $statusReenvio->id);
        });

        $documentosSemEnvio = $pi->piDocumentos->filter(function ($piDoc) {
            return ! $piDoc->documentoEnviado;
        });

        $documentosComEnvio = $pi->piDocumentos->filter(fn($pd) => $pd->documentoEnviado);

        $dataPrimeiroEnvio = null;
        $dataUltimaAprovacao = null;

        if ($documentosComEnvio->isNotEmpty()) {
            $dataPrimeiroEnvio = $documentosComEnvio
                ->map(fn($pd) => $pd->documentoEnviado->data_envio)
                ->filter()
                ->filter(fn($d) => $d >= $pi->created_at)
                ->min() ?? $pi->created_at;

            if ($statusAprovado) {
                $todosAprovados = $documentosComEnvio->every(
                    fn($pd) => $pd->documentoEnviado->status_id === $statusAprovado->id
                );

                if ($todosAprovados && $documentosComEnvio->count() === $pi->piDocumentos->count()) {
                    $dataUltimaAprovacao = $documentosComEnvio
                        ->flatMap(fn($pd) => $pd->documentoEnviado->validacoes)
                        ->filter(fn($v) => $v->status_id === $statusAprovado->id)
                        ->max('data_validacao');
                }
            }
        }

        return view('pis.validar-documentos', compact(
            'pi',
            'documentosPendentes',
            'documentosAprovados',
            'documentosReprovados',
            'documentosSemEnvio',
            'dataPrimeiroEnvio',
            'dataUltimaAprovacao'
        ));
    }

    public function aprovarDocumento(Request $request, Pi $pi, PiDocumento $documento)
    {
        $user = Auth::user();

        if (! $user->hasRole('admin') && ! $user->genOp) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário sem perfil de gerencial/operacional vinculado.',
            ], 403);
        }

        if ($user->hasRole('operacional')) {
            $temAcesso = $user->genOp && $pi->operacionalTemAcesso($user->genOp->id);
            if (! $temAcesso) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para aprovar documentos deste PI.',
                ], 403);
            }
        }

        if ($pi->concluido) {
            return response()->json([
                'success' => false,
                'message' => 'Este PI já foi concluído. Não é possível validar documentos.',
            ], 422);
        }

        if ($documento->pi_id !== $pi->id) {
            return response()->json([
                'success' => false,
                'message' => 'Documento não pertence a este PI.',
            ], 404);
        }

        if (! $documento->documentoEnviado) {
            return response()->json([
                'success' => false,
                'message' => 'Este documento ainda não foi enviado pelo fornecedor.',
            ], 422);
        }

        $statusAtualNome = $documento->documentoEnviado->status ? $documento->documentoEnviado->status->nome : '';
        $statusPermitidosParaValidacao = ['Pendente', 'Aguardando validação'];

        if (! in_array($statusAtualNome, $statusPermitidosParaValidacao)) {
            return response()->json([
                'success' => false,
                'message' => 'Este documento não está pendente de validação. Status atual: '.$statusAtualNome,
            ], 422);
        }

        $validated = $request->validate([
            'observacoes' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            $docEnviado = DocumentoEnviado::lockForUpdate()->find($documento->documento_enviado_id);

            if (! $docEnviado) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Documento enviado não encontrado.'], 404);
            }

            $statusAtualNomeLock = $docEnviado->status?->nome ?? '';
            if (! in_array($statusAtualNomeLock, ['Pendente', 'Aguardando validação'])) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Este documento já foi validado por outro usuário. Status atual: ' . $statusAtualNomeLock,
                ], 422);
            }

            $statusAprovado = CacheService::statusPorNome('documento', 'Aprovado');
            $statusAtendido = CacheService::statusPorNome('documento', 'Atendido');

            $docEnviado->update([
                'status_id' => $statusAprovado->id,
                'valido' => true,
            ]);

            $documento->update([
                'status_id' => $statusAtendido->id,
            ]);

            ValidacaoDocumento::create([
                'documento_enviado_id' => $documento->documento_enviado_id,
                'operacional_id' => $user->genOp ? $user->genOp->id : null,
                'status_id' => $statusAprovado->id,
                'data_validacao' => now(),
                'observacoes' => $validated['observacoes'] ?? 'Documento aprovado',
            ]);

            $this->propagarStatusParaOutrosPiDocumentos(
                $documento->documento_enviado_id,
                $documento->id,
                $statusAtendido->id,
                $pi->id
            );

            $pi->refresh();
            $pi->atualizarStatusAutomaticamente();

            $pi->invalidarDocumentosVencidos();
            $pi->refresh();

            $todosAprovados = $pi->todosDocumentosAprovados();

            DB::commit();

            Log::channel('docs')->info('Documento aprovado', [
                'pi_id' => $pi->id,
                'pi_numero' => $pi->numero_pi,
                'pi_documento_id' => $documento->id,
                'documento_enviado_id' => $documento->documento_enviado_id,
                'tipo_documento' => $documento->tipoDocumento->nome ?? null,
                'aprovado_por' => $user->id,
                'todos_aprovados' => $todosAprovados,
            ]);

            $mensagem = 'Documento aprovado com sucesso!';
            if ($todosAprovados) {
                $mensagem .= ' Todos os documentos foram validados.';
            }

            return response()->json([
                'success' => true,
                'message' => $mensagem,
                'todos_aprovados' => $todosAprovados,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('docs')->error('Erro ao aprovar documento', [
                'pi_id' => $pi->id,
                'pi_numero' => $pi->numero_pi,
                'documento_id' => $documento->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao aprovar documento: '.$e->getMessage(),
            ], 500);
        }
    }

    private function resolverConversasDoPi(Pi $pi)
    {
        try {
            $conversasAbertas = Conversa::where('pi_id', $pi->id)
                ->whereNull('deleted_at')
                ->whereIn('status_conversa', ['aberta', 'escalada'])
                ->get();

            if ($conversasAbertas->isEmpty()) {
                return;
            }

            foreach ($conversasAbertas as $conversa) {
                $conversa->update(['status_conversa' => 'resolvida']);

                Mensagem::create([
                    'conversa_id' => $conversa->id,
                    'remetente_type' => User::class,
                    'remetente_id' => Auth::id(),
                    'mensagem' => "PI {$pi->numero_pi} foi concluído automaticamente. Todos os documentos foram aprovados. Esta conversa foi encerrada automaticamente.",
                    'lida' => true,
                    'data_leitura' => now(),
                ]);

                $conversa->touch();
            }

        } catch (\Exception $e) {
            Log::channel('pis')->error('Erro ao resolver conversas do PI automaticamente', [
                'pi_id' => $pi->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    private function notificarConclusaoPi(Pi $pi)
    {
        try {
            $pi->load(['fornecedor.user', 'cliente']);

            if (! $pi->fornecedor || ! $pi->fornecedor->user || ! $pi->fornecedor->user->email) {
                return;
            }

            $totalDocumentos = $pi->piDocumentos()->count();

            $dados = [
                'pi_numero' => $pi->numero_pi,
                'cliente_nome' => $pi->cliente ? $pi->cliente->razao_social : 'N/A',
                'fornecedor_nome' => $pi->fornecedor->razao_social,
                'data_conclusao' => $pi->data_conclusao ? $pi->data_conclusao->format('d/m/Y H:i') : now()->format('d/m/Y H:i'),
                'total_documentos' => $totalDocumentos,
                'url_pi' => route('pis.enviar_documentos', ['pi' => $pi->id]),
            ];

            MailRotator::send(
                new NotificacaoConclusaoPiMail($dados),
                $pi->fornecedor->user->email
            );

            if ($pi->fornecedor_id) {
                enviar_para_representantes(new NotificacaoConclusaoPiMail($dados), $pi->fornecedor_id);
            }
        } catch (\Exception $e) {
            Log::channel('pis')->error('Erro ao enviar email de conclusão do PI', [
                'pi_id' => $pi->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function reprovarDocumento(Request $request, Pi $pi, PiDocumento $documento)
    {
        $user = Auth::user();

        if (! $user->hasRole('admin') && ! $user->genOp) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário sem perfil de gerencial/operacional vinculado.',
            ], 403);
        }

        if ($user->hasRole('operacional')) {
            $temAcesso = $user->genOp && $pi->operacionalTemAcesso($user->genOp->id);
            if (! $temAcesso) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para reprovar documentos deste PI.',
                ], 403);
            }
        }

        if ($pi->concluido) {
            return response()->json([
                'success' => false,
                'message' => 'Este PI já foi concluído. Não é possível validar documentos.',
            ], 422);
        }

        if ($documento->pi_id !== $pi->id) {
            return response()->json([
                'success' => false,
                'message' => 'Documento não pertence a este PI.',
            ], 404);
        }

        if (! $documento->documentoEnviado) {
            return response()->json([
                'success' => false,
                'message' => 'Este documento ainda não foi enviado pelo fornecedor.',
            ], 422);
        }

        $statusAtualNome = $documento->documentoEnviado->status ? $documento->documentoEnviado->status->nome : '';
        $statusPermitidosParaValidacao = ['Pendente', 'Aguardando validação'];

        if (! in_array($statusAtualNome, $statusPermitidosParaValidacao)) {
            return response()->json([
                'success' => false,
                'message' => 'Este documento não está pendente de validação. Status atual: '.$statusAtualNome,
            ], 422);
        }

        $validated = $request->validate([
            'observacoes' => 'required|string|max:1000',
        ], [
            'observacoes.required' => 'Informe o motivo da reprovação',
            'observacoes.max' => 'O motivo não pode exceder 1000 caracteres',
        ]);

        DB::beginTransaction();
        try {
            $statusReprovado = CacheService::statusPorNome('documento', 'Reprovado');

            $documento->documentoEnviado->update([
                'status_id' => $statusReprovado->id,
                'valido' => false,
            ]);

            $documento->update([
                'status_id' => $statusReprovado->id,
            ]);

            ValidacaoDocumento::create([
                'documento_enviado_id' => $documento->documento_enviado_id,
                'operacional_id' => $user->genOp ? $user->genOp->id : null,
                'status_id' => $statusReprovado->id,
                'data_validacao' => now(),
                'observacoes' => $validated['observacoes'],
            ]);

            $this->propagarStatusParaOutrosPiDocumentos(
                $documento->documento_enviado_id,
                $documento->id,
                $statusReprovado->id,
                $pi->id
            );

            $fornecedor = $pi->fornecedor;
            if ($fornecedor && $fornecedor->user) {
                $conversa = Conversa::whereNull('deleted_at')
                    ->where('pi_id', $pi->id)
                    ->where('status_conversa', '!=', 'resolvida')
                    ->whereHas('participantes', function ($q) use ($user) {
                        $q->where('participante_type', User::class)
                            ->where('participante_id', $user->id)
                            ->whereNull('data_saida');
                    })
                    ->whereHas('participantes', function ($q) use ($fornecedor) {
                        $q->where('participante_type', User::class)
                            ->where('participante_id', $fornecedor->user->id)
                            ->whereNull('data_saida');
                    })
                    ->lockForUpdate()
                    ->first();

                if (! $conversa) {
                    $conversa = Conversa::create([
                        'assunto' => 'PI: '.$pi->numero_pi.' - '.($pi->cliente ? $pi->cliente->razao_social : 'Cliente não definido'),
                        'pi_id' => $pi->id,
                        'nivel_atual' => 'operacional',
                        'status_conversa' => 'aberta',
                        'criador_type' => User::class,
                        'criador_id' => $user->id,
                    ]);

                    ConversaParticipante::create([
                        'conversa_id' => $conversa->id,
                        'participante_type' => User::class,
                        'participante_id' => $user->id,
                        'data_entrada' => now(),
                        'nivel_participante' => 'operacional',
                    ]);

                    ConversaParticipante::create([
                        'conversa_id' => $conversa->id,
                        'participante_type' => User::class,
                        'participante_id' => $fornecedor->user->id,
                        'data_entrada' => now(),
                        'nivel_participante' => 'fornecedor',
                    ]);
                }

                Mensagem::create([
                    'conversa_id' => $conversa->id,
                    'remetente_type' => User::class,
                    'remetente_id' => $user->id,
                    'mensagem' => "Documento Reprovado\n\n".
                                "Documento: {$documento->tipoDocumento->nome}\n\n".
                                "Motivo: {$validated['observacoes']}\n\n".
                                'Por favor, corrija e reenvie o documento.',
                    'lida' => false,
                ]);

                $conversa->touch();

                $chaveCache = "notificacoes_validacao_pi_{$pi->id}";
                $dadosCache = Cache::get($chaveCache, [
                    'pi_id' => $pi->id,
                    'pi_numero' => $pi->numero_pi,
                    'fornecedor_id' => $pi->fornecedor_id,
                    'fornecedor_email' => $fornecedor->user->email ?? null,
                    'fornecedor_nome' => $fornecedor->razao_social,
                    'cliente_nome' => $pi->cliente ? $pi->cliente->razao_social : 'N/A',
                    'documentos_acao' => [],
                    'email_enviado' => false,
                ]);

                $dadosCache['documentos_acao'][] = [
                    'tipo_documento_nome' => $documento->tipoDocumento->nome,
                    'acao' => 'reprovado',
                    'motivo' => $validated['observacoes'],
                    'validador_nome' => $user->name,
                    'data' => now()->toDateTimeString(),
                ];

                Cache::put($chaveCache, $dadosCache, now()->addMinutes(60));
            }

            $pi->refresh();
            $pi->atualizarStatusAutomaticamente();

            DB::commit();

            Log::channel('docs')->info('Documento reprovado', [
                'pi_id' => $pi->id,
                'pi_numero' => $pi->numero_pi,
                'pi_documento_id' => $documento->id,
                'documento_enviado_id' => $documento->documento_enviado_id,
                'tipo_documento' => $documento->tipoDocumento->nome ?? null,
                'reprovado_por' => $user->id,
                'motivo' => $validated['observacoes'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Documento reprovado e fornecedor notificado!',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('docs')->error('Erro ao reprovar documento', [
                'pi_id' => $pi->id,
                'pi_numero' => $pi->numero_pi,
                'documento_id' => $documento->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao reprovar documento: '.$e->getMessage(),
            ], 500);
        }
    }

    public function solicitarReenvioDocumento(Request $request, Pi $pi, PiDocumento $documento)
    {
        $user = Auth::user();

        if (! $user->hasRole('admin') && ! $user->genOp) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário sem nivel necessário.',
            ], 403);
        }

        if ($user->hasRole('operacional')) {
            $temAcesso = $user->genOp && $pi->operacionalTemAcesso($user->genOp->id);
            if (! $temAcesso) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para solicitar reenvio de documentos deste PI.',
                ], 403);
            }
        }

        if ($pi->concluido) {
            return response()->json([
                'success' => false,
                'message' => 'Este PI já foi concluído. Não é possível validar documentos.',
            ], 422);
        }

        if ($documento->pi_id !== $pi->id) {
            return response()->json([
                'success' => false,
                'message' => 'Documento não pertence a este PI.',
            ], 404);
        }

        if (! $documento->documentoEnviado) {
            return response()->json([
                'success' => false,
                'message' => 'Este documento ainda não foi enviado pelo fornecedor.',
            ], 422);
        }

        $statusAtualNome = $documento->documentoEnviado->status ? $documento->documentoEnviado->status->nome : '';
        $statusPermitidosParaValidacao = ['Pendente', 'Aguardando validação'];

        if (! in_array($statusAtualNome, $statusPermitidosParaValidacao)) {
            return response()->json([
                'success' => false,
                'message' => 'Este documento não está pendente de validação. Status atual: '.$statusAtualNome,
            ], 422);
        }

        $validated = $request->validate([
            'observacoes' => 'required|string|max:1000',
        ], [
            'observacoes.required' => 'Informe o motivo da solicitação de reenvio',
            'observacoes.max' => 'O motivo não pode exceder 1000 caracteres',
        ]);

        DB::beginTransaction();
        try {
            $statusReenvio = CacheService::statusPorNome('documento', 'Aguardando Reenvio');

            $documento->documentoEnviado->update([
                'status_id' => $statusReenvio->id,
                'valido' => false,
            ]);

            $documento->update([
                'status_id' => $statusReenvio->id,
            ]);

            ValidacaoDocumento::create([
                'documento_enviado_id' => $documento->documento_enviado_id,
                'operacional_id' => $user->genOp ? $user->genOp->id : null,
                'status_id' => $statusReenvio->id,
                'data_validacao' => now(),
                'observacoes' => $validated['observacoes'],
            ]);

            $this->propagarStatusParaOutrosPiDocumentos(
                $documento->documento_enviado_id,
                $documento->id,
                $statusReenvio->id,
                $pi->id
            );

            $fornecedor = $pi->fornecedor;
            if ($fornecedor && $fornecedor->user) {
                $conversa = Conversa::whereNull('deleted_at')
                    ->where('pi_id', $pi->id)
                    ->where('status_conversa', '!=', 'resolvida')
                    ->whereHas('participantes', function ($q) use ($user) {
                        $q->where('participante_type', User::class)
                            ->where('participante_id', $user->id)
                            ->whereNull('data_saida');
                    })
                    ->whereHas('participantes', function ($q) use ($fornecedor) {
                        $q->where('participante_type', User::class)
                            ->where('participante_id', $fornecedor->user->id)
                            ->whereNull('data_saida');
                    })
                    ->lockForUpdate()
                    ->first();

                if (! $conversa) {
                    $conversa = Conversa::create([
                        'assunto' => 'PI: '.$pi->numero_pi.' - '.($pi->cliente ? $pi->cliente->razao_social : 'Cliente não definido'),
                        'pi_id' => $pi->id,
                        'nivel_atual' => 'operacional',
                        'status_conversa' => 'aberta',
                        'criador_type' => User::class,
                        'criador_id' => $user->id,
                    ]);

                    ConversaParticipante::create([
                        'conversa_id' => $conversa->id,
                        'participante_type' => User::class,
                        'participante_id' => $user->id,
                        'data_entrada' => now(),
                        'nivel_participante' => 'operacional',
                    ]);

                    ConversaParticipante::create([
                        'conversa_id' => $conversa->id,
                        'participante_type' => User::class,
                        'participante_id' => $fornecedor->user->id,
                        'data_entrada' => now(),
                        'nivel_participante' => 'fornecedor',
                    ]);
                }

                Mensagem::create([
                    'conversa_id' => $conversa->id,
                    'remetente_type' => User::class,
                    'remetente_id' => $user->id,
                    'mensagem' => "Solicitação de Reenvio\n\n".
                                "Documento: {$documento->tipoDocumento->nome}\n\n".
                                "Motivo: {$validated['observacoes']}\n\n".
                                'Por favor, envie uma nova versão do documento.',
                    'lida' => false,
                ]);

                $conversa->touch();

                $chaveCache = "notificacoes_validacao_pi_{$pi->id}";
                $dadosCache = Cache::get($chaveCache, [
                    'pi_id' => $pi->id,
                    'pi_numero' => $pi->numero_pi,
                    'fornecedor_id' => $pi->fornecedor_id,
                    'fornecedor_email' => $fornecedor->user->email ?? null,
                    'fornecedor_nome' => $fornecedor->razao_social,
                    'cliente_nome' => $pi->cliente ? $pi->cliente->razao_social : 'N/A',
                    'documentos_acao' => [],
                    'email_enviado' => false,
                ]);

                $dadosCache['documentos_acao'][] = [
                    'tipo_documento_nome' => $documento->tipoDocumento->nome,
                    'acao' => 'reenvio_solicitado',
                    'motivo' => $validated['observacoes'],
                    'validador_nome' => $user->name,
                    'data' => now()->toDateTimeString(),
                ];

                Cache::put($chaveCache, $dadosCache, now()->addMinutes(60));
            }

            $pi->refresh();
            $pi->atualizarStatusAutomaticamente();

            DB::commit();

            Log::channel('docs')->info('Reenvio solicitado', [
                'pi_id' => $pi->id,
                'pi_numero' => $pi->numero_pi,
                'pi_documento_id' => $documento->id,
                'documento_enviado_id' => $documento->documento_enviado_id,
                'tipo_documento' => $documento->tipoDocumento->nome ?? null,
                'solicitado_por' => $user->id,
                'motivo' => $validated['observacoes'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Reenvio solicitado e fornecedor notificado!',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('docs')->error('Erro ao solicitar reenvio', [
                'pi_id' => $pi->id,
                'pi_numero' => $pi->numero_pi,
                'documento_id' => $documento->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao solicitar reenvio: '.$e->getMessage(),
            ], 500);
        }
    }

    public function historicoValidacoesDocumento(Pi $pi, PiDocumento $documento)
    {
        $user = Auth::user();

        if ($user->hasRole('operacional')) {
            $temAcesso = $user->genOp && $pi->operacionalTemAcesso($user->genOp->id);
            if (! $temAcesso) {
                abort(403, 'Você não tem permissão para ver o histórico deste documento.');
            }
        }

        if ($documento->pi_id !== $pi->id) {
            abort(404, 'Documento não pertence a este PI.');
        }

        try {
            if (! $documento->documentoEnviado) {
                return response()->json([
                    'success' => true,
                    'validacoes' => [],
                ]);
            }

            $validacoes = $documento->documentoEnviado->validacoes()->with(['operacional.user', 'status'])->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'validacoes' => $validacoes,
            ]);
        } catch (\Exception $e) {
            Log::channel('docs')->error('Erro ao buscar histórico de validações', [
                'pi_id' => $pi->id,
                'documento_id' => $documento->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar histórico: '.$e->getMessage(),
            ], 500);
        }
    }

    private function copiarDocumentosDoCliente(Pi $pi)
    {
        if (! $pi->cliente_id) {
            Log::channel('pis')->warning('PI sem cliente vinculado', ['pi_id' => $pi->id]);

            return false;
        }

        $cliente = Cliente::find($pi->cliente_id);
        if (! $cliente) {
            Log::channel('pis')->warning('Cliente nao encontrado', ['cliente_id' => $pi->cliente_id]);

            return false;
        }

        $statusPendente = CacheService::statusPorNome('documento', 'Pendente');
        if (! $statusPendente) {
            Log::channel('pis')->error('Status Pendente nao encontrado');

            return false;
        }

        $documentosCriados = 0;
        $tiposDocumentoIds = [];
        $opcionaisMapa = [];

        $exigeVeiculo = $cliente->exige_documento_por_veiculo;
        $temTipoVeiculo = ! empty($pi->tipo_veiculo_id);

        if ($exigeVeiculo && $temTipoVeiculo) {

            $tiposDocumentoIdsBruto = DB::table('tipo_documento_veiculo')
                ->where('tipo_veiculo_id', $pi->tipo_veiculo_id)
                ->pluck('tipo_documento_id')
                ->toArray();

            if (! empty($tiposDocumentoIdsBruto)) {
                $documentosAtivos = TipoDocumento::whereIn('id', $tiposDocumentoIdsBruto)
                    ->where('ativo', true)
                    ->where('avulso', false)
                    ->get();

                $tiposDocumentoIds = $documentosAtivos->pluck('id')->toArray();
            }

        } else {
            $docsCliente = ClienteDocumentoRequerido::where('cliente_id', $pi->cliente_id)
                ->whereHas('tipoDocumento', function ($q) {
                    $q->where('ativo', true)
                        ->where('avulso', false);
                })
                ->get();

            $tiposDocumentoIds = $docsCliente->pluck('tipo_documento_id')->toArray();
            $opcionaisMapa = $docsCliente->pluck('opcional', 'tipo_documento_id')->toArray();
        }

        if (empty($tiposDocumentoIds)) {
            Log::channel('pis')->warning('Nenhum documento encontrado para copiar', [
                'pi_id' => $pi->id,
                'cliente_id' => $pi->cliente_id,
                'exige_por_veiculo' => $exigeVeiculo,
                'tipo_veiculo_id' => $pi->tipo_veiculo_id,
            ]);

            return false;
        }

        foreach ($tiposDocumentoIds as $tipoDocumentoId) {
            $ehOpcional = $opcionaisMapa[$tipoDocumentoId] ?? false;

            $piDocumento = PiDocumento::firstOrCreate([
                'pi_id' => $pi->id,
                'tipo_documento_id' => $tipoDocumentoId,
            ], [
                'documento_enviado_id' => null,
                'status_id' => $statusPendente->id,
                'opcional' => $ehOpcional,
            ]);

            if ($piDocumento->wasRecentlyCreated) {
                $documentosCriados++;
            }
        }

        return true;
    }

    private function vincularDocumentosDoFornecedor(Pi $pi)
    {
        if (! $pi->fornecedor_id) {
            Log::channel('docs')->warning('PI sem fornecedor vinculado', ['pi_id' => $pi->id]);

            return false;
        }

        $documentosPendentes = $pi->piDocumentos()
            ->whereNull('documento_enviado_id')
            ->with('tipoDocumento')
            ->get();

        if ($documentosPendentes->isEmpty()) {
            return false;
        }

        $statusAtendido = CacheService::statusPorNome('documento', 'Atendido');

        if (! $statusAtendido) {
            Log::channel('docs')->error('Status Atendido não encontrado');

            return false;
        }

        $documentosVinculados = 0;
        $documentosIgnorados = 0;

        foreach ($documentosPendentes as $piDoc) {
            if (! $piDoc->tipoDocumento->validar) {
                $documentosIgnorados++;

                continue;
            }

            $docValido = DocumentoEnviado::where('fornecedor_id', $pi->fornecedor_id)
                ->where('tipo_documento_id', $piDoc->tipo_documento_id)
                ->where('valido', true)
                ->where(function ($q) {
                    $q->where('data_validade_documento', '>=', now()->toDateString())
                        ->orWhereNull('data_validade_documento');
                })
                ->whereHas('status', function ($q) {
                    $q->where('nome', 'Aprovado');
                })
                ->orderByRaw('data_validade_documento IS NULL, data_validade_documento DESC')
                ->first();

            if ($docValido) {
                $piDoc->update([
                    'documento_enviado_id' => $docValido->id,
                    'status_id' => $statusAtendido->id,
                ]);
                $documentosVinculados++;

            }
        }

        return true;
    }

    private function propagarStatusParaOutrosPiDocumentos(int $documentoEnviadoId, int $piDocumentoAtualId, int $novoStatusId, ?int $piAtualId = null): array
    {
        $outrosPiDocs = PiDocumento::where('documento_enviado_id', $documentoEnviadoId)
            ->where('id', '!=', $piDocumentoAtualId)
            ->get();

        if ($outrosPiDocs->isEmpty()) {
            return [];
        }

        $pisAfetados = [];

        foreach ($outrosPiDocs as $outroPiDoc) {
            $outroPiDoc->update(['status_id' => $novoStatusId]);

            if ($outroPiDoc->pi_id && $outroPiDoc->pi_id !== $piAtualId) {
                $pisAfetados[$outroPiDoc->pi_id] = true;
            }
        }

        foreach (array_keys($pisAfetados) as $piId) {
            $piAfetado = Pi::find($piId);
            if ($piAfetado) {
                $piAfetado->atualizarStatusAutomaticamente();
            }
        }

        Log::channel('docs')->info('Propagação cross-PI executada', [
            'documento_enviado_id' => $documentoEnviadoId,
            'pi_documento_origem_id' => $piDocumentoAtualId,
            'novo_status_id' => $novoStatusId,
            'pis_afetados' => array_keys($pisAfetados),
            'pi_docs_atualizados' => $outrosPiDocs->pluck('id')->toArray(),
        ]);

        return array_keys($pisAfetados);
    }

    private function getPrivateFilePath(string $relativePath): string
    {
        return storage_path('app/'.$relativePath);
    }

    private function convertMoneyFields(Request $request)
    {
        $moneyFields = ['total_bruto', 'comissao', 'liquido', 'custo_unitario', 'custo_total'];

        foreach ($moneyFields as $field) {
            if ($request->has($field)) {
                $value = $request->input($field);

                if (empty($value) || trim($value) === '' || $value === 'R$ 0,00') {
                    $request->merge([$field => null]);

                    continue;
                }

                $value = preg_replace('/[^\d,]/', '', $value);
                $value = str_replace(',', '.', $value);
                $request->merge([$field => $value]);
            }
        }
    }

    private function convertPercentField(Request $request)
    {
        if ($request->has('desconto')) {
            $value = $request->input('desconto');

            if (empty($value) || trim($value) === '' || $value === '0%') {
                $request->merge(['desconto' => null]);

                return;
            }

            $value = preg_replace('/[^\d,.]/', '', $value);
            $value = str_replace(',', '.', $value);
            $request->merge(['desconto' => $value]);
        }
    }

    private function convertDaysField(Request $request)
    {
        if ($request->has('prazo_pagamento')) {
            $value = $request->input('prazo_pagamento');

            if (empty($value) || trim($value) === '') {
                $request->merge(['prazo_pagamento' => null]);

                return;
            }

            $value = preg_replace('/\D/', '', $value);

            if (! empty($value)) {
                $request->merge(['prazo_pagamento' => $value.' dias']);
            } else {
                $request->merge(['prazo_pagamento' => null]);
            }
        }
    }

    private function prepareValoresData(Request $request)
    {
        $data = [];

        $fields = [
            'total_bruto',
            'comissao',
            'liquido',
            'custo_unitario',
            'desconto',
            'custo_total',
            'local_cobranca',
            'prazo_pagamento',
        ];

        $hasData = false;

        foreach ($fields as $field) {
            if ($request->filled($field)) {
                $data[$field] = $request->input($field);
                $hasData = true;
            } else {
                $data[$field] = in_array($field, ['local_cobranca', 'prazo_pagamento']) ? null : 0;
            }
        }

        return $hasData ? $data : [];
    }

    private function periodoParaInput($periodo)
    {
        if (empty($periodo)) {
            return date('Y-m');
        }

        $mesesMap = [
            'Janeiro' => '01',
            'Fevereiro' => '02',
            'Março' => '03',
            'Abril' => '04',
            'Maio' => '05',
            'Junho' => '06',
            'Julho' => '07',
            'Agosto' => '08',
            'Setembro' => '09',
            'Outubro' => '10',
            'Novembro' => '11',
            'Dezembro' => '12',
        ];

        foreach ($mesesMap as $mesNome => $mesNum) {
            if (stripos($periodo, $mesNome) !== false) {
                $ano = preg_replace('/[^0-9]/', '', $periodo);

                return $ano.'-'.$mesNum;
            }
        }

        return date('Y-m');
    }

    private function validarArquivo($file)
    {

        $extensao = strtolower($file->getClientOriginalExtension());

        if ($extensao !== 'pdf') {
            Log::channel('security')->warning('Upload rejeitado: extensão inválida', ['extensao' => $extensao]);

            return [
                'valido' => false,
                'mensagem' => 'Apenas arquivos PDF são permitidos.',
            ];
        }

        $maxSize = 45 * 1024 * 1024;
        $tamanhoMB = round($file->getSize() / 1048576, 2);

        if ($file->getSize() > $maxSize) {
            Log::channel('security')->warning('Upload rejeitado: arquivo muito grande', [
                'tamanho_mb' => $tamanhoMB,
            ]);

            return [
                'valido' => false,
                'mensagem' => 'O arquivo não pode exceder 45MB.',
            ];
        }

        $isValid = $file->isValid();

        if (! $isValid) {
            Log::channel('security')->warning('Upload rejeitado: arquivo corrompido ou inválido', [
                'erro' => $file->getError(),
                'error_message' => $file->getErrorMessage(),
            ]);

            return [
                'valido' => false,
                'mensagem' => 'Arquivo inválido ou corrompido.',
            ];
        }

        $mimeTypeServidor = $file->getMimeType();
        $mimeTypeCliente = $file->getClientMimeType();

        $mimeTypesValidos = [
            'application/pdf',
            'application/x-pdf',
            'application/acrobat',
            'application/vnd.pdf',
            'text/pdf',
            'text/x-pdf',
            'application/octet-stream',
        ];

        $mimeValido = in_array($mimeTypeCliente, $mimeTypesValidos) ||
                      in_array($mimeTypeServidor, $mimeTypesValidos);

        if (! $mimeValido) {
            Log::channel('security')->warning('Upload rejeitado: MIME type inválido', [
                'mime_servidor' => $mimeTypeServidor,
                'mime_cliente' => $mimeTypeCliente,
                'arquivo' => $file->getClientOriginalName(),
            ]);

            return [
                'valido' => false,
                'mensagem' => 'O arquivo não é um PDF válido.',
            ];
        }

        return ['valido' => true];
    }

    private function handleDocumentUpload($file)
    {
        try {
            if (! $file->isValid()) {
                Log::channel('security')->error('Arquivo inválido no handleDocumentUpload', [
                    'erro' => $file->getError(),
                    'mensagem' => $file->getErrorMessage(),
                ]);
                throw new \Exception('Arquivo inválido: '.$file->getErrorMessage());
            }

            $directory = storage_path('app/private/pis_documentos');

            if (! file_exists($directory)) {
                if (! mkdir($directory, 0755, true)) {
                    Log::channel('docs')->error('Falha ao criar diretório', ['path' => $directory]);
                    throw new \Exception('não foi possível criar o diretório : '.$directory);
                }
            }

            if (! is_writable($directory)) {
                Log::channel('docs')->error('Diretório sem permissão de escrita', [
                    'path' => $directory,
                    'permissions' => substr(sprintf('%o', fileperms($directory)), -4),
                ]);
                throw new \Exception('Diretório não tem permissão de escrita: '.$directory);
            }

            $fileName = 'PI_'.uniqid().'_'.time().'.pdf';
            $fullPath = $directory.'/'.$fileName;

            $saved = $file->move($directory, $fileName);

            if (! $saved) {
                Log::channel('docs')->error('Falha ao mover arquivo - move() retornou false');
                throw new \Exception('Falha ao mover arquivo para o destino');
            }

            if (! file_exists($fullPath)) {
                Log::channel('docs')->error('Arquivo não existe após move()', [
                    'path' => $fullPath,
                    'directory_exists' => file_exists($directory),
                    'directory_writable' => is_writable($directory),
                ]);
                throw new \Exception('Arquivo não foi encontrado após o upload');
            }

            $relativePath = 'private/pis_documentos/'.$fileName;

            return $relativePath;

        } catch (\Exception $e) {
            Log::channel('docs')->error('Erro no upload do documento', [
                'erro' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new \Exception('Erro ao salvar o documento: '.$e->getMessage());
        }
    }

    private function buscarDataNoBinarioPdf($file, $dataInformada)
    {
        $conteudo = file_get_contents($file->getRealPath());

        if ($conteudo === false) {
            return ['encontrada' => false];
        }

        $formatos = [
            $dataInformada->format('d/m/Y'),
            $dataInformada->format('d-m-Y'),
            $dataInformada->format('d.m.Y'),
            $dataInformada->format('d / m / Y'),
            $dataInformada->format('d/m/y'),
            $dataInformada->format('dmY'),
            $dataInformada->format('Y-m-d'),
        ];

        foreach ($formatos as $formato) {
            if (strpos($conteudo, $formato) !== false) {
                return ['encontrada' => true, 'formato' => $formato];
            }
        }

        $dia = $dataInformada->format('d');
        $ano = $dataInformada->format('Y');
        $mesesExtenso = [
            '01' => ['janeiro', 'jan'],
            '02' => ['fevereiro', 'fev'],
            '03' => ['março', 'mar'],
            '04' => ['abril', 'abr'],
            '05' => ['maio', 'mai'],
            '06' => ['junho', 'jun'],
            '07' => ['julho', 'jul'],
            '08' => ['agosto', 'ago'],
            '09' => ['setembro', 'set'],
            '10' => ['outubro', 'out'],
            '11' => ['novembro', 'nov'],
            '12' => ['dezembro', 'dez'],
        ];

        $mes = $dataInformada->format('m');
        foreach ($mesesExtenso[$mes] as $mesNome) {
            $dataExtenso = $dia.' de '.$mesNome.' de '.$ano;
            if (stripos($conteudo, $dataExtenso) !== false) {

                return ['encontrada' => true, 'formato' => $dataExtenso];
            }
        }

        return ['encontrada' => false];
    }

    private function extrairEValidarTextoPdf($file, $dataInformada)
    {
        $textoExtraido = '';

        try {
            $parser = new \Smalot\PdfParser\Parser;
            $pdf = $parser->parseFile($file->getRealPath());
            $textoExtraido = $pdf->getText();
        } catch (\Exception $e) {
            Log::channel('docs')->error('Smalot PdfParser falhou', ['error' => $e->getMessage()]);
        }

        $conteudo = file_get_contents($file->getRealPath());

        if (preg_match_all('/\(([^\)]+)\)/', $conteudo, $matches)) {
            $textoExtraido .= ' '.implode(' ', $matches[1]);
        }

        if (preg_match_all('/stream\s+(.*?)\s+endstream/s', $conteudo, $streams)) {
            foreach ($streams[1] as $stream) {
                if (function_exists('gzuncompress')) {
                    $decoded = @gzuncompress($stream);
                    if ($decoded !== false) {
                        if (preg_match_all('/\(([^\)]+)\)/', $decoded, $streamMatches)) {
                            $textoExtraido .= ' '.implode(' ', $streamMatches[1]);
                        }
                    }
                }
            }
        }

        $textoLimpo = $this->limparTextoPdf($textoExtraido);

        $razaoTextoReal = $this->calcularRazaoTextoReal($textoLimpo);

        if ($razaoTextoReal < 0.3) {
            return [
                'encontrada' => false,
                'eh_imagem_escaneada' => true,
            ];
        }

        $formatos = [
            $dataInformada->format('d/m/Y'),
            $dataInformada->format('d-m-Y'),
            $dataInformada->format('d.m.Y'),
            $dataInformada->format('d / m / Y'),
            $dataInformada->format('d/m/y'),
            $dataInformada->format('Y-m-d'),
        ];

        foreach ($formatos as $formato) {
            if (stripos($textoLimpo, $formato) !== false) {
                return ['encontrada' => true, 'formato' => $formato, 'eh_imagem_escaneada' => false];
            }
        }

        $dia = $dataInformada->format('d');
        $mes = $dataInformada->format('m');
        $ano = $dataInformada->format('Y');

        $padroes = [
            "/{$dia}.{0,20}{$mes}.{0,20}{$ano}/",
            "/\b{$dia}[\s\/\-\.]+{$mes}[\s\/\-\.]+{$ano}\b/i",
        ];

        foreach ($padroes as $padrao) {
            if (preg_match($padrao, $textoLimpo, $matches)) {
                return ['encontrada' => true, 'formato' => trim($matches[0]), 'eh_imagem_escaneada' => false];
            }
        }

        return ['encontrada' => false, 'eh_imagem_escaneada' => false];
    }

    private function limparTextoPdf($texto)
    {
        $textoLimpo = preg_replace('/[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F]/', '', $texto);
        $textoLimpo = str_replace(["\r\n", "\r"], "\n", $textoLimpo);
        $textoLimpo = preg_replace('/[ \t]+/', ' ', $textoLimpo);
        $textoLimpo = preg_replace('/\n+/', "\n", $textoLimpo);

        if (function_exists('mb_check_encoding') && ! mb_check_encoding($textoLimpo, 'UTF-8')) {
            $encoding = mb_detect_encoding($textoLimpo, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
            if ($encoding && $encoding !== 'UTF-8') {
                $textoLimpo = mb_convert_encoding($textoLimpo, 'UTF-8', $encoding);
            }
        }

        return trim($textoLimpo);
    }

    private function calcularRazaoTextoReal($texto)
    {
        if (empty($texto)) {
            return 0;
        }

        $totalChars = strlen($texto);
        $letrasEspacos = preg_match_all('/[a-zA-Z\xC0-\xFF\s]/', $texto);

        return round($letrasEspacos / $totalChars, 2);
    }

    public function adicionarDocumentoAvulso(Request $request, Pi $pi)
    {
        $user = Auth::user();

        if ($user->hasRole('fornecedor')) {
            if (! $user->fornecedor || $pi->fornecedor_id !== $user->fornecedor->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para adicionar documentos neste PI.',
                ], 403);
            }
        }

        if ($user->hasRole('representante')) {
            if (! $this->representanteTemAcessoAoPi($user, $pi)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para adicionar documentos neste PI.',
                ], 403);
            }
        }

        if ($user->hasRole('operacional')) {
            $temAcesso = $user->genOp && $pi->operacionalTemAcesso($user->genOp->id);
            if (! $temAcesso) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para adicionar documentos neste PI.',
                ], 403);
            }
        }

        $validated = $request->validate([
            'tipo_documento_id' => 'required|exists:tipo_documentos,id',
            'observacoes' => 'nullable|string|max:1000',
        ], [
            'tipo_documento_id.required' => 'Selecione um tipo de documento.',
            'tipo_documento_id.exists' => 'Tipo de documento inválido.',
        ]);

        $tipoDocumento = TipoDocumento::find($validated['tipo_documento_id']);
        if (! $tipoDocumento->avulso) {
            return response()->json([
                'success' => false,
                'message' => 'Este tipo de documento não é avulso. Documentos regulares devem ser configurados no cliente.',
            ], 422);
        }

        $jaExiste = PiDocumento::where('pi_id', $pi->id)
            ->where('tipo_documento_id', $validated['tipo_documento_id'])
            ->whereNull('deleted_at')
            ->exists();

        if ($jaExiste) {
            return response()->json([
                'success' => false,
                'message' => 'Este tipo de documento já existe neste PI.',
            ], 422);
        }

        $statusPendente = CacheService::statusPorNome('documento', 'Pendente');

        if (! $statusPendente) {
            return response()->json([
                'success' => false,
                'message' => 'Status Pendente não encontrado no sistema.',
            ], 500);
        }

        try {
            $piDocumento = PiDocumento::create([
                'pi_id' => $pi->id,
                'tipo_documento_id' => $validated['tipo_documento_id'],
                'documento_enviado_id' => null,
                'status_id' => $statusPendente->id,
                'observacoes' => $validated['observacoes'] ?? null,
            ]);

            $fornecedor = $pi->fornecedor;
            if ($fornecedor && $fornecedor->user) {
                $chaveCache = "notificacao_avulso_pi_{$pi->id}";
                $dadosCache = Cache::get($chaveCache, [
                    'pi_id' => $pi->id,
                    'pi_numero' => $pi->numero_pi,
                    'fornecedor_id' => $pi->fornecedor_id,
                    'fornecedor_email' => $fornecedor->user->email ?? null,
                    'fornecedor_nome' => $fornecedor->razao_social,
                    'cliente_nome' => $pi->cliente ? $pi->cliente->razao_social : 'N/A',
                    'solicitante_nome' => $user->name,
                    'documentos_avulsos' => [],
                    'email_enviado' => false,
                ]);

                $dadosCache['documentos_avulsos'][] = [
                    'tipo_documento_id' => $tipoDocumento->id,
                    'tipo_documento_nome' => $tipoDocumento->nome,
                    'descricao' => $tipoDocumento->descricao,
                    'observacoes' => $validated['observacoes'] ?? null,
                    'data_adicao' => now()->toDateTimeString(),
                    'adicionado_por' => $user->name,
                ];

                Cache::put($chaveCache, $dadosCache, now()->addMinutes(60));
            }

            $pi->refresh();
            $pi->atualizarStatusAutomaticamente();

            return response()->json([
                'success' => true,
                'message' => 'Documento avulso adicionado com sucesso!',
                'pi_documento' => $piDocumento->load('tipoDocumento', 'status'),
            ]);
        } catch (\Exception $e) {
            Log::channel('docs')->error('Erro ao adicionar documento avulso', [
                'pi_id' => $pi->id,
                'tipo_documento_id' => $validated['tipo_documento_id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao adicionar documento avulso: '.$e->getMessage(),
            ], 500);
        }
    }

    public function getTiposDocumentosAvulsos(Pi $pi)
    {
        $user = Auth::user();

        if ($user->hasRole('fornecedor')) {
            if (! $user->fornecedor || $pi->fornecedor_id !== $user->fornecedor->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para esta ação.',
                ], 403);
            }
        }

        if ($user->hasRole('representante')) {
            if (! $this->representanteTemAcessoAoPi($user, $pi)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para esta ação.',
                ], 403);
            }
        }

        if ($user->hasRole('operacional')) {
            $temAcesso = $user->genOp && $pi->operacionalTemAcesso($user->genOp->id);
            if (! $temAcesso) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para esta ação.',
                ], 403);
            }
        }

        $tiposDisponiveis = CacheService::tiposDocumentoAtivos()
            ->where('avulso', true)
            ->values()
            ->map(function ($tipo) {
                return [
                    'id' => $tipo->id,
                    'nome' => $tipo->nome,
                    'descricao' => $tipo->descricao,
                    'validade_dias' => $tipo->validade_dias,
                    'validar' => $tipo->validar,
                ];
            });

        return response()->json([
            'success' => true,
            'tipos_documentos' => $tiposDisponiveis,
        ]);
    }

    public function removerDocumentoAvulso(Request $request, Pi $pi, PiDocumento $piDocumento)
    {
        $user = Auth::user();

        if ($user->hasRole('fornecedor')) {
            if (! $user->fornecedor || $pi->fornecedor_id !== $user->fornecedor->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para remover documentos deste PI.',
                ], 403);
            }
        } elseif ($user->hasRole('representante')) {
            if (! $this->representanteTemAcessoAoPi($user, $pi)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para remover documentos deste PI.',
                ], 403);
            }
        } elseif ($user->hasRole('operacional')) {
            $temAcesso = $user->genOp && $pi->operacionalTemAcesso($user->genOp->id);
            if (! $temAcesso) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para remover documentos deste PI.',
                ], 403);
            }
        } elseif (! $user->hasAnyRole(['admin', 'gerencial'])) {
            return response()->json([
                'success' => false,
                'message' => 'Você não tem permissão para remover documentos.',
            ], 403);
        }

        if ($piDocumento->pi_id !== $pi->id) {
            return response()->json([
                'success' => false,
                'message' => 'Documento não pertence a esta PI.',
            ], 422);
        }

        $tipoDocumento = $piDocumento->tipoDocumento;
        if (! $tipoDocumento || ! $tipoDocumento->avulso) {
            return response()->json([
                'success' => false,
                'message' => 'Somente documentos avulsos podem ser removidos da PI.',
            ], 422);
        }

        if ($piDocumento->documento_enviado_id) {
            return response()->json([
                'success' => false,
                'message' => 'Este documento já foi enviado e não pode ser removido.',
            ], 422);
        }

        try {
            $piDocumento->delete();

            $pi->refresh();
            $pi->atualizarStatusAutomaticamente();

            return response()->json([
                'success' => true,
                'message' => 'Documento avulso removido com sucesso!',
            ]);
        } catch (\Exception $e) {
            Log::channel('docs')->error('Erro ao remover documento avulso', [
                'pi_id' => $pi->id,
                'pi_documento_id' => $piDocumento->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover documento avulso: '.$e->getMessage(),
            ], 500);
        }
    }

    public function desfazerEnvioDocumento(Request $request, Pi $pi, PiDocumento $piDocumento)
    {
        $user = Auth::user();

        if ($user->hasRole('fornecedor')) {
            if (! $user->fornecedor || $pi->fornecedor_id !== $user->fornecedor->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para esta ação neste PI.',
                ], 403);
            }
        } elseif ($user->hasRole('representante')) {
            if (! $this->representanteTemAcessoAoPi($user, $pi)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para esta ação neste PI.',
                ], 403);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Apenas fornecedores ou representantes podem desfazer o envio de documentos.',
            ], 403);
        }

        if ($piDocumento->pi_id !== $pi->id) {
            return response()->json([
                'success' => false,
                'message' => 'Documento não pertence a este PI.',
            ], 422);
        }

        if (! $piDocumento->documento_enviado_id) {
            return response()->json([
                'success' => false,
                'message' => 'Este documento ainda não foi enviado.',
            ], 422);
        }

        $documentoEnviado = $piDocumento->documentoEnviado;

        if (! $documentoEnviado) {
            return response()->json([
                'success' => false,
                'message' => 'Documento enviado não encontrado.',
            ], 422);
        }

        $statusPermitidos = ['Pendente', 'Aguardando validação'];
        $statusAtual = $documentoEnviado->status ? $documentoEnviado->status->nome : '';

        if (! in_array($statusAtual, $statusPermitidos)) {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível desfazer o envio. O documento já foi analisado pela equipe ('.$statusAtual.').',
            ], 422);
        }

        if ($pi->concluido) {
            return response()->json([
                'success' => false,
                'message' => 'Este PI já foi concluído.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $statusPendente = CacheService::statusPorNome('documento', 'Pendente');

            if (! $statusPendente) {
                throw new \Exception('Status Pendente não encontrado.');
            }

            $outrosPiDocs = PiDocumento::where('documento_enviado_id', $documentoEnviado->id)
                ->where('id', '!=', $piDocumento->id)
                ->count();

            if ($outrosPiDocs === 0) {
                foreach ($documentoEnviado->arquivos as $arquivo) {
                    $caminhos = [
                        storage_path('app/private/'.$arquivo->file_path),
                        storage_path('app/'.$arquivo->file_path),
                    ];
                    foreach ($caminhos as $caminho) {
                        if (file_exists($caminho)) {
                            unlink($caminho);
                            break;
                        }
                    }
                    $arquivo->delete();
                }

                $documentoEnviado->delete();
            }

            $piDocumento->update([
                'documento_enviado_id' => null,
                'status_id' => $statusPendente->id,
            ]);

            $pi->update(['envio_confirmado' => false]);

            $pi->refresh();
            $pi->atualizarStatusAutomaticamente();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Envio desfeito com sucesso! Agora você pode enviar o documento correto.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('docs')->error('Erro ao desfazer envio de documento', [
                'pi_id' => $pi->id,
                'pi_documento_id' => $piDocumento->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao desfazer envio: '.$e->getMessage(),
            ], 500);
        }
    }

    public function reverterValidacao(Request $request, Pi $pi, PiDocumento $documento)
    {
        $user = Auth::user();

        if (! $user->hasRole('admin') && ! $user->genOp) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário sem perfil de gerencial/operacional vinculado.',
            ], 403);
        }

        if ($pi->concluido) {
            if (! $user->hasAnyRole(['admin', 'gerencial'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este PI já foi concluído. Apenas gerenciais podem reverter documentos.',
                ], 403);
            }
        } else {
            if ($user->hasRole('operacional')) {
                if (! $user->genOp || ! $user->genOp->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Usuário sem perfil genOp válido.',
                    ], 403);
                }
                $temAcesso = $user->genOp && $pi->operacionalTemAcesso($user->genOp->id);
                if (! $temAcesso) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Você não tem permissão para reverter documentos deste PI.',
                    ], 403);
                }
            }
        }

        if ($documento->pi_id !== $pi->id) {
            return response()->json([
                'success' => false,
                'message' => 'Documento não pertence a este PI.',
            ], 404);
        }

        if (! $documento->documentoEnviado) {
            return response()->json([
                'success' => false,
                'message' => 'Este documento ainda não foi enviado.',
            ], 400);
        }

        $statusAtualObj = $documento->documentoEnviado->status;
        if (! $statusAtualObj) {
            return response()->json([
                'success' => false,
                'message' => 'Documento sem status definido.',
            ], 400);
        }

        $statusAtual = $statusAtualObj->nome;
        if (! in_array($statusAtual, ['Aprovado', 'Reprovado'])) {
            return response()->json([
                'success' => false,
                'message' => 'Apenas documentos aprovados ou reprovados podem ser revertidos.',
            ], 400);
        }

        $validated = $request->validate([
            'observacoes' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            $statusPendente = CacheService::statusPorTipo('documento')
                ->whereIn('nome', ['Pendente', 'Aguardando validação', 'Aguardando Validacao'])
                ->first();

            if (! $statusPendente) {
                $statusPendente = CacheService::statusPorTipo('documento')
                    ->whereNotIn('nome', ['Aprovado', 'Reprovado'])
                    ->first();
            }

            if (! $statusPendente) {
                throw new \Exception('Não foi possível encontrar um status válido para reverter o documento.');
            }

            $documento->documentoEnviado->update([
                'status_id' => $statusPendente->id,
                'valido' => false,
            ]);

            $documento->update([
                'status_id' => $statusPendente->id,
            ]);

            $genOpId = $user->genOp ? $user->genOp->id : null;
            if (! $genOpId && ! $user->hasRole('admin')) {
                throw new \Exception('Usuário sem perfil genOp válido.');
            }

            $documentoEnviadoId = $documento->documento_enviado_id;
            if (! $documentoEnviadoId) {
                throw new \Exception('Documento sem ID de envio.');
            }

            ValidacaoDocumento::create([
                'documento_enviado_id' => $documentoEnviadoId,
                'operacional_id' => $genOpId,
                'status_id' => $statusPendente->id,
                'data_validacao' => now(),
                'observacoes' => $validated['observacoes']
                    ? "Validação revertida: {$validated['observacoes']}"
                    : "Validação revertida de '{$statusAtual}' para 'Pendente'",
            ]);

            $this->propagarStatusParaOutrosPiDocumentos(
                $documentoEnviadoId,
                $documento->id,
                $statusPendente->id,
                $pi->id
            );

            $piReaberto = false;
            if ($pi->concluido) {
                $pi->update([
                    'concluido' => false,
                    'data_conclusao' => null,
                ]);
                $piReaberto = true;
            }

            $pi->refresh();
            $pi->atualizarStatusAutomaticamente();

            DB::commit();

            Log::channel('docs')->warning('Validação revertida', [
                'pi_id' => $pi->id,
                'pi_numero' => $pi->numero_pi,
                'pi_documento_id' => $documento->id,
                'documento_enviado_id' => $documentoEnviadoId,
                'tipo_documento' => $documento->tipoDocumento->nome ?? null,
                'status_anterior' => $statusAtual,
                'revertido_por' => $user->id,
                'pi_reaberto' => $piReaberto,
                'observacoes' => $validated['observacoes'] ?? null,
            ]);

            $mensagem = 'Documento revertido para pendente com sucesso!';
            if ($piReaberto) {
                $mensagem .= ' O PI foi reaberto automaticamente.';
            }

            return response()->json([
                'success' => true,
                'message' => $mensagem,
                'pi_reaberto' => $piReaberto,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('docs')->error('Erro ao reverter validação do documento', [
                'pi_id' => $pi->id,
                'pi_numero' => $pi->numero_pi,
                'documento_id' => $documento->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao reverter validação: '.$e->getMessage(),
            ], 500);
        }
    }

    public function getTipoVeiculos()
    {
        try {
            $tipoVeiculos = CacheService::tiposVeiculoAtivos()
                ->map(function ($tipo) {
                    return [
                        'id' => $tipo->id,
                        'nome' => $tipo->nome,
                        'descricao' => $tipo->descricao,
                    ];
                });

            return response()->json([
                'success' => true,
                'tipo_veiculos' => $tipoVeiculos,
            ]);
        } catch (\Exception $e) {
            Log::channel('pis')->error('Erro ao buscar tipos de veículos', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar tipos de veículos: '.$e->getMessage(),
            ], 500);
        }
    }

    public function extrairNumeroPi(Request $request)
    {
        if (! $request->hasFile('documento_pi')) {
            return response()->json([
                'success' => false,
                'message' => 'Nenhum arquivo foi enviado.',
            ], 422);
        }

        $file = $request->file('documento_pi');

        if (! $file->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'Arquivo corrompido.',
            ], 422);
        }

        if (strtolower($file->getClientOriginalExtension()) !== 'pdf') {
            return response()->json([
                'success' => false,
                'message' => 'Apenas arquivos PDF são permitidos.',
            ], 422);
        }

        try {
            $numeroPi = $this->extrairNumeroPiDoPdf($file);

            if ($numeroPi) {
                return response()->json([
                    'success' => true,
                    'numero_pi' => $numeroPi,
                    'message' => 'Número do PI extraído com sucesso.',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Não foi possível identificar o número do PI no documento.',
            ]);

        } catch (\Exception $e) {
            Log::channel('pis')->error('Erro ao extrair número do PI', [
                'erro' => $e->getMessage(),
                'linha' => $e->getLine(),
                'arquivo_erro' => $e->getFile(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar o documento.',
            ], 500);
        }
    }

    private function extrairNumeroPiDoPdf($file)
    {
        $conteudo = file_get_contents($file->getRealPath());

        if ($conteudo === false) {
            return null;
        }

        $padroesPI = [
            '/PI\s*\n\s*(\d{4,10})/',
            '/PI[\s\-_]*(\d{4,10})/',
            '/\bPI\b[^\d]{0,20}(\d{4,10})/',
        ];

        foreach ($padroesPI as $padrao) {
            if (preg_match($padrao, $conteudo, $matches)) {
                return trim($matches[1]);
            }
        }

        $textosParenteses = '';
        if (preg_match_all('/\(([^\)]+)\)/', $conteudo, $matches)) {
            $textosParenteses = implode(' ', $matches[1]);
        }

        if (! empty($textosParenteses)) {
            foreach ($padroesPI as $padrao) {
                if (preg_match($padrao, $textosParenteses, $matches)) {
                    return trim($matches[1]);
                }
            }
        }

        $textoExtraido = '';
        try {
            $parser = new \Smalot\PdfParser\Parser;
            $pdf = $parser->parseFile($file->getRealPath());
            $textoExtraido = $pdf->getText();
        } catch (\Exception $e) {
            Log::channel('pis')->warning('PdfParser falhou ao extrair número do PI', ['error' => $e->getMessage()]);
        }

        if (! empty($textoExtraido)) {
            $textoLimpo = $this->limparTextoPdf($textoExtraido);

            foreach ($padroesPI as $padrao) {
                if (preg_match($padrao, $textoLimpo, $matches)) {
                    return trim($matches[1]);
                }
            }

            if (preg_match('/PEDIDO\s+DE\s+INSER[ÇC][AÃ]O\s*(?:PI)?\s*(\d{4,10})/iu', $textoLimpo, $matches)) {
                return trim($matches[1]);
            }
        }

        if (preg_match_all('/stream\s+(.*?)\s+endstream/s', $conteudo, $streams)) {
            foreach ($streams[1] as $stream) {
                if (function_exists('gzuncompress')) {
                    $decoded = @gzuncompress($stream);
                    if ($decoded !== false) {
                        $textoStream = '';
                        if (preg_match_all('/\(([^\)]+)\)/', $decoded, $streamMatches)) {
                            $textoStream = implode(' ', $streamMatches[1]);
                        }

                        foreach ($padroesPI as $padrao) {
                            if (preg_match($padrao, $textoStream, $matches)) {
                                return trim($matches[1]);
                            }
                        }
                    }
                }
            }
        }

        $nomeArquivo = $file->getClientOriginalName();
        $padroesNome = [
            '/PI[_\-\s]*(\d{4,10})/i',
            '/^(\d{4,10})[_\-\s]/i',
        ];

        foreach ($padroesNome as $padrao) {
            if (preg_match($padrao, $nomeArquivo, $matches)) {
                return trim($matches[1]);
            }
        }

        return null;
    }

    public function visualizarDocumentoPi(Pi $pi)
    {
        $user = Auth::user();

        if ($user->hasRole('fornecedor')) {
            if (! $user->fornecedor || $pi->fornecedor_id !== $user->fornecedor->id) {
                abort(403, 'Você não tem permissão para visualizar este documento.');
            }
        }

        if ($user->hasRole('operacional')) {
            $temAcesso = $user->genOp && $pi->operacionalTemAcesso($user->genOp->id);
            if (! $temAcesso) {
                abort(403, 'Você não tem permissão para visualizar este documento.');
            }
        }

        if ($user->hasRole('representante')) {
            if (! $this->representanteTemAcessoAoPi($user, $pi)) {
                abort(403, 'Você não tem permissão para visualizar este documento.');
            }
        }

        if (! $pi->file_path) {
            abort(404, 'Este PI não possui documento anexado.');
        }

        $filePath = $this->getPrivateFilePath($pi->file_path);

        if (! file_exists($filePath)) {
            abort(404, 'Arquivo não encontrado no servidor.');
        }

        return response()->file($filePath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="PI_'.$pi->numero_pi.'.pdf"',
        ]);
    }

    public function verificaClienteExigeVeiculo(Cliente $cliente)
    {
        try {
            $tipoVeiculos = [];
            if ($cliente->exige_documento_por_veiculo) {
                $tipoVeiculos = CacheService::tiposVeiculoAtivos();
            }

            return response()->json([
                'success' => true,
                'exige_documento_por_veiculo' => $cliente->exige_documento_por_veiculo,
                'tipo_veiculos' => $tipoVeiculos,
            ]);
        } catch (\Exception $e) {
            Log::channel('pis')->error('Erro ao verificar configuração do cliente', [
                'cliente_id' => $cliente->id ?? null,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erro ao verificar configuração do cliente: '.$e->getMessage(),
            ], 500);
        }
    }

    public function envioMassa()
    {
        $user = Auth::user();

        if (! $user->hasAnyRole(['admin', 'gerencial'])) {
            abort(403);
        }

        $clientes = Cliente::whereNull('deleted_at')
            ->orderBy('razao_social')
            ->get(['id', 'razao_social', 'nome_fantasia']);

        $fornecedores = Fornecedor::whereNull('deleted_at')
            ->orderBy('razao_social')
            ->get(['id', 'razao_social', 'nome_fantasia']);

        $operacionais = GenOp::with('user')
            ->whereHas('user', function ($q) {
                $q->whereNull('deleted_at')
                    ->whereHas('roles', function ($rq) {
                        $rq->where('name', 'operacional');
                    });
            })
            ->orderBy('nome_completo')
            ->get();

        $statusList = CacheService::statusPorTipo('pi');

        return view('pis.envio-massa', compact('clientes', 'fornecedores', 'operacionais', 'statusList'));
    }

    public function buscarPisParaReatribuicao(Request $request)
    {
        $user = Auth::user();

        if (! $user->hasAnyRole(['admin', 'gerencial'])) {
            return response()->json(['success' => false, 'message' => 'Sem permissão.'], 403);
        }

        $query = Pi::with(['cliente:id,razao_social,nome_fantasia', 'fornecedor:id,razao_social,nome_fantasia', 'status:id,nome,cor'])
            ->whereNull('pis.deleted_at')
            ->where('concluido', false);

        if ($request->filled('cliente_id')) {
            $query->where('cliente_id', $request->cliente_id);
        }

        if ($request->filled('fornecedor_id')) {
            $query->where('fornecedor_id', $request->fornecedor_id);
        }

        if ($request->filled('operacional_id')) {
            $opId = $request->operacional_id;
            $query->where(function ($q) use ($opId) {
                $q->where('operacional_id', $opId)
                    ->orWhereHas('atribuicoesOperacionais', function ($sq) use ($opId) {
                        $sq->where('operacional_id', $opId)
                            ->where('ativo', true)
                            ->whereNull('deleted_at');
                    });
            });
        }

        if ($request->filled('status_id')) {
            $query->where('status_id', $request->status_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('numero_pi', 'like', "%{$search}%")
                    ->orWhereHas('cliente', function ($sq) use ($search) {
                        $sq->where('razao_social', 'like', "%{$search}%");
                    })
                    ->orWhereHas('fornecedor', function ($sq) use ($search) {
                        $sq->where('razao_social', 'like', "%{$search}%");
                    });
            });
        }

        $pis = $query->orderBy('created_at', 'desc')
            ->limit(500)
            ->get();

        $result = $pis->map(function ($pi) {
            $opPrincipal = $pi->operacional_principal;

            return [
                'id' => $pi->id,
                'numero_pi' => $pi->numero_pi,
                'cliente' => $pi->cliente ? $pi->cliente->razao_social : null,
                'fornecedor' => $pi->fornecedor ? $pi->fornecedor->razao_social : null,
                'operacional_nome' => $opPrincipal ? $opPrincipal->nome_completo : 'Não atribuído',
                'operacional_id' => $opPrincipal ? $opPrincipal->id : null,
                'status_nome' => $pi->status ? $pi->status->nome : 'Sem status',
                'status_cor' => $pi->status ? $pi->status->cor : '#6b7280',
                'data' => $pi->created_at->format('d/m/Y'),
            ];
        });

        return response()->json([
            'success' => true,
            'pis' => $result,
            'total' => $result->count(),
        ]);
    }

    public function processarEnvioMassa(Request $request)
    {
        $user = Auth::user();

        if (! $user->hasAnyRole(['admin', 'gerencial'])) {
            return response()->json(['success' => false, 'message' => 'Sem permissão.'], 403);
        }

        $validated = $request->validate([
            'pi_ids' => 'required|array|min:1',
            'pi_ids.*' => 'integer|exists:pis,id',
            'operacional_destino_id' => 'required|integer|exists:gen_op,id',
            'motivo' => 'nullable|string|max:500',
        ]);

        $opDestino = GenOp::with('user')
            ->whereHas('user', function ($q) {
                $q->whereNull('deleted_at')
                    ->whereHas('roles', function ($rq) {
                        $rq->where('name', 'operacional');
                    });
            })
            ->findOrFail($validated['operacional_destino_id']);

        $motivo = $validated['motivo'] ?? 'Reatribuição em massa por '.$user->name;
        $piIds = $validated['pi_ids'];
        $sucesso = 0;
        $erros = [];

        DB::beginTransaction();

        try {
            foreach ($piIds as $piId) {
                $pi = Pi::find($piId);

                if (! $pi || $pi->concluido) {
                    $erros[] = "PI #{$piId} não encontrado ou já concluído.";

                    continue;
                }

                PiAtribuicao::where('pi_id', $pi->id)
                    ->where('ativo', true)
                    ->where('principal', true)
                    ->whereNull('deleted_at')
                    ->update([
                        'ativo' => false,
                        'principal' => false,
                        'data_desatribuicao' => now(),
                        'motivo_mudanca' => $motivo,
                    ]);

                $atribuicaoExistente = PiAtribuicao::withTrashed()
                    ->where('pi_id', $pi->id)
                    ->where('operacional_id', $opDestino->id)
                    ->first();

                if ($atribuicaoExistente) {
                    if ($atribuicaoExistente->trashed()) {
                        $atribuicaoExistente->restore();
                    }
                    $atribuicaoExistente->update([
                        'cliente_id' => $pi->cliente_id,
                        'data_atribuicao' => now(),
                        'ativo' => true,
                        'principal' => true,
                        'data_desatribuicao' => null,
                    ]);
                } else {
                    PiAtribuicao::create([
                        'pi_id' => $pi->id,
                        'cliente_id' => $pi->cliente_id,
                        'operacional_id' => $opDestino->id,
                        'data_atribuicao' => now(),
                        'ativo' => true,
                        'principal' => true,
                    ]);
                }

                $pi->update(['operacional_id' => $opDestino->id]);

                $pi->atualizarStatusAutomaticamente();

                $sucesso++;
            }

            DB::commit();

            $message = "{$sucesso} ".($sucesso === 1 ? 'PI reatribuído' : 'PIs reatribuídos')." para {$opDestino->nome_completo}.";

            if (count($erros) > 0) {
                $message .= ' '.count($erros).' erro(s) encontrado(s).';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'total_sucesso' => $sucesso,
                'total_erros' => count($erros),
                'erros' => $erros,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('pis')->error('Erro ao processar reatribuição em massa', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar reatribuição: '.$e->getMessage(),
            ], 500);
        }
    }

    private function representanteTemAcessoAoPi($user, Pi $pi): bool
    {
        if (! $user->hasRole('representante')) {
            return false;
        }

        $repUsuario = $user->representanteUsuario;
        if (! $repUsuario) {
            return false;
        }

        if (! $pi->fornecedor_id) {
            return false;
        }

        return $repUsuario->representaFornecedor($pi->fornecedor_id);
    }
}