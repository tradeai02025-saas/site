<?php

/* app/Http/Controllers/DocumentoController.php */

namespace App\Http\Controllers;

use App\Models\{Arquivo, DocumentoEnviado, Fornecedor, Pi, PiDocumento, RepresentanteUsuario, TipoDocumento, ValidacaoDocumento};
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Cache, DB, Log, Storage};

class DocumentoController extends Controller
{
    public function index(Request $request)
    {
        return redirect()->route('fornecedor.docs_list.index');
    }

    public function validarDocs(Request $request)
    {
        return redirect()->route('pis.index');
    }

    public function list(Request $request, $validade = null)
    {
        $user = Auth::user();

        $fornecedorIds = $this->resolverFornecedorIds($user);
        if (empty($fornecedorIds)) {
            abort(403, 'Acesso negado.');
        }

        $documentosEnviados = DocumentoEnviado::whereIn('fornecedor_id', $fornecedorIds)
            ->with([
                'tipoDocumento',
                'status',
                'arquivos',
                'validacoes.operacional.user',
                'validacoes.status',
                'piDocumentos.pi.cliente',
                'fornecedor',
            ]);

        $pisPendentes = PiDocumento::whereHas('pi', function ($q) use ($fornecedorIds) {
            $q->whereIn('fornecedor_id', $fornecedorIds)
                ->whereNull('deleted_at');
        })
            ->whereNull('documento_enviado_id')
            ->with(['tipoDocumento', 'pi', 'status'])
            ->get();

        switch ($validade) {
            case 'vencendo':
                $documentosEnviados->where('valido', true)
                    ->whereBetween('data_validade_documento', [
                        now()->toDateString(),
                        now()->addDays(14)->toDateString(),
                    ]);
                break;
            case 'vencido':
                $documentosEnviados->where(function ($q) {
                    $q->where('valido', false)
                        ->orWhere('data_validade_documento', '<', now()->toDateString());
                });
                break;
            case 'valido':
                $documentosEnviados->where('valido', true)
                    ->where(function ($q) {
                        $q->where('data_validade_documento', '>', now()->addDays(14)->toDateString())
                            ->orWhereNull('data_validade_documento');
                    });
                break;
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $documentosEnviados->where(function ($q) use ($search) {
                $q->whereHas('tipoDocumento', function ($sq) use ($search) {
                    $sq->where('nome', 'like', "%{$search}%");
                })
                    ->orWhereHas('piDocumentos.pi.cliente', function ($sq) use ($search) {
                        $sq->where('razao_social', 'like', "%{$search}%")
                            ->orWhere('nome_fantasia', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('tipo_documento_id')) {
            $documentosEnviados->where('tipo_documento_id', $request->tipo_documento_id);
        }

        if ($request->filled('status_filtro')) {
            switch ($request->status_filtro) {
                case 'vencendo':
                    $documentosEnviados->where('valido', true)
                        ->whereBetween('data_validade_documento', [
                            now()->toDateString(),
                            now()->addDays(14)->toDateString(),
                        ]);
                    break;
                case 'vencido':
                    $documentosEnviados->where(function ($q) {
                        $q->where('valido', false)
                            ->orWhere('data_validade_documento', '<', now()->toDateString());
                    });
                    break;
                case 'valido':
                    $documentosEnviados->where('valido', true)
                        ->where(function ($q) {
                            $q->where('data_validade_documento', '>', now()->addDays(14)->toDateString())
                                ->orWhereNull('data_validade_documento');
                        });
                    break;
                case 'pendente':
                    $documentosEnviados->whereHas('status', function ($q) {
                        $q->whereIn('nome', ['Pendente', 'Aguardando validação']);
                    });
                    break;
                case 'reprovado':
                    $documentosEnviados->whereHas('status', function ($q) {
                        $q->whereIn('nome', ['Reprovado', 'Aguardando Reenvio']);
                    });
                    break;
                case 'aprovado':
                    $documentosEnviados->whereHas('status', function ($q) {
                        $q->where('nome', 'Aprovado');
                    });
                    break;
            }
        }

        $documentos = $documentosEnviados->orderBy('data_envio', 'desc')->paginate(15)->withQueryString();

        $hoje = now()->toDateString();
        $daqui14 = now()->addDays(14)->toDateString();

        $contadorStats = DB::table('documentos_enviados as de')
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN de.valido = 1 AND (de.data_validade_documento > ? OR de.data_validade_documento IS NULL) THEN 1 ELSE 0 END) as validos,
                SUM(CASE WHEN de.valido = 0 OR de.data_validade_documento < ? THEN 1 ELSE 0 END) as vencidos,
                SUM(CASE WHEN de.valido = 1 AND de.data_validade_documento BETWEEN ? AND ? THEN 1 ELSE 0 END) as vencendo,
                SUM(CASE WHEN s.nome IN ('Reprovado', 'Aguardando Reenvio') THEN 1 ELSE 0 END) as reprovados,
                SUM(CASE WHEN s.nome IN ('Pendente', 'Aguardando validação') THEN 1 ELSE 0 END) as aguardando,
                SUM(CASE WHEN s.nome = 'Aprovado' THEN 1 ELSE 0 END) as aprovados
            ", [$daqui14, $hoje, $hoje, $daqui14])
            ->join('status as s', 'de.status_id', '=', 's.id')
            ->whereIn('de.fornecedor_id', $fornecedorIds)
            ->whereNull('de.deleted_at')
            ->first();

        $totalDocumentos = (int) ($contadorStats->total ?? 0);
        $documentosValidos = (int) ($contadorStats->validos ?? 0);
        $documentosVencidos = (int) ($contadorStats->vencidos ?? 0);
        $documentosVencendo = (int) ($contadorStats->vencendo ?? 0);
        $documentosPendentes = $pisPendentes->count();
        $documentosReprovados = (int) ($contadorStats->reprovados ?? 0);
        $documentosAguardandoValidacao = (int) ($contadorStats->aguardando ?? 0);
        $documentosAprovados = (int) ($contadorStats->aprovados ?? 0);

        $pisComReprovados = PiDocumento::whereHas('pi', function ($q) use ($fornecedorIds) {
            $q->whereIn('fornecedor_id', $fornecedorIds)
                ->whereNull('deleted_at');
        })
            ->whereNotNull('documento_enviado_id')
            ->whereHas('documentoEnviado', function ($q) {
                $q->whereHas('status', function ($sq) {
                    $sq->whereIn('nome', ['Reprovado', 'Aguardando Reenvio']);
                });
            })
            ->with(['tipoDocumento', 'pi.cliente', 'documentoEnviado.status', 'documentoEnviado.validacoes'])
            ->get();

        $acaoNecessariaCount = $documentosPendentes + $documentosReprovados;

        $docsVencendo = DocumentoEnviado::whereIn('fornecedor_id', $fornecedorIds)
            ->where('valido', true)
            ->whereNotNull('data_validade_documento')
            ->whereBetween('data_validade_documento', [
                now()->toDateString(),
                now()->addDays(14)->toDateString(),
            ])
            ->whereHas('status', function ($q) {
                $q->where('nome', 'Aprovado');
            })
            ->with(['tipoDocumento', 'piDocumentos.pi.cliente', 'arquivos'])
            ->orderBy('data_validade_documento', 'asc')
            ->get();

        $pisDoFornecedor = Pi::whereIn('fornecedor_id', $fornecedorIds)
            ->whereNull('deleted_at')
            ->where('concluido', false)
            ->with([
                'cliente',
                'status',
                'tipoVeiculo',
                'piDocumentos' => function ($q) {
                    $q->with([
                        'tipoDocumento',
                        'documentoEnviado' => function ($sq) {
                            $sq->with(['status', 'arquivos']);
                        },
                        'status',
                    ]);
                },
            ])
            ->withCount([
                'piDocumentos as total_docs',
                'piDocumentos as docs_atendidos' => function ($q) {
                    $q->whereNotNull('documento_enviado_id')
                        ->whereHas('documentoEnviado', function ($sq) {
                            $sq->whereHas('status', function ($ssq) {
                                $ssq->where('nome', 'Aprovado');
                            });
                        });
                },
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        $pisConcluidosFornecedor = Pi::whereIn('fornecedor_id', $fornecedorIds)
            ->whereNull('deleted_at')
            ->where('concluido', true)
            ->with([
                'cliente',
                'status',
                'piDocumentos' => function ($q) {
                    $q->with([
                        'tipoDocumento',
                        'documentoEnviado' => function ($sq) {
                            $sq->with(['status']);
                        },
                    ]);
                },
            ])
            ->withCount([
                'piDocumentos as total_docs',
                'piDocumentos as docs_atendidos' => function ($q) {
                    $q->whereNotNull('documento_enviado_id')
                        ->whereHas('documentoEnviado', function ($sq) {
                            $sq->whereHas('status', function ($ssq) {
                                $ssq->where('nome', 'Aprovado');
                            });
                        });
                },
            ])
            ->orderBy('data_conclusao', 'desc')
            ->take(20)
            ->get();

        $tiposDocumentos = CacheService::tiposDocumentoAtivos();
        $statusList = CacheService::statusPorTipo('documento');

        return view('fornecedores.meus-documentos', compact(
            'documentos',
            'statusList',
            'totalDocumentos',
            'documentosValidos',
            'documentosVencidos',
            'documentosVencendo',
            'documentosPendentes',
            'documentosReprovados',
            'documentosAguardandoValidacao',
            'documentosAprovados',
            'acaoNecessariaCount',
            'tiposDocumentos',
            'pisPendentes',
            'pisComReprovados',
            'docsVencendo',
            'pisDoFornecedor',
            'pisConcluidosFornecedor'
        ));
    }

    public function docsEnviar(Request $request)
    {
        $user = Auth::user();

        $fornecedorIds = $this->resolverFornecedorIds($user);
        if (empty($fornecedorIds)) {
            abort(403, 'Apenas fornecedores ou representantes podem acessar esta página.');
        }

        $fornecedorIdSelecionado = $request->input('fornecedor_id');
        if ($fornecedorIdSelecionado && ! in_array((int) $fornecedorIdSelecionado, $fornecedorIds)) {
            abort(403, 'Sem permissão para este fornecedor.');
        }

        $fornecedorIdAtivo = $fornecedorIdSelecionado ? (int) $fornecedorIdSelecionado : $fornecedorIds[0];

        $pisDoFornecedor = Pi::where('fornecedor_id', $fornecedorIdAtivo)
            ->whereNull('deleted_at')
            ->where('concluido', false)
            ->with([
                'cliente',
                'status',
                'tipoVeiculo',
                'piDocumentos' => function ($q) {
                    $q->with([
                        'tipoDocumento',
                        'documentoEnviado' => function ($sq) {
                            $sq->with([
                                'status',
                                'arquivos',
                                'validacoes' => function ($vq) {
                                    $vq->orderBy('created_at', 'desc')->limit(1);
                                },
                            ]);
                        },
                        'status',
                    ]);
                },
            ])
            ->withCount([
                'piDocumentos as total_docs',
                'piDocumentos as docs_aprovados' => function ($q) {
                    $q->whereNotNull('documento_enviado_id')
                        ->whereHas('documentoEnviado', function ($sq) {
                            $sq->whereHas('status', function ($ssq) {
                                $ssq->where('nome', 'Aprovado');
                            });
                        });
                },
                'piDocumentos as docs_rejeitados' => function ($q) {
                    $q->whereNotNull('documento_enviado_id')
                        ->whereHas('documentoEnviado', function ($sq) {
                            $sq->whereHas('status', function ($ssq) {
                                $ssq->whereIn('nome', ['Reprovado', 'Aguardando Reenvio']);
                            });
                        });
                },
                'piDocumentos as docs_pendentes' => function ($q) {
                    $q->whereNull('documento_enviado_id');
                },
                'piDocumentos as docs_em_analise' => function ($q) {
                    $q->whereNotNull('documento_enviado_id')
                        ->whereHas('documentoEnviado', function ($sq) {
                            $sq->whereHas('status', function ($ssq) {
                                $ssq->whereIn('nome', ['Pendente', 'Aguardando validação']);
                            });
                        });
                },
            ])
            ->get()
            ->sortByDesc(function ($pi) {
                if ($pi->docs_rejeitados > 0) return 3;
                if ($pi->docs_pendentes > 0) return 2;
                if ($pi->docs_em_analise > 0) return 1;
                return 0;
            })
            ->values();

        $totais = [
            'rejeitados' => $pisDoFornecedor->sum('docs_rejeitados'),
            'pendentes' => $pisDoFornecedor->sum('docs_pendentes'),
            'em_analise' => $pisDoFornecedor->sum('docs_em_analise'),
            'aprovados' => $pisDoFornecedor->sum('docs_aprovados'),
        ];

        $tiposDocumento = CacheService::tiposDocumentoAtivos()->where('avulso', false)->values();

        $fornecedores = null;
        if ($user->hasRole('representante') && count($fornecedorIds) > 1) {
            $fornecedores = Fornecedor::whereIn('id', $fornecedorIds)->orderBy('razao_social')->get();
        }

        return view('fornecedores.enviar-documentos', compact(
            'pisDoFornecedor',
            'totais',
            'tiposDocumento',
            'fornecedores',
            'fornecedorIdAtivo'
        ));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $fornecedorId = $this->resolverFornecedorIdParaAcao($user, $request);
        if (! $fornecedorId) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Acesso negado.');
        }

        $tipoDocumento = TipoDocumento::find($request->tipo_documento_id);

        $ehReenvio = false;
        $documentoExistente = null;
        $piDocumentoOrigem = null;

        if (! empty($request->pi_documento_id)) {
            $piDocumentoOrigem = PiDocumento::find($request->pi_documento_id);
            if ($piDocumentoOrigem && $piDocumentoOrigem->documento_enviado_id) {
                $documentoExistente = DocumentoEnviado::find($piDocumentoOrigem->documento_enviado_id);
                if ($documentoExistente) {
                    $statusRejeitados = ['Reprovado', 'Aguardando Reenvio'];
                    $statusNomeExistente = $documentoExistente->status ? $documentoExistente->status->nome : '';
                    if (in_array($statusNomeExistente, $statusRejeitados)) {
                        $ehReenvio = true;
                    }
                }
            }
            if ($piDocumentoOrigem && ! $piDocumentoOrigem->documento_enviado_id) {
                $ehReenvio = true;
            }
        }

        if (! $ehReenvio) {
            $hoje = now()->toDateString();

            $docAtivo = DocumentoEnviado::where('fornecedor_id', $fornecedorId)
                ->where('tipo_documento_id', $request->tipo_documento_id)
                ->whereHas('status', function ($q) {
                    $q->whereIn('nome', ['Pendente', 'Aguardando validação', 'Aprovado']);
                })
                ->where(function ($q) use ($hoje) {
                    $q->whereNull('data_validade_documento')
                        ->orWhere('data_validade_documento', '>=', $hoje);
                })
                ->first();

            if ($docAtivo) {
                $statusNome = $docAtivo->status ? $docAtivo->status->nome : '';
                if ($statusNome === 'Aprovado') {
                    return redirect()
                        ->back()
                        ->withInput()
                        ->with('error', 'Já existe um documento aprovado e válido deste tipo. Confira em "Meus Documentos".');
                }

                return redirect()
                    ->back()
                    ->withInput()
                    ->with('error', 'Já existe um documento deste tipo aguardando análise. Confira em "Meus Documentos".');
            }
        }

        $dataValidadeObrigatoria = $tipoDocumento &&
            $tipoDocumento->validar == 1 &&
            $tipoDocumento->validade_dias > 0;

        $regrasValidacao = [
            'tipo_documento_id' => 'required|exists:tipo_documentos,id',
            'arquivo' => 'required|file|mimes:pdf|max:46080',
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
            'arquivo.mimes' => 'O arquivo deve ser PDF',
            'arquivo.max' => 'O arquivo não pode exceder 45MB',
        ]);

        if ($request->hasFile('arquivo')) {
            $validacao = $this->validarArquivo($request);
            if (! $validacao['valido']) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with('error', $validacao['mensagem']);
            }
        }

        DB::beginTransaction();
        $fullPath = null;

        try {
            if (! $tipoDocumento) {
                throw new \Exception('Tipo de documento não encontrado.');
            }

            $dataValidade = null;
            $valido = true;

            if (! empty($validated['data_validade_documento'])) {
                $dataValidade = \Carbon\Carbon::parse($validated['data_validade_documento']);
                $valido = $dataValidade->endOfDay()->isFuture() || $dataValidade->isToday();
            }

            $pisParaAtualizar = collect();

            if ($ehReenvio && $documentoExistente) {
                $statusReenvio = CacheService::statusPorNome('documento', 'Aguardando validação');

                if (! $statusReenvio) {
                    $statusReenvio = CacheService::statusPorNome('documento', 'Pendente');
                }

                if (! $statusReenvio) {
                    throw new \Exception('Status de reenvio não encontrado.');
                }

                foreach ($documentoExistente->arquivos as $arquivoAntigo) {
                    $caminhos = [
                        storage_path('app/private/' . $arquivoAntigo->file_path),
                        storage_path('app/' . $arquivoAntigo->file_path),
                    ];
                    foreach ($caminhos as $caminho) {
                        if (file_exists($caminho)) {
                            unlink($caminho);
                            break;
                        }
                    }
                    $arquivoAntigo->delete();
                }

                $documentoExistente->update([
                    'status_id' => $statusReenvio->id,
                    'data_envio' => now(),
                    'data_validade_documento' => $dataValidade,
                    'valido' => $valido,
                ]);

                $piDocumentosVinculados = PiDocumento::where('documento_enviado_id', $documentoExistente->id)->get();
                foreach ($piDocumentosVinculados as $piDoc) {
                    $piDoc->update([
                        'status_id' => $statusReenvio->id,
                    ]);
                    if ($piDoc->pi_id) {
                        $pisParaAtualizar->push($piDoc->pi_id);
                    }
                }

                $documentoEnviado = $documentoExistente;

                if ($request->hasFile('arquivo')) {
                    $fullPath = $this->salvarArquivo($request->file('arquivo'), $documentoEnviado, $fornecedorId);
                }
            } else {
                $statusPendente = CacheService::statusPorNome('documento', 'Pendente');

                if (! $statusPendente) {
                    throw new \Exception('Status "Pendente" não encontrado.');
                }

                $documentoEnviado = DocumentoEnviado::create([
                    'fornecedor_id' => $fornecedorId,
                    'tipo_documento_id' => $validated['tipo_documento_id'],
                    'status_id' => $statusPendente->id,
                    'data_envio' => now(),
                    'data_validade_documento' => $dataValidade,
                    'valido' => $valido,
                ]);

                if ($piDocumentoOrigem) {
                    $piDocumentoOrigem->update([
                        'documento_enviado_id' => $documentoEnviado->id,
                        'status_id' => $statusPendente->id,
                    ]);

                    if ($piDocumentoOrigem->pi_id) {
                        $pisParaAtualizar->push($piDocumentoOrigem->pi_id);
                    }
                }

                if ($tipoDocumento && $tipoDocumento->validar) {
                    $outrosPiDocs = PiDocumento::where('tipo_documento_id', $validated['tipo_documento_id'])
                        ->whereNull('documento_enviado_id')
                        ->whereHas('pi', function ($q) use ($fornecedorId) {
                            $q->where('fornecedor_id', $fornecedorId)
                                ->whereNull('deleted_at')
                                ->where('concluido', false);
                        })
                        ->when($piDocumentoOrigem, function ($q) use ($piDocumentoOrigem) {
                            $q->where('id', '!=', $piDocumentoOrigem->id);
                        })
                        ->get();

                    foreach ($outrosPiDocs as $outroPiDoc) {
                        $outroPiDoc->update([
                            'documento_enviado_id' => $documentoEnviado->id,
                            'status_id' => $statusPendente->id,
                        ]);
                        if ($outroPiDoc->pi_id) {
                            $pisParaAtualizar->push($outroPiDoc->pi_id);
                        }
                    }
                }

                if ($request->hasFile('arquivo')) {
                    $fullPath = $this->salvarArquivo($request->file('arquivo'), $documentoEnviado, $fornecedorId);
                }
            }

            foreach ($pisParaAtualizar->unique()->filter() as $piId) {
                $piAtualizar = Pi::find($piId);
                if ($piAtualizar) {
                    $piAtualizar->atualizarStatusAutomaticamente();
                }
            }

            DB::commit();

            $mensagemSucesso = $ehReenvio
                ? 'Documento reenviado com sucesso! Aguarde a nova validação.'
                : 'Documento enviado com sucesso! Aguarde a análise.';

            if (! empty($request->pi_documento_id)) {
                $piDocRedirect = PiDocumento::find($request->pi_documento_id);
                if ($piDocRedirect && $piDocRedirect->pi_id) {
                    return redirect()
                        ->route('fornecedor.docs_enviar.index')
                        ->with('success', $mensagemSucesso);
                }

                return redirect()
                    ->route('fornecedor.docs_enviar.index')
                    ->with('success', $mensagemSucesso);
            } else {
                return redirect()
                    ->route('fornecedor.docs_list.index')
                    ->with('success', $mensagemSucesso);
            }
        } catch (\Exception $e) {
            DB::rollBack();

            if ($fullPath && file_exists(Storage::disk('private')->path($fullPath))) {
                Storage::disk('private')->delete($fullPath);
            }

            Log::channel('docs')->error('Erro ao enviar documento: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id,
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Erro ao enviar o documento. Tente novamente.');
        }
    }

    public function validarDataPdf(Request $request)
    {
        $user = Auth::user();

        $fornecedorIds = $this->resolverFornecedorIds($user);
        if (empty($fornecedorIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso negado.',
            ], 403);
        }

        try {
            $request->validate([
                'arquivo' => 'required|file|mimes:pdf|max:46080',
                'data_validade' => 'required|date|after_or_equal:today',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
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
                'message' => 'Erro ao processar o documento. Verifique se o arquivo está correto e tente novamente.',
            ], 500);
        }
    }

    public function extrairDataDoPdf(Request $request)
    {
        $user = Auth::user();

        $fornecedorIds = $this->resolverFornecedorIds($user);
        if (empty($fornecedorIds)) {
            return response()->json(['success' => false, 'message' => 'Acesso negado.'], 403);
        }

        if (! $request->hasFile('arquivo')) {
            return response()->json(['success' => false, 'message' => 'Nenhum arquivo enviado.'], 422);
        }

        $file = $request->file('arquivo');

        if (! $file->isValid() || strtolower($file->getClientOriginalExtension()) !== 'pdf') {
            return response()->json(['success' => false, 'message' => 'Arquivo inválido.'], 422);
        }

        try {
            $dataDetectada = $this->extrairDataDeValidadeDoPdf($file);

            if ($dataDetectada) {
                return response()->json([
                    'success' => true,
                    'data' => $dataDetectada->format('Y-m-d'),
                    'formatted' => $dataDetectada->format('d/m/Y'),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Data de validade não encontrada no documento.',
            ]);
        } catch (\Exception $e) {
            Log::channel('docs')->error('Erro ao extrair data do PDF: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Erro ao processar o documento.'], 500);
        }
    }

    private function extrairDataDeValidadeDoPdf($file): ?\Carbon\Carbon
    {
        $textoCompleto = '';
        $conteudo = file_get_contents($file->getRealPath());

        if ($conteudo !== false) {
            if (preg_match_all('/\(([^\)]+)\)/', $conteudo, $matchesBin)) {
                $textoCompleto .= ' '.implode(' ', $matchesBin[1]);
            }

            if (preg_match_all('/stream\s+(.*?)\s+endstream/s', $conteudo, $streams)) {
                foreach ($streams[1] as $stream) {
                    if (function_exists('gzuncompress')) {
                        $decoded = @gzuncompress($stream);
                        if ($decoded !== false) {
                            if (preg_match_all('/\(([^\)]+)\)/', $decoded, $sm)) {
                                $textoCompleto .= ' '.implode(' ', $sm[1]);
                            }
                            $textoCompleto .= ' '.$decoded;
                        }
                    }
                }
            }
        }

        try {
            $parser = new \Smalot\PdfParser\Parser;
            $pdf = $parser->parseFile($file->getRealPath());
            $textoCompleto .= ' '.$pdf->getText();
        } catch (\Exception $e) {
        }

        $texto = preg_replace('/[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F]/', '', $textoCompleto);
        $texto = preg_replace('/[ \t]+/', ' ', $texto);

        if (function_exists('mb_check_encoding') && ! mb_check_encoding($texto, 'UTF-8')) {
            $encoding = mb_detect_encoding($texto, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
            if ($encoding && $encoding !== 'UTF-8') {
                $texto = mb_convert_encoding($texto, 'UTF-8', $encoding);
            }
        }

        $hoje = \Carbon\Carbon::today();

        if (preg_match('/[Vv]alidade\s*:?\s*(\d{2}\/\d{2}\/\d{4})\s+a\s+(\d{2}\/\d{2}\/\d{4})/i', $texto, $m)) {
            try {
                $data = \Carbon\Carbon::createFromFormat('d/m/Y', trim($m[2]));
                if ($data && $data->greaterThanOrEqualTo($hoje)) {
                    return $data;
                }
            } catch (\Exception $e) {
            }
        }

        if (preg_match('/[Vv][aá]lid[ao]\s+at[eé]\s+(\d{2}\/\d{2}\/\d{4})/iu', $texto, $m)) {
            try {
                $data = \Carbon\Carbon::createFromFormat('d/m/Y', trim($m[1]));
                if ($data && $data->greaterThanOrEqualTo($hoje)) {
                    return $data;
                }
            } catch (\Exception $e) {
            }
        }

        if (preg_match('/[Vv]alidade\s*:\s*(\d{2}\/\d{2}\/\d{4})(?!\s*a\s*\d)/i', $texto, $m)) {
            try {
                $data = \Carbon\Carbon::createFromFormat('d/m/Y', trim($m[1]));
                if ($data && $data->greaterThanOrEqualTo($hoje)) {
                    return $data;
                }
            } catch (\Exception $e) {
            }
        }

        if (preg_match('/[Vv]encimento\s*:\s*(\d{2}\/\d{2}\/\d{4})/i', $texto, $m)) {
            try {
                $data = \Carbon\Carbon::createFromFormat('d/m/Y', trim($m[1]));
                if ($data && $data->greaterThanOrEqualTo($hoje)) {
                    return $data;
                }
            } catch (\Exception $e) {
            }
        }

        if (preg_match('/[Dd]ata\s+de\s+validade\s*:\s*(\d{2}\/\d{2}\/\d{4})/i', $texto, $m)) {
            try {
                $data = \Carbon\Carbon::createFromFormat('d/m/Y', trim($m[1]));
                if ($data && $data->greaterThanOrEqualTo($hoje)) {
                    return $data;
                }
            } catch (\Exception $e) {
            }
        }

        if (preg_match_all('/\b(\d{2}\/\d{2}\/\d{4})\b/', $texto, $allDates)) {
            $futuras = [];
            foreach ($allDates[1] as $dateStr) {
                try {
                    $d = \Carbon\Carbon::createFromFormat('d/m/Y', $dateStr);
                    if ($d && $d->greaterThan($hoje)) {
                        $futuras[] = $d;
                    }
                } catch (\Exception $e) {
                }
            }
            if (! empty($futuras)) {
                usort($futuras, fn ($a, $b) => $a->timestamp - $b->timestamp);

                return $futuras[0];
            }
        }

        return null;
    }

    public function validar(Request $request, DocumentoEnviado $documento)
    {
        $user = Auth::user();

        if (! $user->hasRole(['admin', 'gerencial', 'operacional'])) {
            return response()->json([
                'success' => false,
                'message' => 'Você não tem permissão para validar documentos.',
            ], 403);
        }

        if ($user->hasRole('operacional')) {
            $temPermissao = $documento->piDocumentos()
                ->whereHas('pi.operacionais', function ($q) use ($user) {
                    $q->where('gen_op.id', $user->genOp->id)
                        ->where('pi_atribuicoes.ativo', true);
                })
                ->exists();

            if (! $temPermissao) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para validar este documento.',
                ], 403);
            }
        }

        $statusAtualNome = $documento->status ? $documento->status->nome : '';
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
            $statusAprovado = CacheService::statusPorNome('documento', 'Aprovado');

            if (! $statusAprovado) {
                return response()->json([
                    'success' => false,
                    'message' => 'Status "Aprovado" não encontrado no sistema.',
                ], 500);
            }

            $operacionalId = null;
            if ($user->hasRole(['operacional', 'gerencial']) && $user->genOp) {
                $operacionalId = $user->genOp->id;
            }

            ValidacaoDocumento::create([
                'documento_enviado_id' => $documento->id,
                'operacional_id' => $operacionalId,
                'status_id' => $statusAprovado->id,
                'data_validacao' => now(),
                'observacoes' => $validated['observacoes'] ?? null,
            ]);

            $documento->update([
                'status_id' => $statusAprovado->id,
                'valido' => true,
            ]);

            $statusAtendido = CacheService::statusPorNome('documento', 'Atendido');

            $piDocumentos = PiDocumento::where('documento_enviado_id', $documento->id)->get();
            foreach ($piDocumentos as $piDoc) {
                $piDoc->update([
                    'status_id' => $statusAtendido ? $statusAtendido->id : $statusAprovado->id,
                ]);
            }

            $pisIds = $piDocumentos->pluck('pi_id')->unique()->filter();
            foreach ($pisIds as $piId) {
                $pi = Pi::find($piId);
                if ($pi) {
                    $pi->atualizarStatusAutomaticamente();
                }
            }

            DB::commit();

            $documento->load([
                'status',
                'validacoes.operacional.user',
                'validacoes.status',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Documento validado com sucesso!',
                'documento' => [
                    'id' => $documento->id,
                    'status' => $documento->status?->nome,
                    'status_cor' => $documento->status?->cor,
                    'data_validacao' => $documento->validacoes->last()?->data_validacao?->format('d/m/Y H:i'),
                    'validado_por' => $documento->validacoes->last()?->operacional?->nome_completo ?? 'Administrador',
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::channel('docs')->error('Erro ao validar/aprovar documento', [
                'documento_id' => $documento->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao validar documento: '.$e->getMessage(),
            ], 500);
        }
    }

    public function reprovar(Request $request, DocumentoEnviado $documento)
    {
        $user = Auth::user();

        if (! $user->hasRole(['admin', 'gerencial', 'operacional'])) {
            return response()->json([
                'success' => false,
                'message' => 'Você não tem permissão para reprovar documentos.',
            ], 403);
        }

        if ($user->hasRole('operacional')) {
            $temPermissao = $documento->piDocumentos()
                ->whereHas('pi.operacionais', function ($q) use ($user) {
                    $q->where('gen_op.id', $user->genOp->id)
                        ->where('pi_atribuicoes.ativo', true);
                })
                ->exists();

            if (! $temPermissao) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para reprovar este documento.',
                ], 403);
            }
        }

        $statusAtualNome = $documento->status ? $documento->status->nome : '';
        $statusPermitidosParaValidacao = ['Pendente', 'Aguardando validação'];

        if (! in_array($statusAtualNome, $statusPermitidosParaValidacao)) {
            return response()->json([
                'success' => false,
                'message' => 'Este documento não está pendente de validação. Status atual: '.$statusAtualNome,
            ], 422);
        }

        $validated = $request->validate([
            'motivo' => 'required|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            $statusReprovado = CacheService::statusPorNome('documento', 'Reprovado');

            if (! $statusReprovado) {
                return response()->json([
                    'success' => false,
                    'message' => 'Status "Reprovado" não encontrado no sistema.',
                ], 500);
            }

            $operacionalId = null;
            if ($user->hasRole(['operacional', 'gerencial']) && $user->genOp) {
                $operacionalId = $user->genOp->id;
            }

            ValidacaoDocumento::create([
                'documento_enviado_id' => $documento->id,
                'operacional_id' => $operacionalId,
                'status_id' => $statusReprovado->id,
                'data_validacao' => now(),
                'observacoes' => $validated['motivo'],
            ]);

            $documento->update([
                'status_id' => $statusReprovado->id,
                'valido' => false,
            ]);

            $piDocumentos = PiDocumento::where('documento_enviado_id', $documento->id)->get();
            foreach ($piDocumentos as $piDoc) {
                $piDoc->update([
                    'status_id' => $statusReprovado->id,
                ]);
            }

            $pisIds = $piDocumentos->pluck('pi_id')->unique()->filter();
            foreach ($pisIds as $piId) {
                $pi = Pi::find($piId);
                if ($pi) {
                    $pi->atualizarStatusAutomaticamente();

                    $fornecedor = $pi->fornecedor;
                    if ($fornecedor && $fornecedor->user) {
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
                            'motivo' => $validated['motivo'],
                            'validador_nome' => $user->name,
                            'data' => now()->toDateTimeString(),
                        ];

                        Cache::put($chaveCache, $dadosCache, now()->addMinutes(60));
                    }
                }
            }

            DB::commit();

            $documento->load([
                'status',
                'validacoes.operacional.user',
                'validacoes.status',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Documento reprovado com sucesso!',
                'documento' => [
                    'id' => $documento->id,
                    'status' => $documento->status?->nome,
                    'status_cor' => $documento->status?->cor,
                    'data_validacao' => $documento->validacoes->last()?->data_validacao?->format('d/m/Y H:i'),
                    'reprovado_por' => $documento->validacoes->last()?->operacional?->nome_completo ?? 'Administrador',
                    'motivo' => $validated['motivo'],
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::channel('docs')->error('Erro ao reprovar documento', [
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

    public function preview(DocumentoEnviado $documento, $arquivoId)
    {
        $user = Auth::user();

        if ($user->hasRole('fornecedor')) {
            if (! $user->fornecedor) {
                Log::channel('security')->warning('Preview negado: fornecedor sem perfil associado', [
                    'user_id' => $user->id,
                ]);
                abort(403, 'Usuário fornecedor sem cadastro de fornecedor associado.');
            }

            if ($documento->fornecedor_id !== $user->fornecedor->id) {
                Log::channel('security')->warning('Preview negado: documento não pertence ao fornecedor', [
                    'user_id' => $user->id,
                    'user_fornecedor_id' => $user->fornecedor->id,
                    'documento_fornecedor_id' => $documento->fornecedor_id,
                ]);
                abort(403, 'Você não tem permissão para visualizar este documento.');
            }
        } elseif ($user->hasRole('representante')) {
            if (! $this->representanteTemAcessoAoDocumento($user, $documento)) {
                abort(403, 'Você não tem permissão para visualizar este documento.');
            }
        }

        $arquivo = $documento->arquivos()->findOrFail($arquivoId);

        if (! Storage::disk('private')->exists($arquivo->file_path)) {
            abort(404, 'Arquivo não encontrado no servidor.');
        }

        $fullPath = Storage::disk('private')->path($arquivo->file_path);

        return response()->file($fullPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline',
        ]);
    }

    public function download(DocumentoEnviado $documento, $arquivoId)
    {
        $user = Auth::user();

        if ($user->hasRole('fornecedor')) {
            if (! $user->fornecedor) {
                Log::channel('security')->warning('Download negado: fornecedor sem perfil associado', [
                    'user_id' => $user->id,
                ]);
                abort(403, 'Usuário fornecedor sem cadastro de fornecedor associado.');
            }

            if ($documento->fornecedor_id !== $user->fornecedor->id) {
                Log::channel('security')->warning('Download negado: documento não pertence ao fornecedor', [
                    'user_id' => $user->id,
                    'user_fornecedor_id' => $user->fornecedor->id,
                    'documento_fornecedor_id' => $documento->fornecedor_id,
                ]);
                abort(403, 'Você não tem permissão para baixar este documento.');
            }
        } elseif ($user->hasRole('representante')) {
            if (! $this->representanteTemAcessoAoDocumento($user, $documento)) {
                abort(403, 'Você não tem permissão para baixar este documento.');
            }
        }

        $arquivo = $documento->arquivos()->findOrFail($arquivoId);

        if (! Storage::disk('private')->exists($arquivo->file_path)) {
            Log::channel('docs')->error('Download - arquivo não encontrado no storage', [
                'file_path' => $arquivo->file_path,
            ]);
            abort(404, 'Arquivo não encontrado no servidor.');
        }

        return Storage::disk('private')->download(
            $arquivo->file_path,
            $arquivo->nome_original
        );
    }

    public function solicitarReenvio(Request $request, DocumentoEnviado $documento)
    {
        $user = Auth::user();

        if (! $user->hasRole(['admin', 'gerencial', 'operacional'])) {
            return response()->json([
                'success' => false,
                'message' => 'Você não tem permissão para solicitar reenvio.',
            ], 403);
        }

        if ($user->hasRole('operacional')) {
            $temPermissao = $documento->piDocumentos()
                ->whereHas('pi.operacionais', function ($q) use ($user) {
                    $q->where('gen_op.id', $user->genOp->id)
                        ->where('pi_atribuicoes.ativo', true);
                })
                ->exists();

            if (! $temPermissao) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para solicitar reenvio deste documento.',
                ], 403);
            }
        }

        $statusAtualNome = $documento->status ? $documento->status->nome : '';
        $statusPermitidosParaValidacao = ['Pendente', 'Aguardando validação'];

        if (! in_array($statusAtualNome, $statusPermitidosParaValidacao)) {
            return response()->json([
                'success' => false,
                'message' => 'Este documento não está pendente de validação. Status atual: '.$statusAtualNome,
            ], 422);
        }

        $validated = $request->validate([
            'motivo' => 'required|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            $statusReenvio = CacheService::statusPorNome('documento', 'Aguardando Reenvio');

            if (! $statusReenvio) {
                $statusReenvio = CacheService::statusPorNome('documento', 'Pendente');
            }

            if (! $statusReenvio) {
                return response()->json([
                    'success' => false,
                    'message' => 'Status de reenvio não encontrado no sistema.',
                ], 500);
            }

            $operacionalId = null;
            if ($user->hasRole(['operacional', 'gerencial']) && $user->genOp) {
                $operacionalId = $user->genOp->id;
            }

            ValidacaoDocumento::create([
                'documento_enviado_id' => $documento->id,
                'operacional_id' => $operacionalId,
                'status_id' => $statusReenvio->id,
                'data_validacao' => now(),
                'observacoes' => 'Solicitação de reenvio: '.$validated['motivo'],
            ]);

            $documento->update([
                'status_id' => $statusReenvio->id,
                'valido' => false,
            ]);

            $piDocumentos = PiDocumento::where('documento_enviado_id', $documento->id)->get();
            foreach ($piDocumentos as $piDoc) {
                $piDoc->update([
                    'status_id' => $statusReenvio->id,
                ]);
            }

            $pisIds = $piDocumentos->pluck('pi_id')->unique()->filter();
            foreach ($pisIds as $piId) {
                $pi = Pi::find($piId);
                if ($pi) {
                    $pi->atualizarStatusAutomaticamente();

                    $fornecedor = $pi->fornecedor;
                    if ($fornecedor && $fornecedor->user) {
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
                            'motivo' => $validated['motivo'],
                            'validador_nome' => $user->name,
                            'data' => now()->toDateTimeString(),
                        ];

                        Cache::put($chaveCache, $dadosCache, now()->addMinutes(60));
                    }
                }
            }

            DB::commit();

            $documento->load([
                'status',
                'validacoes.operacional.user',
                'validacoes.status',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Reenvio solicitado com sucesso!',
                'documento' => [
                    'id' => $documento->id,
                    'status' => $documento->status?->nome,
                    'status_cor' => $documento->status?->cor,
                    'data_solicitacao' => $documento->validacoes->last()?->data_validacao?->format('d/m/Y H:i'),
                    'solicitado_por' => $documento->validacoes->last()?->operacional?->nome_completo ?? 'Administrador',
                    'motivo' => $validated['motivo'],
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::channel('docs')->error('Erro ao solicitar reenvio', [
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

    public function historicoValidacoes(DocumentoEnviado $documento)
    {
        $user = Auth::user();

        if ($user->hasRole('fornecedor')) {
            if (! $user->fornecedor || $documento->fornecedor_id !== $user->fornecedor->id) {
                abort(403, 'Você não tem permissão para visualizar este histórico.');
            }
        } elseif ($user->hasRole('representante')) {
            if (! $this->representanteTemAcessoAoDocumento($user, $documento)) {
                abort(403, 'Você não tem permissão para visualizar este histórico.');
            }
        }

        $validacoes = $documento->validacoes()
            ->with(['operacional.user', 'status'])
            ->orderBy('data_validacao', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'validacoes' => $validacoes->map(function ($validacao) {
                return [
                    'id' => $validacao->id,
                    'data_validacao' => $validacao->data_validacao?->format('d/m/Y H:i'),
                    'operacional' => $validacao->operacional?->nome_completo ?? 'Administrador',
                    'operacional_email' => $validacao->operacional?->user?->email,
                    'status' => $validacao->status?->nome,
                    'status_cor' => $validacao->status?->cor,
                    'observacoes' => $validacao->observacoes,
                ];
            }),
        ]);
    }

    public function documentosVencendo(Request $request)
    {
        $user = Auth::user();

        $diasAntes = $request->input('dias', 14);
        $dataLimite = now()->addDays($diasAntes);

        $query = DocumentoEnviado::with([
            'fornecedor.user',
            'tipoDocumento',
            'status',
            'piDocumentos.pi.cliente',
        ])
            ->where('valido', true)
            ->whereBetween('data_validade_documento', [now(), $dataLimite])
            ->whereHas('status', function ($q) {
                $q->where('nome', 'Aprovado');
            });

        if ($user->hasRole('operacional')) {
            $query->whereHas('piDocumentos.pi.operacionais', function ($q) use ($user) {
                $q->where('gen_op.id', $user->genOp->id)
                    ->where('pi_atribuicoes.ativo', true);
            });
        }

        $documentos = $query->orderBy('data_validade_documento', 'asc')->get();

        return response()->json([
            'success' => true,
            'total' => $documentos->count(),
            'documentos' => $documentos->map(function ($doc) {
                $diasRestantes = now()->diffInDays($doc->data_validade_documento, false);

                return [
                    'id' => $doc->id,
                    'fornecedor' => $doc->fornecedor?->razao_social,
                    'tipo_documento' => $doc->tipoDocumento?->nome,
                    'data_validade' => $doc->data_validade_documento?->format('d/m/Y'),
                    'dias_restantes' => $diasRestantes,
                    'urgente' => $diasRestantes <= 7,
                    'status' => $doc->status?->nome,
                    'pis_vinculados' => $doc->piDocumentos->count(),
                ];
            }),
        ]);
    }

    public function documentosVencidos(Request $request)
    {
        $user = Auth::user();

        $query = DocumentoEnviado::with([
            'fornecedor.user',
            'tipoDocumento',
            'status',
            'piDocumentos.pi.cliente',
        ])
            ->whereNotNull('data_validade_documento')
            ->where('data_validade_documento', '<', now()->toDateString())
            ->whereHas('status', function ($q) {
                $q->whereNotIn('nome', ['Reprovado', 'Aguardando Reenvio']);
            });

        if ($user->hasRole('operacional')) {
            $query->whereHas('piDocumentos.pi.operacionais', function ($q) use ($user) {
                $q->where('gen_op.id', $user->genOp->id)
                    ->where('pi_atribuicoes.ativo', true);
            });
        }

        $documentos = $query->orderBy('data_validade_documento', 'desc')->get();

        return response()->json([
            'success' => true,
            'total' => $documentos->count(),
            'documentos' => $documentos->map(function ($doc) {
                $diasVencidos = now()->diffInDays($doc->data_validade_documento);

                return [
                    'id' => $doc->id,
                    'fornecedor' => $doc->fornecedor?->razao_social,
                    'tipo_documento' => $doc->tipoDocumento?->nome,
                    'data_validade' => $doc->data_validade_documento?->format('d/m/Y'),
                    'dias_vencidos' => $diasVencidos,
                    'status' => $doc->status?->nome,
                    'pis_vinculados' => $doc->piDocumentos->count(),
                ];
            }),
        ]);
    }

    public function baixarTodosZip()
    {
        $user = Auth::user();

        $fornecedorIds = $this->resolverFornecedorIds($user);
        if (empty($fornecedorIds)) {
            abort(403, 'Acesso negado.');
        }

        $documentosVinculadosIds = PiDocumento::whereHas('pi', function ($q) use ($fornecedorIds) {
            $q->whereIn('fornecedor_id', $fornecedorIds)
                ->whereNull('deleted_at');
        })
            ->whereNotNull('documento_enviado_id')
            ->pluck('documento_enviado_id')
            ->unique()
            ->toArray();

        $documentos = DocumentoEnviado::whereIn('fornecedor_id', $fornecedorIds)
            ->whereIn('id', $documentosVinculadosIds)
            ->with(['tipoDocumento', 'arquivos', 'fornecedor'])
            ->whereHas('arquivos')
            ->get();

        if ($documentos->isEmpty()) {
            return redirect()
                ->back()
                ->with('error', 'Você não possui documentos para baixar.');
        }

        $nomeBase = 'documentos';
        if ($user->hasRole('fornecedor') && $user->fornecedor) {
            $nomeBase = $user->fornecedor->nome_fantasia ?? $user->fornecedor->razao_social;
        } elseif ($user->hasRole('representante') && $user->representanteUsuario) {
            $nomeBase = $user->representanteUsuario->nome_empresa ?? $user->name;
        }
        $nomeBase = preg_replace('/[^A-Za-z0-9\-\_]/', '_', $nomeBase);

        $zipFileName = 'documentos_'.$nomeBase.'_'.date('Y-m-d_His').'.zip';
        $zipPath = storage_path('app/temp/'.$zipFileName);

        if (! file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $zip = new \ZipArchive;

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            Log::channel('docs')->error('Erro ao criar arquivo ZIP', [
                'user_id' => $user->id,
                'zip_path' => $zipPath,
            ]);

            return redirect()
                ->back()
                ->with('error', 'Erro ao criar arquivo ZIP. Tente novamente.');
        }

        $arquivosAdicionados = 0;

        foreach ($documentos as $documento) {
            foreach ($documento->arquivos as $arquivo) {
                $filePath = Storage::disk('private')->path($arquivo->file_path);

                if (file_exists($filePath)) {
                    $tipoDocNome = preg_replace('/[^A-Za-z0-9\-\_]/', '_', $documento->tipoDocumento->nome);
                    $nomeArquivo = $tipoDocNome.'/'.$arquivo->nome_original;

                    $zip->addFile($filePath, $nomeArquivo);
                    $arquivosAdicionados++;
                } else {
                    Log::channel('docs')->warning('Arquivo não encontrado ao criar ZIP', [
                        'arquivo_id' => $arquivo->id,
                        'file_path' => $arquivo->file_path,
                    ]);
                }
            }
        }

        $zip->close();

        if ($arquivosAdicionados === 0) {
            if (file_exists($zipPath)) {
                unlink($zipPath);
            }

            return redirect()
                ->back()
                ->with('error', 'Nenhum arquivo foi encontrado para download.');
        }

        return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
    }

    private function salvarArquivo($file, DocumentoEnviado $documentoEnviado, $fornecedorId)
    {
        $fileName = time().'_'.uniqid().'_'.$file->getClientOriginalName();

        $path = $file->storeAs(
            'private/fornecedor_documentos/'.$fornecedorId,
            $fileName
        );

        Arquivo::create([
            'documento_enviado_id' => $documentoEnviado->id,
            'nome_original' => $file->getClientOriginalName(),
            'nome_arquivo' => $fileName,
            'file_path' => $path,
            'extensao' => $file->getClientOriginalExtension(),
            'tamanho' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ]);

        return $path;
    }

    private function validarArquivo(Request $request)
    {
        $file = $request->file('arquivo');

        if (strtolower($file->getClientOriginalExtension()) !== 'pdf') {
            return [
                'valido' => false,
                'mensagem' => 'Apenas arquivos PDF são permitidos.',
            ];
        }

        $maxSize = 45 * 1024 * 1024;
        if ($file->getSize() > $maxSize) {
            return [
                'valido' => false,
                'mensagem' => 'O arquivo não pode exceder 45MB.',
            ];
        }

        if (! $file->isValid()) {
            return [
                'valido' => false,
                'mensagem' => 'Arquivo inválido ou corrompido.',
            ];
        }

        if ($request->filled('data_validade_documento')) {
            try {
                $resultadoValidacao = $this->validarDataPdfInterno($file, $request->data_validade_documento);

                if (! $resultadoValidacao['valido']) {
                    return [
                        'valido' => false,
                        'mensagem' => $resultadoValidacao['mensagem'],
                    ];
                }
            } catch (\Throwable $th) {
                Log::channel('docs')->warning('Erro ao validar data no PDF - documento aceito para revisão manual', [
                    'erro' => $th->getMessage(),
                    'trace' => $th->getTraceAsString(),
                    'user_id' => Auth::id(),
                ]);

                return [
                    'valido' => true,
                    'mensagem' => 'Não foi possível validar a data no documento. Verifique se o arquivo está correto.',
                ];
            }
        }

        return ['valido' => true];
    }

    private function validarDataPdfInterno($file, $dataInformadaString)
    {
        try {
            $dataInformada = \Carbon\Carbon::parse($dataInformadaString);

            if (! $dataInformada->isFuture() && ! $dataInformada->isToday()) {
                return [
                    'valido' => false,
                    'mensagem' => 'A data de validade deve ser igual ou posterior à data atual.',
                ];
            }

            $resultadoBinario = $this->buscarDataNoBinarioPdf($file, $dataInformada);

            if ($resultadoBinario['encontrada']) {
                return [
                    'valido' => true,
                    'mensagem' => 'Data validada com sucesso no documento.',
                ];
            }

            $resultadoTexto = $this->extrairEValidarTextoPdf($file, $dataInformada);

            if ($resultadoTexto['encontrada']) {
                return [
                    'valido' => true,
                    'mensagem' => 'Data validada com sucesso no documento.',
                ];
            }

            if ($resultadoTexto['eh_imagem_escaneada']) {
                Log::channel('docs')->info('PDF escaneado - validação de data ignorada, será validado manualmente', [
                    'arquivo' => $file->getClientOriginalName(),
                    'data_informada' => $dataInformada->format('d/m/Y'),
                ]);

                return [
                    'valido' => true,
                    'validacao_automatica' => false,
                    'mensagem' => 'Documento aceito. Por ser uma imagem escaneada, a data será verificada manualmente pela equipe.',
                ];
            }

            Log::channel('docs')->info('Data não encontrada no PDF - será validado manualmente', [
                'arquivo' => $file->getClientOriginalName(),
                'data_procurada' => $dataInformada->format('d/m/Y'),
            ]);

            return [
                'valido' => true,
                'validacao_automatica' => false,
                'mensagem' => 'Documento aceito. A data de validade será verificada manualmente pela equipe.',
            ];
        } catch (\Exception $e) {
            Log::channel('docs')->warning('Erro ao processar PDF - documento aceito para revisão manual', [
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'valido' => true,
                'mensagem' => 'Erro ao processar o documento. Tente novamente ou entre em contato com o suporte.',
            ];
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

    public function reenviar(Request $request, DocumentoEnviado $documento)
    {
        $user = Auth::user();
        $isAjax = $request->expectsJson() || $request->ajax();

        $fornecedorIds = $this->resolverFornecedorIds($user);
        if (empty($fornecedorIds)) {
            if ($isAjax) {
                return response()->json(['success' => false, 'message' => 'Acesso negado.'], 403);
            }
            abort(403, 'Acesso negado.');
        }

        if (! in_array($documento->fornecedor_id, $fornecedorIds)) {
            if ($isAjax) {
                return response()->json(['success' => false, 'message' => 'Este documento não pertence a você.'], 403);
            }
            abort(403, 'Este documento não pertence a você.');
        }

        $fornecedorId = $documento->fornecedor_id;

        $pisConcluidos = $documento->piDocumentos()
            ->whereHas('pi', fn ($q) => $q->where('concluido', true))
            ->exists();

        if ($pisConcluidos) {
            $msg = 'Não é possível reenviar este documento pois o PI vinculado já foi concluído.';
            if ($isAjax) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        $podeReenviar = $documento->status && in_array($documento->status->nome, ['Reprovado', 'Aguardando Reenvio']);
        if (! $podeReenviar) {
            if ($isAjax) {
                return response()->json(['success' => false, 'message' => 'Este documento não pode ser reenviado no momento.'], 422);
            }
            return back()->with('error', 'Este documento não pode ser reenviado no momento.');
        }

        $tipoDocumento = $documento->tipoDocumento;
        $dataValidadeObrigatoria = $tipoDocumento->validar == 1 && $tipoDocumento->validade_dias > 0;

        $regrasValidacao = [
            'arquivo' => 'required|file|mimes:pdf|max:46080',
        ];

        if ($dataValidadeObrigatoria) {
            $regrasValidacao['data_validade_documento'] = 'required|date|after_or_equal:today';
        } else {
            $regrasValidacao['data_validade_documento'] = 'nullable|date|after_or_equal:today';
        }

        $validated = $request->validate($regrasValidacao);

        if ($request->hasFile('arquivo')) {
            $validacao = $this->validarArquivo($request);
            if (! $validacao['valido']) {
                if ($isAjax) {
                    return response()->json(['success' => false, 'message' => $validacao['mensagem']], 422);
                }
                return back()->withInput()->with('error', $validacao['mensagem']);
            }
        }

        DB::beginTransaction();
        try {
            $statusReenvio = CacheService::statusPorNome('documento', 'Aguardando validação');

            if (! $statusReenvio) {
                $statusReenvio = CacheService::statusPorNome('documento', 'Pendente');
            }

            $dataValidade = null;
            $valido = true;

            if (! empty($validated['data_validade_documento'])) {
                $dataValidade = \Carbon\Carbon::parse($validated['data_validade_documento']);
                $valido = $dataValidade->endOfDay()->isFuture() || $dataValidade->isToday();
            }

            $documento->update([
                'status_id' => $statusReenvio->id,
                'data_envio' => now(),
                'data_validade_documento' => $dataValidade,
                'valido' => $valido,
            ]);

            foreach ($documento->arquivos as $arquivoAntigo) {
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

            if ($request->hasFile('arquivo')) {
                $this->salvarArquivo($request->file('arquivo'), $documento, $fornecedorId);
            }

            $piDocumentos = PiDocumento::where('documento_enviado_id', $documento->id)->get();
            foreach ($piDocumentos as $piDoc) {
                $piDoc->update(['status_id' => $statusReenvio->id]);
            }

            $pisIds = $piDocumentos->pluck('pi_id')->unique()->filter();
            foreach ($pisIds as $piId) {
                $pi = Pi::find($piId);
                if ($pi) {
                    $pi->atualizarStatusAutomaticamente();
                }
            }

            DB::commit();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Documento reenviado com sucesso! Aguarde a nova validação.',
                ]);
            }

            return redirect()
                ->route('fornecedor.docs_list.index')
                ->with('success', 'Documento reenviado com sucesso! Aguarde a nova validação.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('docs')->error('Erro ao reenviar documento', [
                'documento_id' => $documento->id,
                'fornecedor_id' => $fornecedorId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao reenviar documento.',
                ], 500);
            }

            return back()->withInput()->with('error', 'Erro ao reenviar documento.');
        }
    }

    public function destroy(DocumentoEnviado $documento)
    {
        $user = Auth::user();

        if ($user->hasRole('fornecedor')) {
            if (! $user->fornecedor || $documento->fornecedor_id !== $user->fornecedor->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para excluir este documento.',
                ], 403);
            }

            $statusPermitidos = ['Pendente', 'Aguardando validação', 'Reprovado', 'Aguardando Reenvio'];
            if ($documento->status && ! in_array($documento->status->nome, $statusPermitidos)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Não é possível excluir um documento já aprovado.',
                ], 422);
            }
        } elseif ($user->hasRole('representante')) {
            if (! $this->representanteTemAcessoAoDocumento($user, $documento)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para excluir este documento.',
                ], 403);
            }

            $statusPermitidos = ['Pendente', 'Aguardando validação', 'Reprovado', 'Aguardando Reenvio'];
            if ($documento->status && ! in_array($documento->status->nome, $statusPermitidos)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Não é possível excluir um documento já aprovado.',
                ], 422);
            }
        } elseif ($user->hasRole('operacional')) {
            $temPermissao = $documento->piDocumentos()
                ->whereHas('pi', function ($q) use ($user) {
                    $q->where(function ($sq) use ($user) {
                        $sq->whereHas('operacionais', function ($oq) use ($user) {
                            $oq->where('gen_op.id', $user->genOp->id);
                        })->orWhere('operacional_id', $user->genOp->id);
                    });
                })
                ->exists();

            if (! $temPermissao) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para excluir este documento.',
                ], 403);
            }
        } elseif (! $user->hasRole(['admin', 'gerencial'])) {
            return response()->json([
                'success' => false,
                'message' => 'Você não tem permissão para excluir documentos.',
            ], 403);
        }

        DB::beginTransaction();
        try {
            $piDocumentos = PiDocumento::where('documento_enviado_id', $documento->id)->get();
            $pisIds = $piDocumentos->pluck('pi_id')->unique()->filter();

            $statusPendente = CacheService::statusPorNome('documento', 'Pendente');

            foreach ($piDocumentos as $piDoc) {
                $piDoc->update([
                    'documento_enviado_id' => null,
                    'status_id' => $statusPendente ? $statusPendente->id : $piDoc->status_id,
                ]);
            }

            foreach ($documento->arquivos as $arquivo) {
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

            $documento->validacoes()->delete();
            $documento->delete();

            foreach ($pisIds as $piId) {
                $pi = Pi::find($piId);
                if ($pi) {
                    if ($pi->concluido) {
                        $pi->update(['concluido' => false, 'data_conclusao' => null]);
                    }
                    $pi->atualizarStatusAutomaticamente();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Documento excluído com sucesso.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('docs')->error('Erro ao excluir documento', [
                'documento_id' => $documento->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir documento.',
            ], 500);
        }
    }

    private function resolverFornecedorIds($user): array
    {
        if ($user->hasRole('fornecedor') && $user->fornecedor) {
            return [$user->fornecedor->id];
        }

        if ($user->hasRole('representante') && $user->representanteUsuario) {
            return $user->representanteUsuario->getFornecedorIds();
        }

        return [];
    }

    private function resolverFornecedorIdParaAcao($user, Request $request): ?int
    {
        if ($user->hasRole('fornecedor') && $user->fornecedor) {
            return $user->fornecedor->id;
        }

        if ($user->hasRole('representante') && $user->representanteUsuario) {
            $fornecedorIds = $user->representanteUsuario->getFornecedorIds();
            $fornecedorId = $request->input('fornecedor_id');

            if ($fornecedorId && in_array((int) $fornecedorId, $fornecedorIds)) {
                return (int) $fornecedorId;
            }

            if (count($fornecedorIds) === 1) {
                return $fornecedorIds[0];
            }

            return null;
        }

        return null;
    }

    private function representanteTemAcessoAoDocumento($user, DocumentoEnviado $documento): bool
    {
        if (! $user->hasRole('representante')) {
            return false;
        }

        $repUsuario = $user->representanteUsuario;
        if (! $repUsuario) {
            return false;
        }

        return $repUsuario->representaFornecedor($documento->fornecedor_id);
    }
}