<?php

namespace App\Http\Controllers;

use App\Models\{Conversa, ConversaParticipante, ConversaTransferencia, Mensagem, MensagemAnexo, Pi, RepresentanteUsuario, User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Cache, DB, Log, Storage};
use Illuminate\Support\Str;

class ChatController extends Controller
{
    private function getUserRole($user)
    {
        $role = $user->roles->first();

        if (! $role) {
            Log::channel('chat')->error('Usuário sem role definida', [
                'user_id' => $user->id,
                'user_email' => $user->email,
            ]);
            abort(403, 'Usuário sem permissões definidas. Entre em contato com o administrador.');
        }

        return $role->name;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $user->registrarAtividade('Acessou lista de conversas');

        $userRole = $this->getUserRole($user);
        $filtro = $request->input('filtro', 'todas');
        $busca = trim($request->input('busca', ''));

        $filtrosValidos = ['todas', 'nao-lidas', 'abertas', 'resolvidas', 'escaladas'];
        if (! in_array($filtro, $filtrosValidos)) {
            $filtro = 'todas';
        }

        if ($userRole === 'admin') {
            $conversasIds = Conversa::whereNull('deleted_at')->pluck('id');

        } elseif ($userRole === 'gerencial') {
            $conversasParticipando = ConversaParticipante::where('participante_type', User::class)
                ->where('participante_id', $user->id)
                ->whereNull('data_saida')
                ->pluck('conversa_id');

            $conversasDisponiveis = Conversa::whereNull('deleted_at')
                ->where('nivel_atual', 'gerencial')
                ->whereIn('status_conversa', ['aberta', 'escalada'])
                ->whereDoesntHave('participantes', function ($q) {
                    $q->whereNull('data_saida')
                        ->whereHasMorph('participante', [User::class], function ($q2) {
                            $q2->whereHas('roles', function ($q3) {
                                $q3->where('name', 'gerencial');
                            });
                        });
                })
                ->pluck('id');

            $conversasIds = $conversasParticipando->merge($conversasDisponiveis)->unique();

        } else {
            $conversasIds = ConversaParticipante::where('participante_type', User::class)
                ->where('participante_id', $user->id)
                ->whereNull('data_saida')
                ->pluck('conversa_id');
        }

        $conversasOndeEhParticipante = ConversaParticipante::where('participante_type', User::class)
            ->where('participante_id', $user->id)
            ->whereNull('data_saida')
            ->whereHas('conversa', function ($q) {
                $q->whereNull('deleted_at');
            })
            ->pluck('conversa_id');

        $contagens = $this->calcularContagensConversas($conversasIds, $conversasOndeEhParticipante, $user);

        $query = Conversa::whereIn('id', $conversasIds)
            ->whereNull('deleted_at')
            ->with([
                'criador',
                'pi.cliente',
                'pi.status',
                'participantes' => function ($query) {
                    $query->whereNull('data_saida')->with('participante');
                },
                'mensagens' => function ($query) {
                    $query->whereNull('deleted_at')->latest()->limit(1);
                },
            ])
            ->withCount([
                'mensagens as mensagens_nao_lidas_count' => function ($query) use ($user, $conversasOndeEhParticipante) {
                    $query->where(function ($q) use ($user) {
                        $q->where('remetente_type', '!=', User::class)
                            ->orWhere('remetente_id', '!=', $user->id);
                    })
                        ->where('lida', false)
                        ->whereNull('deleted_at')
                        ->whereIn('conversa_id', $conversasOndeEhParticipante);
                },
            ]);

        if ($filtro === 'nao-lidas') {
            $query->whereHas('mensagens', function ($q) use ($user, $conversasOndeEhParticipante) {
                $q->where(function ($qq) use ($user) {
                    $qq->where('remetente_type', '!=', User::class)
                        ->orWhere('remetente_id', '!=', $user->id);
                })
                    ->where('lida', false)
                    ->whereNull('deleted_at')
                    ->whereIn('conversa_id', $conversasOndeEhParticipante);
            });
        } elseif ($filtro === 'abertas') {
            $query->where('status_conversa', 'aberta');
        } elseif ($filtro === 'resolvidas') {
            $query->where('status_conversa', 'resolvida');
        } elseif ($filtro === 'escaladas') {
            $query->where('status_conversa', 'escalada');
        }

        if ($busca !== '') {
            $buscaLike = '%'.str_replace(['%', '_'], ['\%', '\_'], $busca).'%';
            $query->where(function ($q) use ($buscaLike) {
                $q->where('assunto', 'like', $buscaLike)
                    ->orWhereHas('pi', function ($qp) use ($buscaLike) {
                        $qp->where('numero_pi', 'like', $buscaLike);
                    })
                    ->orWhereHas('mensagens', function ($qm) use ($buscaLike) {
                        $qm->where('mensagem', 'like', $buscaLike)
                            ->whereNull('deleted_at');
                    });
            });
        }

        $conversas = $query
            ->orderByDesc('mensagens_nao_lidas_count')
            ->orderBy('updated_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(20)
            ->appends($request->only(['filtro', 'busca']));

        return view('chat.index', compact('conversas', 'contagens', 'filtro', 'busca'));
    }

    private function calcularContagensConversas($conversasIds, $conversasOndeEhParticipante, $user): array
    {
        $base = Conversa::whereIn('id', $conversasIds)->whereNull('deleted_at');

        $total = (clone $base)->count();
        $abertas = (clone $base)->where('status_conversa', 'aberta')->count();
        $resolvidas = (clone $base)->where('status_conversa', 'resolvida')->count();
        $escaladas = (clone $base)->where('status_conversa', 'escalada')->count();

        $naoLidas = Mensagem::whereIn('conversa_id', $conversasOndeEhParticipante)
            ->where(function ($q) use ($user) {
                $q->where('remetente_type', '!=', User::class)
                    ->orWhere('remetente_id', '!=', $user->id);
            })
            ->where('lida', false)
            ->whereNull('deleted_at')
            ->select('conversa_id')
            ->distinct()
            ->count('conversa_id');

        return [
            'total' => $total,
            'nao_lidas' => $naoLidas,
            'abertas' => $abertas,
            'resolvidas' => $resolvidas,
            'escaladas' => $escaladas,
        ];
    }

    public function show(Conversa $conversa)
    {
        $user = Auth::user();

        if (! $this->usuarioPodeAcessarConversa($user, $conversa)) {
            Log::channel('security')->warning('Tentativa de acesso negado a conversa', [
                'user_id' => $user->id,
                'conversa_id' => $conversa->id,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
            abort(404);
        }

        $user->registrarAtividade('Visualizou conversa: '.$conversa->assunto);

        $conversa->load([
            'pi.cliente',
            'pi.status',
            'pi.fornecedor',
            'pi.gerencial',
            'pi.operacional',
        ]);

        $mensagens = Mensagem::where('conversa_id', $conversa->id)
            ->whereNull('deleted_at')
            ->with(['remetente.roles', 'anexos'])
            ->orderBy('created_at', 'asc')
            ->get();

        $participantes = ConversaParticipante::where('conversa_id', $conversa->id)
            ->whereNull('data_saida')
            ->with(['participante' => function ($query) {
                if (get_class($query->getRelated()) === User::class) {
                    $query->with('roles');
                }
            }])
            ->get();

        return view('chat.show', compact('conversa', 'mensagens', 'participantes'));
    }

    public function criarConversa(Request $request)
    {
        $user = Auth::user();
        $userRole = $this->getUserRole($user);

        if ($userRole === 'fornecedor' || $userRole === 'representante') {
            $validated = $request->validate([
                'assunto' => 'required|string|max:255',
                'pi_id' => 'nullable|exists:pis,id',
                'mensagem_inicial' => 'required|string|max:5000',
            ]);

            $validated['assunto'] = strip_tags($validated['assunto']);
            $validated['mensagem_inicial'] = strip_tags($validated['mensagem_inicial']);

            if (! empty($validated['pi_id'])) {
                $piParaConversa = Pi::find($validated['pi_id']);
                if ($piParaConversa) {
                    if ($userRole === 'fornecedor' && $user->fornecedor && $piParaConversa->fornecedor_id !== $user->fornecedor->id) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Você não tem permissão para criar conversas neste PI.',
                        ], 403);
                    }
                    if ($userRole === 'representante' && $user->representanteUsuario) {
                        $fornecedorIds = $user->representanteUsuario->getFornecedorIds();
                        if (! in_array($piParaConversa->fornecedor_id, $fornecedorIds)) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Você não tem permissão para criar conversas neste PI.',
                            ], 403);
                        }
                    }
                }
            }

            DB::beginTransaction();

            try {
                $operacionalSelecionado = $this->selecionarOperacionalAutomatico($user->id, $validated['pi_id'] ?? null);

                if (! $operacionalSelecionado) {
                    DB::rollBack();

                    return response()->json([
                        'success' => false,
                        'message' => 'Nenhum operacional disponível no momento. Tente novamente mais tarde.',
                    ], 503);
                }

                $verificacao = Conversa::whereNull('deleted_at')
                    ->whereHas('participantes', function ($q) use ($user) {
                        $q->where('participante_type', User::class)
                            ->where('participante_id', $user->id)
                            ->whereNull('data_saida');
                    })
                    ->whereHas('participantes', function ($q) use ($operacionalSelecionado) {
                        $q->where('participante_type', User::class)
                            ->where('participante_id', $operacionalSelecionado->id)
                            ->whereNull('data_saida');
                    })
                    ->whereIn('status_conversa', ['aberta', 'escalada'])
                    ->lockForUpdate()
                    ->first();

                if ($verificacao) {
                    DB::rollBack();

                    return response()->json([
                        'success' => false,
                        'message' => 'Já existe uma conversa ativa com este usuário sobre este tópico.',
                        'conversa_id' => $verificacao->id,
                        'redirect_url' => route('chat.show', $verificacao->id),
                    ], 409);
                }

                $destinatario = $operacionalSelecionado;
                $destinatarioRole = 'operacional';

                $conversa = Conversa::create([
                    'assunto' => $validated['assunto'],
                    'pi_id' => $validated['pi_id'] ?? null,
                    'nivel_atual' => $this->determinarNivelInicial($userRole, $destinatarioRole),
                    'status_conversa' => 'aberta',
                    'criador_type' => User::class,
                    'criador_id' => $user->id,
                ]);

                ConversaParticipante::create([
                    'conversa_id' => $conversa->id,
                    'participante_type' => User::class,
                    'participante_id' => $user->id,
                    'data_entrada' => now(),
                    'nivel_participante' => $userRole,
                ]);

                ConversaParticipante::create([
                    'conversa_id' => $conversa->id,
                    'participante_type' => User::class,
                    'participante_id' => $destinatario->id,
                    'data_entrada' => now(),
                    'nivel_participante' => $destinatarioRole,
                ]);

                Mensagem::create([
                    'conversa_id' => $conversa->id,
                    'remetente_type' => User::class,
                    'remetente_id' => $user->id,
                    'mensagem' => $validated['mensagem_inicial'],
                    'lida' => false,
                ]);

                $user->registrarAtividade('Criou conversa: '.$validated['assunto']);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'conversa_id' => $conversa->id,
                    'redirect_url' => route('chat.show', $conversa->id),
                    'message' => 'Conversa criada com sucesso!',
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                Log::channel('chat')->error('Erro ao criar conversa (fornecedor)', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao criar conversa. Tente novamente.',
                ], 500);
            }

        } else {
            $validated = $request->validate([
                'destinatario_id' => 'required|exists:users,id',
                'assunto' => 'required|string|max:255',
                'pi_id' => 'nullable|exists:pis,id',
                'mensagem_inicial' => 'required|string|max:5000',
            ]);

            $validated['assunto'] = strip_tags($validated['assunto']);
            $validated['mensagem_inicial'] = strip_tags($validated['mensagem_inicial']);

            if ($validated['destinatario_id'] == $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não pode iniciar uma conversa consigo mesmo.',
                ], 422);
            }

            $destinatario = User::with('roles')->findOrFail($validated['destinatario_id']);
            $destinatarioRole = $this->getUserRole($destinatario);

            if (! $this->podeConversarCom($userRole, $destinatarioRole)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para conversar diretamente com este usuário.',
                ], 403);
            }

            DB::beginTransaction();

            try {
                $verificacao = Conversa::whereNull('deleted_at')
                    ->whereHas('participantes', function ($q) use ($user) {
                        $q->where('participante_type', User::class)
                            ->where('participante_id', $user->id)
                            ->whereNull('data_saida');
                    })
                    ->whereHas('participantes', function ($q) use ($destinatario) {
                        $q->where('participante_type', User::class)
                            ->where('participante_id', $destinatario->id)
                            ->whereNull('data_saida');
                    })
                    ->whereIn('status_conversa', ['aberta', 'escalada'])
                    ->lockForUpdate()
                    ->first();

                if ($verificacao) {
                    DB::rollBack();

                    return response()->json([
                        'success' => false,
                        'message' => 'Já existe uma conversa ativa com este usuário sobre este tópico.',
                        'conversa_id' => $verificacao->id,
                        'redirect_url' => route('chat.show', $verificacao->id),
                    ], 409);
                }

                $conversa = Conversa::create([
                    'assunto' => $validated['assunto'],
                    'pi_id' => $validated['pi_id'] ?? null,
                    'nivel_atual' => $this->determinarNivelInicial($userRole, $destinatarioRole),
                    'status_conversa' => 'aberta',
                    'criador_type' => User::class,
                    'criador_id' => $user->id,
                ]);

                ConversaParticipante::create([
                    'conversa_id' => $conversa->id,
                    'participante_type' => User::class,
                    'participante_id' => $user->id,
                    'data_entrada' => now(),
                    'nivel_participante' => $userRole,
                ]);

                ConversaParticipante::create([
                    'conversa_id' => $conversa->id,
                    'participante_type' => User::class,
                    'participante_id' => $destinatario->id,
                    'data_entrada' => now(),
                    'nivel_participante' => $destinatarioRole,
                ]);

                Mensagem::create([
                    'conversa_id' => $conversa->id,
                    'remetente_type' => User::class,
                    'remetente_id' => $user->id,
                    'mensagem' => $validated['mensagem_inicial'],
                    'lida' => false,
                ]);

                $user->registrarAtividade('Criou conversa: '.$validated['assunto']);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'conversa_id' => $conversa->id,
                    'redirect_url' => route('chat.show', $conversa->id),
                    'message' => 'Conversa criada com sucesso!',
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                Log::channel('chat')->error('Erro ao criar conversa', [
                    'user_id' => $user->id,
                    'destinatario_id' => $validated['destinatario_id'] ?? null,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao criar conversa. Tente novamente.',
                ], 500);
            }
        }
    }

    public function enviarMensagem(Request $request, Conversa $conversa)
    {
        $user = Auth::user();

        if (! $this->usuarioPodeAcessarConversa($user, $conversa)) {
            Log::channel('security')->warning('Tentativa de envio de mensagem sem permissão', [
                'user_id' => $user->id,
                'conversa_id' => $conversa->id,
                'ip' => request()->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Você não tem permissão para enviar mensagens nesta conversa.',
            ], 403);
        }

        $ehParticipante = ConversaParticipante::where('conversa_id', $conversa->id)
            ->where('participante_type', User::class)
            ->where('participante_id', $user->id)
            ->whereNull('data_saida')
            ->exists();

        if (! $ehParticipante) {
            return response()->json([
                'success' => false,
                'message' => 'Você está em modo somente leitura. Assuma a conversa para enviar mensagens.',
            ], 403);
        }

        if ($conversa->status_conversa === 'resolvida') {
            return response()->json([
                'success' => false,
                'message' => 'Esta conversa está resolvida. Reabra-a para enviar mensagens.',
            ], 422);
        }

        $validated = $request->validate([
            'mensagem' => 'nullable|string|max:5000',
            'anexo' => 'nullable|file|max:46080',
        ]);

        if (! empty($validated['mensagem'])) {
            $validated['mensagem'] = strip_tags($validated['mensagem']);
            $validated['mensagem'] = Str::limit($validated['mensagem'], 5000);
        }

        if (empty($validated['mensagem']) && ! $request->hasFile('anexo')) {
            return response()->json([
                'success' => false,
                'message' => 'Você precisa enviar uma mensagem ou um anexo.',
            ], 422);
        }

        if ($request->hasFile('anexo')) {
            $validacao = $this->validarArquivo($request->file('anexo'));
            if (! $validacao['valido']) {
                return response()->json([
                    'success' => false,
                    'message' => $validacao['mensagem'],
                ], 422);
            }
        }

        $arquivosCriados = [];

        try {
            DB::beginTransaction();

            $mensagem = Mensagem::create([
                'conversa_id' => $conversa->id,
                'remetente_type' => User::class,
                'remetente_id' => $user->id,
                'mensagem' => $validated['mensagem'] ?? '',
                'lida' => false,
            ]);

            $anexoData = null;
            if ($request->hasFile('anexo')) {
                $anexoData = $this->processarAnexo($request->file('anexo'), $mensagem, $conversa);
                if (! empty($anexoData['file_path'])) {
                    $arquivosCriados[] = $anexoData['file_path'];
                }
            }

            $conversa->touch();
            $user->registrarAtividade('Enviou mensagem');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Mensagem enviada com sucesso!',
                'mensagem' => $this->formatarMensagemParaResposta($mensagem, $user, $anexoData),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            foreach ($arquivosCriados as $path) {
                try {
                    Storage::disk('private')->delete($path);
                } catch (\Exception $cleanupErr) {
                    Log::channel('chat')->warning('Falha ao limpar arquivo órfão após rollback', [
                        'path' => $path,
                        'error' => $cleanupErr->getMessage(),
                    ]);
                }
            }

            Log::channel('chat')->error('Erro ao enviar mensagem', [
                'conversa_id' => $conversa->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao enviar mensagem. Tente novamente.',
            ], 500);
        }
    }

    public function buscarMensagensNovas(Request $request, Conversa $conversa)
    {
        $user = Auth::user();

        if (! $this->usuarioPodeAcessarConversa($user, $conversa)) {
            return response()->json([
                'success' => false,
                'message' => 'Você não tem permissão para acessar esta conversa.',
            ], 403);
        }

        $ultimaMensagemId = (int) $request->input('ultima_mensagem_id', 0);
        $idsParaChecarLeitura = (array) $request->input('ids_para_checar_leitura', []);
        $idsParaChecarLeitura = array_filter(array_map('intval', $idsParaChecarLeitura));

        $novasMensagens = Mensagem::where('conversa_id', $conversa->id)
            ->where('id', '>', $ultimaMensagemId)
            ->whereNull('deleted_at')
            ->with(['remetente.roles', 'anexos'])
            ->orderBy('created_at', 'asc')
            ->get();

        $mensagensFormatadas = $novasMensagens->map(function ($mensagem) use ($user) {
            return $this->formatarMensagemParaResposta($mensagem, $user);
        });

        $atualizacoesLeitura = [];
        if (! empty($idsParaChecarLeitura)) {
            $atualizacoesLeitura = Mensagem::whereIn('id', $idsParaChecarLeitura)
                ->where('conversa_id', $conversa->id)
                ->where('lida', true)
                ->whereNull('deleted_at')
                ->get(['id', 'lida', 'data_leitura'])
                ->map(function ($m) {
                    return [
                        'id' => $m->id,
                        'lida' => true,
                        'data_leitura' => $m->data_leitura ? $m->data_leitura->format('d/m/Y H:i') : null,
                    ];
                })
                ->values()
                ->toArray();
        }

        return response()->json([
            'success' => true,
            'mensagens' => $mensagensFormatadas,
            'atualizacoes_leitura' => $atualizacoesLeitura,
            'status_conversa' => $conversa->status_conversa,
        ]);
    }

    public function marcarComoLida(Conversa $conversa)
    {
        $user = Auth::user();

        if (! $this->usuarioPodeAcessarConversa($user, $conversa)) {
            return response()->json([
                'success' => false,
                'message' => 'Você não tem permissão para acessar esta conversa.',
            ], 403);
        }

        $ehParticipante = ConversaParticipante::where('conversa_id', $conversa->id)
            ->where('participante_type', User::class)
            ->where('participante_id', $user->id)
            ->whereNull('data_saida')
            ->exists();

        if (! $ehParticipante) {
            return response()->json([
                'success' => true,
                'message' => 'Visualização registrada.',
            ]);
        }

        try {
            Mensagem::where('conversa_id', $conversa->id)
                ->where(function ($q) use ($user) {
                    $q->where('remetente_type', '!=', User::class)
                        ->orWhere('remetente_id', '!=', $user->id);
                })
                ->where('lida', false)
                ->whereNull('deleted_at')
                ->update(['lida' => true, 'data_leitura' => now()]);

            Cache::forget("unread_messages_{$user->id}");

            return response()->json([
                'success' => true,
                'message' => 'Mensagens marcadas como lidas.',
            ]);

        } catch (\Exception $e) {
            Log::channel('chat')->error('Erro ao marcar mensagens como lidas', [
                'conversa_id' => $conversa->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao marcar mensagens como lidas.',
            ], 500);
        }
    }

    public function buscarUsuarios(Request $request)
    {
        $search = $request->input('search', '');
        $excludeIds = $request->input('exclude', []);
        $user = Auth::user();
        $userRole = $this->getUserRole($user);

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
            'usuarios' => $usuarios,
        ]);
    }

    public function buscarPis(Request $request)
    {
        $search = $request->input('search', '');
        $user = Auth::user();
        $userRole = $this->getUserRole($user);

        $query = Pi::query();

        if ($userRole === 'fornecedor' && $user->fornecedor) {
            $query->where('fornecedor_id', $user->fornecedor->id);
        } elseif ($userRole === 'representante' && $user->representanteUsuario) {
            $fornecedorIds = $user->representanteUsuario->getFornecedorIds();
            $query->whereIn('fornecedor_id', $fornecedorIds);
        } elseif ($userRole === 'operacional' && $user->genOp) {
            $query->whereHas('operacionais', function ($q) use ($user) {
                $q->where('gen_op.id', $user->genOp->id);
            });
        } elseif ($userRole === 'gerencial' && $user->genOp) {
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('numero_pi', 'like', "%{$search}%")
                    ->orWhereHas('cliente', function ($qc) use ($search) {
                        $qc->where('razao_social', 'like', "%{$search}%");
                    });
            });
        }

        $pis = $query->with(['cliente', 'status'])
            ->where('concluido', false)
            ->limit(10)
            ->get()
            ->map(function ($pi) {
                return [
                    'id' => $pi->id,
                    'numero_pi' => $pi->numero_pi,
                    'cliente' => $pi->cliente ? $pi->cliente->razao_social : 'Sem cliente',
                    'status' => $pi->status ? $pi->status->nome : 'Sem status',
                ];
            });

        return response()->json([
            'success' => true,
            'pis' => $pis,
        ]);
    }

    public function limparMensagensOrfas()
    {
        $user = Auth::user();

        $conversasInativas = ConversaParticipante::where('participante_type', User::class)
            ->where('participante_id', $user->id)
            ->where(function ($q) {
                $q->whereNotNull('data_saida')
                    ->orWhereHas('conversa', function ($q2) {
                        $q2->whereNotNull('deleted_at');
                    });
            })
            ->pluck('conversa_id');

        if ($conversasInativas->isNotEmpty()) {
            Mensagem::whereIn('conversa_id', $conversasInativas)
                ->where('remetente_id', '!=', $user->id)
                ->where('lida', false)
                ->whereNull('deleted_at')
                ->update(['lida' => true, 'data_leitura' => now()]);

            Cache::forget("unread_messages_{$user->id}");
        }

        return response()->json([
            'success' => true,
            'message' => 'Mensagens órfãs limpas',
        ]);
    }

    public function conversasComNaoLidas()
    {
        $user = Auth::user();

        $conversasOndeEhParticipante = ConversaParticipante::where('participante_type', User::class)
            ->where('participante_id', $user->id)
            ->whereNull('data_saida')
            ->whereHas('conversa', function ($q) {
                $q->whereNull('deleted_at');
            })
            ->pluck('conversa_id');

        $conversasComNaoLidas = Mensagem::whereIn('conversa_id', $conversasOndeEhParticipante)
            ->where(function ($q) use ($user) {
                $q->where('remetente_type', '!=', User::class)
                    ->orWhere('remetente_id', '!=', $user->id);
            })
            ->where('lida', false)
            ->whereNull('deleted_at')
            ->select('conversa_id')
            ->distinct()
            ->pluck('conversa_id');

        return response()->json([
            'success' => true,
            'conversas_ids' => $conversasComNaoLidas,
        ]);
    }

    public function conversasNaoLidas()
    {
        $user = Auth::user();

        $conversasOndeEhParticipante = ConversaParticipante::where('participante_type', User::class)
            ->where('participante_id', $user->id)
            ->whereNull('data_saida')
            ->whereHas('conversa', function ($q) {
                $q->whereNull('deleted_at');
            })
            ->pluck('conversa_id');

        $totalNaoLidas = Mensagem::whereIn('conversa_id', $conversasOndeEhParticipante)
            ->where(function ($q) use ($user) {
                $q->where('remetente_type', '!=', User::class)
                    ->orWhere('remetente_id', '!=', $user->id);
            })
            ->where('lida', false)
            ->whereNull('deleted_at')
            ->count();

        return response()->json([
            'success' => true,
            'total_nao_lidas' => $totalNaoLidas,
        ]);
    }

    public function escalarConversa(Request $request, Conversa $conversa)
    {
        $user = Auth::user();
        $userRole = $this->getUserRole($user);

        if (! $this->usuarioPodeAcessarConversa($user, $conversa)) {
            return response()->json([
                'success' => false,
                'message' => 'Você não tem permissão para escalar esta conversa.',
            ], 403);
        }

        if ($conversa->status_conversa === 'resolvida') {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível escalar uma conversa resolvida.',
            ], 422);
        }

        if (! in_array($userRole, ['operacional', 'gerencial', 'admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Apenas usuários Operacionais, Gerenciais e Administradores podem escalar conversas.',
            ], 403);
        }

        $validated = $request->validate([
            'usuario_id' => 'nullable|exists:users,id',
            'motivo' => 'nullable|string|max:500',
        ]);

        if (isset($validated['motivo'])) {
            $validated['motivo'] = strip_tags($validated['motivo']);
        }

        $proximoNivel = $this->obterProximoNivel($conversa->nivel_atual);

        if (! $proximoNivel) {
            return response()->json([
                'success' => false,
                'message' => 'Esta conversa já está no nível máximo.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $conversa->update([
                'nivel_atual' => $proximoNivel,
                'status_conversa' => 'escalada',
            ]);

            if (isset($validated['usuario_id'])) {
                $destinatario = User::with('roles')->findOrFail($validated['usuario_id']);

                $jaParticipa = ConversaParticipante::where('conversa_id', $conversa->id)
                    ->where('participante_id', $destinatario->id)
                    ->where('participante_type', User::class)
                    ->whereNull('data_saida')
                    ->exists();

                if (! $jaParticipa) {
                    ConversaParticipante::create([
                        'conversa_id' => $conversa->id,
                        'participante_id' => $destinatario->id,
                        'participante_type' => User::class,
                        'data_entrada' => now(),
                        'nivel_participante' => $this->getUserRole($destinatario),
                    ]);
                }
            }

            $motivo = $validated['motivo'] ?? 'Sem motivo especificado';

            Mensagem::create([
                'conversa_id' => $conversa->id,
                'remetente_type' => User::class,
                'remetente_id' => $user->id,
                'mensagem' => 'Conversa escalada para nível superior. Motivo: '.$motivo,
                'lida' => true,
                'data_leitura' => now(),
            ]);

            $user->registrarAtividade('Escalou conversa: '.$conversa->assunto);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Conversa escalada com sucesso!',
                'novo_nivel' => $proximoNivel,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('chat')->error('Erro ao escalar conversa', [
                'conversa_id' => $conversa->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao escalar conversa. Tente novamente.',
            ], 500);
        }
    }

    public function resolverConversa(Conversa $conversa)
    {
        $user = Auth::user();
        $userRole = $this->getUserRole($user);

        if (! $this->usuarioPodeAcessarConversa($user, $conversa)) {
            return response()->json([
                'success' => false,
                'message' => 'Você não tem permissão para resolver esta conversa.',
            ], 403);
        }

        $podeResolver = in_array($userRole, ['operacional', 'gerencial', 'admin']);

        if (! $podeResolver) {
            return response()->json([
                'success' => false,
                'message' => 'Apenas Operacionais, Gerenciais e Administradores podem resolver conversas.',
            ], 403);
        }

        if ($conversa->status_conversa === 'resolvida') {
            return response()->json([
                'success' => false,
                'message' => 'Esta conversa já está resolvida.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $conversa->update(['status_conversa' => 'resolvida']);

            Mensagem::create([
                'conversa_id' => $conversa->id,
                'remetente_type' => User::class,
                'remetente_id' => $user->id,
                'mensagem' => 'Conversa marcada como resolvida',
                'lida' => true,
                'data_leitura' => now(),
            ]);

            $user->registrarAtividade('Resolveu conversa: '.$conversa->assunto);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Conversa marcada como resolvida.',
                'reload' => true,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('chat')->error('Erro ao resolver conversa', [
                'conversa_id' => $conversa->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao resolver conversa.',
            ], 500);
        }
    }

    public function reabrirConversa(Conversa $conversa)
    {
        $user = Auth::user();
        $userRole = $this->getUserRole($user);

        if (! $this->usuarioPodeAcessarConversa($user, $conversa)) {
            return response()->json([
                'success' => false,
                'message' => 'Você não tem permissão para reabrir esta conversa.',
            ], 403);
        }

        $podeReabrir = in_array($userRole, ['operacional', 'gerencial', 'admin']);

        if (! $podeReabrir) {
            return response()->json([
                'success' => false,
                'message' => 'Apenas Operacionais, Gerenciais e Administradores podem reabrir conversas.',
            ], 403);
        }

        if ($conversa->status_conversa !== 'resolvida') {
            return response()->json([
                'success' => false,
                'message' => 'Esta conversa não está resolvida.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $conversa->update(['status_conversa' => 'aberta']);

            Mensagem::create([
                'conversa_id' => $conversa->id,
                'remetente_type' => User::class,
                'remetente_id' => $user->id,
                'mensagem' => 'Conversa reaberta',
                'lida' => true,
                'data_leitura' => now(),
            ]);

            $user->registrarAtividade('Reabriu conversa: '.$conversa->assunto);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Conversa reaberta com sucesso.',
                'reload' => true,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('chat')->error('Erro ao reabrir conversa', [
                'conversa_id' => $conversa->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao reabrir conversa.',
            ], 500);
        }
    }

    public function adicionarParticipante(Request $request, Conversa $conversa)
    {
        $user = Auth::user();
        $userRole = $this->getUserRole($user);

        if (! $this->usuarioPodeAcessarConversa($user, $conversa)) {
            return response()->json([
                'success' => false,
                'message' => 'Você não tem permissão para adicionar participantes.',
            ], 403);
        }

        if (! in_array($userRole, ['gerencial', 'admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Você não tem permissão para adicionar participantes.',
            ], 403);
        }

        if ($conversa->status_conversa === 'resolvida') {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível adicionar participantes em uma conversa resolvida.',
            ], 422);
        }

        $validated = $request->validate(['usuario_id' => 'required|exists:users,id']);

        $novoParticipante = User::with('roles')->findOrFail($validated['usuario_id']);
        $novoRole = $this->getUserRole($novoParticipante);

        $jaParticipa = ConversaParticipante::where('conversa_id', $conversa->id)
            ->where('participante_type', User::class)
            ->where('participante_id', $novoParticipante->id)
            ->whereNull('data_saida')
            ->exists();

        if ($jaParticipa) {
            return response()->json([
                'success' => false,
                'message' => 'Este usuário já participa desta conversa.',
            ], 422);
        }

        if ($conversa->pi_id && in_array($novoRole, ['fornecedor', 'representante'])) {
            $pi = $conversa->pi;
            if (! $pi) {
                return response()->json([
                    'success' => false,
                    'message' => 'PI da conversa não encontrado.',
                ], 422);
            }

            if ($novoRole === 'fornecedor') {
                if (! $novoParticipante->fornecedor || $novoParticipante->fornecedor->id !== $pi->fornecedor_id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Este fornecedor não tem relação com o PI desta conversa.',
                    ], 422);
                }
            }

            if ($novoRole === 'representante') {
                $repUsuario = $novoParticipante->representanteUsuario;
                if (! $repUsuario || ! $repUsuario->representaFornecedor($pi->fornecedor_id)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Este representante não tem relação com o fornecedor do PI.',
                    ], 422);
                }
            }
        }

        try {
            DB::beginTransaction();

            ConversaParticipante::create([
                'conversa_id' => $conversa->id,
                'participante_type' => User::class,
                'participante_id' => $novoParticipante->id,
                'data_entrada' => now(),
                'nivel_participante' => $novoRole,
            ]);

            Mensagem::create([
                'conversa_id' => $conversa->id,
                'remetente_type' => User::class,
                'remetente_id' => $user->id,
                'mensagem' => $novoParticipante->name.' foi adicionado à conversa',
                'lida' => true,
                'data_leitura' => now(),
            ]);

            $user->registrarAtividade('Adicionou participante à conversa: '.$conversa->assunto);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Participante adicionado com sucesso.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('chat')->error('Erro ao adicionar participante', [
                'conversa_id' => $conversa->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao adicionar participante.',
            ], 500);
        }
    }

    public function transferirConversa(Request $request, Conversa $conversa)
    {
        $user = Auth::user();
        $userRole = $this->getUserRole($user);

        if ($userRole !== 'operacional') {
            return response()->json([
                'success' => false,
                'message' => 'Apenas operacionais podem transferir conversas.',
            ], 403);
        }

        if (! $this->usuarioPodeAcessarConversa($user, $conversa)) {
            return response()->json([
                'success' => false,
                'message' => 'Você não participa desta conversa.',
            ], 403);
        }

        if ($conversa->status_conversa === 'resolvida') {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível transferir uma conversa resolvida.',
            ], 422);
        }

        $validated = $request->validate([
            'para_usuario_id' => 'required|exists:users,id',
            'motivo' => 'nullable|string|max:500',
        ]);

        if (isset($validated['motivo'])) {
            $validated['motivo'] = strip_tags($validated['motivo']);
        }

        $paraUsuario = User::with('roles', 'genOp')->findOrFail($validated['para_usuario_id']);

        if ($this->getUserRole($paraUsuario) !== 'operacional') {
            return response()->json([
                'success' => false,
                'message' => 'Só é possível transferir para outro operacional.',
            ], 422);
        }

        if ($paraUsuario->id == $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Você não pode transferir para si mesmo.',
            ], 422);
        }

        if (! $paraUsuario->genOp) {
            return response()->json([
                'success' => false,
                'message' => 'O operacional destino não está ativo (sem cadastro operacional).',
            ], 422);
        }

        if ($paraUsuario->trashed() || $paraUsuario->deleted_at) {
            return response()->json([
                'success' => false,
                'message' => 'O operacional destino está inativo.',
            ], 422);
        }

        $jaParticipa = ConversaParticipante::where('conversa_id', $conversa->id)
            ->where('participante_type', User::class)
            ->where('participante_id', $paraUsuario->id)
            ->whereNull('data_saida')
            ->exists();

        if ($jaParticipa) {
            return response()->json([
                'success' => false,
                'message' => 'Este usuário já participa desta conversa.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            ConversaTransferencia::create([
                'conversa_id' => $conversa->id,
                'de_user_id' => $user->id,
                'para_user_id' => $paraUsuario->id,
                'motivo' => $validated['motivo'] ?? null,
                'data_transferencia' => now(),
            ]);

            ConversaParticipante::create([
                'conversa_id' => $conversa->id,
                'participante_type' => User::class,
                'participante_id' => $paraUsuario->id,
                'data_entrada' => now(),
                'nivel_participante' => 'operacional',
            ]);

            $participacao = ConversaParticipante::where('conversa_id', $conversa->id)
                ->where('participante_type', User::class)
                ->where('participante_id', $user->id)
                ->whereNull('data_saida')
                ->first();

            if ($participacao) {
                $participacao->update(['data_saida' => now()]);
            }

            $motivo = $validated['motivo'] ?? 'Sem motivo especificado';

            Mensagem::create([
                'conversa_id' => $conversa->id,
                'remetente_type' => User::class,
                'remetente_id' => $user->id,
                'mensagem' => "Conversa transferida para {$paraUsuario->name}. Motivo: {$motivo}",
                'lida' => true,
                'data_leitura' => now(),
            ]);

            $user->registrarAtividade('Transferiu conversa: '.$conversa->assunto);

            Cache::forget("unread_messages_{$user->id}");
            Cache::forget("unread_messages_{$paraUsuario->id}");

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Conversa transferida com sucesso.',
                'redirect_url' => route('chat.index'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('chat')->error('Erro ao transferir conversa', [
                'conversa_id' => $conversa->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao transferir conversa.',
            ], 500);
        }
    }

    public function sairConversa(Conversa $conversa)
    {
        $user = Auth::user();
        $userRole = $this->getUserRole($user);

        if ($userRole === 'operacional') {
            return response()->json([
                'success' => false,
                'message' => 'Operacionais não podem sair diretamente. Use a função de transferir.',
            ], 403);
        }

        if (! $this->usuarioPodeAcessarConversa($user, $conversa)) {
            return response()->json([
                'success' => false,
                'message' => 'Você não participa desta conversa.',
            ], 403);
        }

        if ($conversa->status_conversa === 'resolvida') {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível sair de uma conversa resolvida.',
            ], 422);
        }

        $participacao = ConversaParticipante::where('conversa_id', $conversa->id)
            ->where('participante_type', User::class)
            ->where('participante_id', $user->id)
            ->whereNull('data_saida')
            ->first();

        if (! $participacao) {
            return response()->json([
                'success' => false,
                'message' => 'Você não participa desta conversa.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $participacao->update(['data_saida' => now()]);

            Mensagem::create([
                'conversa_id' => $conversa->id,
                'remetente_type' => User::class,
                'remetente_id' => $user->id,
                'mensagem' => "{$user->name} saiu da conversa",
                'lida' => true,
                'data_leitura' => now(),
            ]);

            $user->registrarAtividade('Saiu da conversa: '.$conversa->assunto);

            Cache::forget("unread_messages_{$user->id}");

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Você saiu da conversa.',
                'redirect_url' => route('chat.index'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('chat')->error('Erro ao sair da conversa', [
                'conversa_id' => $conversa->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao sair da conversa.',
            ], 500);
        }
    }

    public function excluirConversa(Conversa $conversa)
    {
        $user = Auth::user();
        $userRole = $this->getUserRole($user);

        $podeExcluir = ($conversa->criador_id === $user->id && $conversa->criador_type === User::class) || $userRole === 'admin';

        if (! $podeExcluir) {
            return response()->json([
                'success' => false,
                'message' => 'Você não tem permissão para excluir esta conversa.',
            ], 403);
        }

        if (! $this->usuarioPodeAcessarConversa($user, $conversa)) {
            return response()->json([
                'success' => false,
                'message' => 'Você não tem permissão para excluir esta conversa.',
            ], 403);
        }

        try {
            $anexos = MensagemAnexo::whereHas('mensagem', function ($q) use ($conversa) {
                $q->where('conversa_id', $conversa->id);
            })->get();

            foreach ($anexos as $anexo) {
                try {
                    Storage::disk('private')->delete($anexo->file_path);
                } catch (\Exception $e) {
                    Log::channel('chat')->warning('Falha ao remover anexo físico ao excluir conversa', [
                        'anexo_id' => $anexo->id,
                        'conversa_id' => $conversa->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $participantesIds = ConversaParticipante::where('conversa_id', $conversa->id)
                ->where('participante_type', User::class)
                ->whereNull('data_saida')
                ->pluck('participante_id')
                ->toArray();

            $user->registrarAtividade('Excluiu conversa: '.$conversa->assunto);
            $conversa->delete();

            foreach ($participantesIds as $uid) {
                Cache::forget("unread_messages_{$uid}");
            }

            return response()->json([
                'success' => true,
                'message' => 'Conversa excluída com sucesso.',
                'redirect_url' => route('chat.index'),
            ]);

        } catch (\Exception $e) {
            Log::channel('chat')->error('Erro ao excluir conversa', [
                'conversa_id' => $conversa->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir conversa.',
            ], 500);
        }
    }

    public function previewAnexo(MensagemAnexo $anexo)
    {
        $user = Auth::user();
        $conversa = $anexo->mensagem->conversa;

        if (! $this->usuarioPodeAcessarConversa($user, $conversa)) {
            Log::channel('security')->warning('Tentativa de acesso a anexo sem permissão', [
                'user_id' => $user->id,
                'anexo_id' => $anexo->id,
                'conversa_id' => $conversa->id,
                'ip' => request()->ip(),
            ]);
            abort(403, 'Você não tem permissão para acessar este anexo.');
        }

        $path = Storage::disk('private')->path($anexo->file_path);
        $basePath = Storage::disk('private')->path('chat/');

        if (! file_exists($path)) {
            abort(404, 'Arquivo não encontrado.');
        }

        $realPath = realpath($path);
        $realBasePath = realpath($basePath);

        if (! $realPath || ! $realBasePath || ! str_starts_with($realPath, $realBasePath)) {
            Log::channel('security')->error('Tentativa de path traversal detectada', [
                'user_id' => $user->id,
                'anexo_id' => $anexo->id,
                'path' => $path,
                'ip' => request()->ip(),
            ]);
            abort(403, 'Acesso negado.');
        }

        $mimeTypes = [
            'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif',
            'bmp' => 'image/bmp', 'webp' => 'image/webp', 'mp4' => 'video/mp4', 'avi' => 'video/x-msvideo',
            'mov' => 'video/quicktime', 'wmv' => 'video/x-ms-wmv', 'flv' => 'video/x-flv',
            'mkv' => 'video/x-matroska', 'webm' => 'video/webm', 'mpeg' => 'video/mpeg',
            'pdf' => 'application/pdf', 'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'txt' => 'text/plain', 'csv' => 'text/csv',
        ];

        $mimeType = $mimeTypes[strtolower($anexo->extensao)] ?? 'application/octet-stream';

        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="'.$anexo->nome_original.'"',
        ]);
    }

    public function downloadAnexo(MensagemAnexo $anexo)
    {
        $user = Auth::user();
        $conversa = $anexo->mensagem->conversa;

        if (! $this->usuarioPodeAcessarConversa($user, $conversa)) {
            Log::channel('security')->warning('Tentativa de download de anexo sem permissão', [
                'user_id' => $user->id,
                'anexo_id' => $anexo->id,
                'conversa_id' => $conversa->id,
                'ip' => request()->ip(),
            ]);
            abort(403, 'Você não tem permissão para acessar este anexo.');
        }

        $path = Storage::disk('private')->path($anexo->file_path);
        $basePath = Storage::disk('private')->path('chat/');

        if (! file_exists($path)) {
            abort(404, 'Arquivo não encontrado.');
        }

        $realPath = realpath($path);
        $realBasePath = realpath($basePath);

        if (! $realPath || ! $realBasePath || ! str_starts_with($realPath, $realBasePath)) {
            Log::channel('security')->error('Tentativa de path traversal detectada no download', [
                'user_id' => $user->id,
                'anexo_id' => $anexo->id,
                'path' => $path,
                'ip' => request()->ip(),
            ]);
            abort(403, 'Acesso negado.');
        }

        return response()->download($path, $anexo->nome_original);
    }

    private function usuarioPodeAcessarConversa($user, $conversa)
    {
        $userRole = $this->getUserRole($user);

        if ($userRole === 'admin') {
            return true;
        }

        $participaAtivamente = ConversaParticipante::where('conversa_id', $conversa->id)
            ->where('participante_type', User::class)
            ->where('participante_id', $user->id)
            ->whereNull('data_saida')
            ->exists();

        if ($participaAtivamente) {
            return true;
        }

        if ($userRole === 'gerencial') {
            if (in_array($conversa->nivel_atual, ['gerencial']) && in_array($conversa->status_conversa, ['aberta', 'escalada'])) {
                $temGerencial = ConversaParticipante::where('conversa_id', $conversa->id)
                    ->whereNull('data_saida')
                    ->whereHas('participante', function ($q) {
                        $q->whereHas('roles', function ($q2) {
                            $q2->where('name', 'gerencial');
                        });
                    })
                    ->exists();

                if (! $temGerencial) {
                    return true;
                }
            }
        }

        if ($userRole === 'representante') {
            $repUsuario = $user->representanteUsuario;
            if ($repUsuario && $conversa->pi && $conversa->pi->fornecedor_id) {
                if ($repUsuario->representaFornecedor($conversa->pi->fornecedor_id)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function processarAnexo($arquivo, $mensagem, $conversa)
    {
        $nomeOriginal = $arquivo->getClientOriginalName();
        $extensao = strtolower($arquivo->getClientOriginalExtension());
        $tamanho = $arquivo->getSize();
        $nomeArquivo = Str::uuid().'.'.$extensao;
        $path = $arquivo->storeAs('chat/'.$conversa->id, $nomeArquivo, 'private');
        $tipoArquivo = $this->determinarTipoArquivo($extensao);

        $anexo = MensagemAnexo::create([
            'mensagem_id' => $mensagem->id,
            'nome_original' => $nomeOriginal,
            'nome_arquivo' => $nomeArquivo,
            'file_path' => $path,
            'tipo_arquivo' => $tipoArquivo,
            'extensao' => $extensao,
            'tamanho' => $tamanho,
        ]);

        return [
            'id' => $anexo->id,
            'nome_original' => $anexo->nome_original,
            'tipo_arquivo' => $anexo->tipo_arquivo,
            'tamanho' => $anexo->tamanho,
            'file_path' => $path,
            'preview_url' => route('chat.preview_anexo', $anexo->id),
            'download_url' => route('chat.download_anexo', $anexo->id),
        ];
    }

    private function formatarMensagemParaResposta($mensagem, $user, $anexoData = null)
    {
        $remetente = $mensagem->remetente;
        $nomeRemetente = $remetente ? $remetente->name : 'Usuário Removido';
        $iniciaisRemetente = $remetente ? mb_strtoupper(mb_substr($remetente->name, 0, 2, 'UTF-8')) : '??';

        $anexos = $anexoData ? [$anexoData] : $mensagem->anexos->map(function ($anexo) {
            return [
                'id' => $anexo->id,
                'nome_original' => $anexo->nome_original,
                'tipo_arquivo' => $anexo->tipo_arquivo,
                'tamanho' => $anexo->tamanho,
                'preview_url' => route('chat.preview_anexo', $anexo->id),
                'download_url' => route('chat.download_anexo', $anexo->id),
            ];
        })->toArray();

        return [
            'id' => $mensagem->id,
            'mensagem' => $mensagem->mensagem,
            'remetente_id' => $mensagem->remetente_id,
            'remetente_nome' => $nomeRemetente,
            'remetente_iniciais' => $iniciaisRemetente,
            'created_at' => $mensagem->created_at->format('d/m/Y H:i'),
            'lida' => $mensagem->lida,
            'is_mine' => $mensagem->remetente_id === $user->id,
            'anexos' => $anexos,
        ];
    }

    private function determinarTipoArquivo($extensao)
    {
        $tipos = [
            'jpg' => 'imagem', 'jpeg' => 'imagem', 'png' => 'imagem', 'gif' => 'imagem',
            'bmp' => 'imagem', 'webp' => 'imagem', 'mp4' => 'video', 'avi' => 'video',
            'mov' => 'video', 'wmv' => 'video', 'flv' => 'video', 'mkv' => 'video',
            'webm' => 'video', 'mpeg' => 'video', 'pdf' => 'pdf', 'doc' => 'doc',
            'docx' => 'doc', 'xls' => 'doc', 'xlsx' => 'doc', 'ppt' => 'doc',
            'pptx' => 'doc', 'txt' => 'doc', 'csv' => 'doc',
        ];

        return $tipos[strtolower($extensao)] ?? 'outro';
    }

    private function determinarNivelInicial($roleOrigem, $roleDestino)
    {
        $hierarquia = ['fornecedor' => 1, 'representante' => 1, 'operacional' => 2, 'gerencial' => 3, 'admin' => 4];

        $nivelOrigem = $hierarquia[$roleOrigem] ?? 2;
        $nivelDestino = $hierarquia[$roleDestino] ?? 2;
        $nivelMaior = max($nivelOrigem, $nivelDestino);

        foreach ($hierarquia as $role => $nivel) {
            if ($nivel === $nivelMaior) {
                return $role;
            }
        }

        return 'operacional';
    }

    private function obterNiveisPermitidos($roleAtual)
    {
        $permissoes = [
            'fornecedor' => ['operacional'],
            'representante' => ['operacional'],
            'operacional' => ['fornecedor', 'representante', 'operacional', 'gerencial'],
            'gerencial' => ['fornecedor', 'representante', 'operacional', 'gerencial', 'admin'],
            'admin' => ['fornecedor', 'representante', 'operacional', 'gerencial', 'admin'],
        ];

        return $permissoes[$roleAtual] ?? [];
    }

    private function obterProximoNivel($nivelAtual)
    {
        $niveis = [
            'fornecedor' => 'operacional',
            'representante' => 'operacional',
            'operacional' => 'gerencial',
            'gerencial' => 'admin',
            'admin' => null,
        ];

        return $niveis[$nivelAtual] ?? null;
    }

    private function podeConversarCom($roleOrigem, $roleDestino)
    {
        $niveisPermitidos = $this->obterNiveisPermitidos($roleOrigem);

        return in_array($roleDestino, $niveisPermitidos);
    }

    private function validarArquivo($arquivo)
    {
        if ($arquivo->getSize() > 45 * 1024 * 1024) {
            return ['valido' => false, 'mensagem' => 'Arquivo muito grande. Máximo 45MB.'];
        }

        $extensao = strtolower($arquivo->getClientOriginalExtension());

        $extensoesPermitidas = [
            'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp',
            'mp4', 'avi', 'mov', 'wmv', 'flv', 'mkv', 'webm', 'mpeg',
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv',
        ];

        if (! in_array($extensao, $extensoesPermitidas)) {
            return ['valido' => false, 'mensagem' => 'Extensão de arquivo não permitida.'];
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeReal = finfo_file($finfo, $arquivo->getRealPath());
        finfo_close($finfo);

        $mimeEsperado = $this->getMimeEsperado($extensao);

        if (! in_array($mimeReal, $mimeEsperado)) {
            Log::channel('security')->warning('Tentativa de upload de arquivo com MIME inválido', [
                'extensao' => $extensao,
                'mime_declarado' => $arquivo->getMimeType(),
                'mime_real' => $mimeReal,
                'ip' => request()->ip(),
            ]);

            return ['valido' => false, 'mensagem' => 'Arquivo suspeito detectado.'];
        }

        if (str_starts_with($mimeReal, 'image/')) {
            try {
                $imageInfo = @getimagesize($arquivo->getRealPath());
                if ($imageInfo === false) {
                    return ['valido' => false, 'mensagem' => 'Imagem corrompida ou inválida.'];
                }
            } catch (\Exception $e) {
                Log::channel('security')->warning('Upload rejeitado: imagem inválida', [
                    'nome_arquivo' => $arquivo->getClientOriginalName(),
                    'error' => $e->getMessage(),
                ]);
                return ['valido' => false, 'mensagem' => 'Arquivo de imagem inválido.'];
            }
        }

        $nomeArquivo = strtolower($arquivo->getClientOriginalName());
        $padroesMaliciosos = ['.php', '.exe', '.sh', '.bat', '.cmd', '.phtml', '.jsp'];
        foreach ($padroesMaliciosos as $padrao) {
            if (str_contains($nomeArquivo, $padrao)) {
                Log::channel('security')->error('Tentativa de upload de arquivo malicioso', [
                    'nome_arquivo' => $nomeArquivo,
                    'ip' => request()->ip(),
                ]);

                return ['valido' => false, 'mensagem' => 'Nome de arquivo suspeito detectado.'];
            }
        }

        return ['valido' => true];
    }

    private function getMimeEsperado($extensao)
    {
        $mapeamento = [
            'jpg' => ['image/jpeg'], 'jpeg' => ['image/jpeg'],
            'png' => ['image/png'], 'gif' => ['image/gif'],
            'bmp' => ['image/bmp', 'image/x-ms-bmp'], 'webp' => ['image/webp'],
            'mp4' => ['video/mp4'], 'avi' => ['video/x-msvideo', 'video/avi'],
            'mov' => ['video/quicktime'], 'wmv' => ['video/x-ms-wmv'],
            'flv' => ['video/x-flv'], 'mkv' => ['video/x-matroska'],
            'webm' => ['video/webm'], 'mpeg' => ['video/mpeg'],
            'pdf' => ['application/pdf', 'application/x-pdf'],
            'doc' => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'xls' => ['application/vnd.ms-excel'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            'ppt' => ['application/vnd.ms-powerpoint'],
            'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
            'txt' => ['text/plain'], 'csv' => ['text/plain', 'text/csv'],
        ];

        return $mapeamento[$extensao] ?? [];
    }

    private function selecionarOperacionalAutomatico($userId, $piId = null)
    {
        $query = User::whereHas('roles', function ($q) {
            $q->where('name', 'operacional');
        })->whereHas('genOp');

        if ($piId) {
            $pi = Pi::find($piId);
            if ($pi) {
                $atribuicaoPrincipal = \App\Models\PiAtribuicao::where('pi_id', $pi->id)
                    ->where('ativo', true)
                    ->where('principal', true)
                    ->whereNull('deleted_at')
                    ->first();

                if ($atribuicaoPrincipal) {
                    $operacionalDoPi = User::whereHas('genOp', function ($q) use ($atribuicaoPrincipal) {
                        $q->where('id', $atribuicaoPrincipal->operacional_id);
                    })->first();

                    if ($operacionalDoPi) {
                        return $operacionalDoPi;
                    }
                }

                if (! $atribuicaoPrincipal) {
                    $atribuicaoAtiva = \App\Models\PiAtribuicao::where('pi_id', $pi->id)
                        ->where('ativo', true)
                        ->whereNull('deleted_at')
                        ->first();

                    if ($atribuicaoAtiva) {
                        $operacionalDoPi = User::whereHas('genOp', function ($q) use ($atribuicaoAtiva) {
                            $q->where('id', $atribuicaoAtiva->operacional_id);
                        })->first();

                        if ($operacionalDoPi) {
                            return $operacionalDoPi;
                        }
                    }
                }

                if ($pi->operacional_id) {
                    $operacionalDoPi = User::whereHas('genOp', function ($q) use ($pi) {
                        $q->where('id', $pi->operacional_id);
                    })->first();

                    if ($operacionalDoPi) {
                        return $operacionalDoPi;
                    }
                }
            }
        }

        $operacionais = $query->get();

        if ($operacionais->isEmpty()) {
            return null;
        }

        $operacionaisAtivos = $operacionais->filter(function ($op) {
            return $op->estaAtivoRecentemente(24);
        });

        if ($operacionaisAtivos->isEmpty()) {
            return $operacionais->sortBy(function ($op) {
                return $op->totalConversasAbertas();
            })->first();
        }

        $userIdsHistorico = [];
        $usuarioOrigem = User::with(['fornecedor', 'representanteUsuario'])->find($userId);

        if ($usuarioOrigem) {
            if ($usuarioOrigem->fornecedor) {
                $userIdsHistorico[] = $usuarioOrigem->id;
            }

            if ($usuarioOrigem->representanteUsuario) {
                $fornecedorIds = $usuarioOrigem->representanteUsuario->getFornecedorIds();
                if (! empty($fornecedorIds)) {
                    $usersDosFornecedores = \App\Models\Fornecedor::whereIn('id', $fornecedorIds)
                        ->whereNotNull('user_id')
                        ->pluck('user_id')
                        ->toArray();
                    $userIdsHistorico = array_merge($userIdsHistorico, $usersDosFornecedores);
                }
            }
        }

        $userIdsHistorico = array_unique($userIdsHistorico);

        $conversasAnteriores = [];
        if (! empty($userIdsHistorico)) {
            $conversasAnteriores = ConversaParticipante::where('participante_type', User::class)
                ->whereIn('participante_id', $userIdsHistorico)
                ->pluck('conversa_id')
                ->toArray();
        }

        $operacionaisComHistorico = [];
        if (! empty($conversasAnteriores)) {
            foreach ($operacionaisAtivos as $op) {
                $temHistorico = ConversaParticipante::where('participante_type', User::class)
                    ->where('participante_id', $op->id)
                    ->whereIn('conversa_id', $conversasAnteriores)
                    ->exists();

                if ($temHistorico) {
                    $operacionaisComHistorico[] = $op;
                }
            }
        }

        if (! empty($operacionaisComHistorico)) {
            $operacionaisAtivos = collect($operacionaisComHistorico);
        }

        return $operacionaisAtivos->sortBy(function ($op) {
            $carga = $op->cargaDeTrabalho();

            return ($carga['conversas_abertas'] * 2) + $carga['mensagens_nao_lidas'];
        })->first();
    }

    public function assumirConversa(Conversa $conversa)
    {
        $user = Auth::user();
        $userRole = $this->getUserRole($user);

        if (! in_array($userRole, ['gerencial', 'admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Apenas Gerenciais ou Admins podem assumir conversas.',
            ], 403);
        }

        if (! $this->usuarioPodeAcessarConversa($user, $conversa)) {
            return response()->json([
                'success' => false,
                'message' => 'Você não tem acesso a esta conversa.',
            ], 403);
        }

        if ($conversa->status_conversa === 'resolvida') {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível assumir uma conversa resolvida.',
            ], 422);
        }

        $jaParticipa = ConversaParticipante::where('conversa_id', $conversa->id)
            ->where('participante_type', User::class)
            ->where('participante_id', $user->id)
            ->whereNull('data_saida')
            ->exists();

        if ($jaParticipa) {
            return response()->json([
                'success' => false,
                'message' => 'Você já participa desta conversa.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $operacionaisRemovidos = ConversaParticipante::where('conversa_id', $conversa->id)
                ->where('participante_type', User::class)
                ->where('nivel_participante', 'operacional')
                ->whereNull('data_saida')
                ->with('participante')
                ->get();

            foreach ($operacionaisRemovidos as $op) {
                $op->update(['data_saida' => now()]);
            }

            ConversaParticipante::create([
                'conversa_id' => $conversa->id,
                'participante_type' => User::class,
                'participante_id' => $user->id,
                'data_entrada' => now(),
                'nivel_participante' => $userRole,
            ]);

            $novoStatus = $conversa->status_conversa === 'aberta' ? 'escalada' : $conversa->status_conversa;
            $conversa->update([
                'nivel_atual' => $userRole,
                'status_conversa' => $novoStatus,
            ]);

            $nomesOps = $operacionaisRemovidos->map(function ($op) {
                return optional($op->participante)->name ?? 'Operacional';
            })->filter()->implode(', ');

            $textoSistema = $user->name.' ('.ucfirst($userRole).') assumiu a conversa.';
            if ($nomesOps !== '') {
                $textoSistema .= ' Operacional anterior removido: '.$nomesOps.'.';
            }

            Mensagem::create([
                'conversa_id' => $conversa->id,
                'remetente_type' => User::class,
                'remetente_id' => $user->id,
                'mensagem' => $textoSistema,
                'lida' => true,
                'data_leitura' => now(),
            ]);

            $conversa->touch();
            $user->registrarAtividade('Assumiu conversa: '.$conversa->assunto);

            $idsParaInvalidar = $operacionaisRemovidos->pluck('participante_id')
                ->push($user->id)
                ->unique()
                ->toArray();

            foreach ($idsParaInvalidar as $uid) {
                Cache::forget("unread_messages_{$uid}");
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Conversa assumida com sucesso.',
                'reload' => true,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('chat')->error('Erro ao assumir conversa', [
                'conversa_id' => $conversa->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao assumir conversa.',
            ], 500);
        }
    }

    public function getUnreadCount()
    {
        $user = Auth::user();

        $conversasOndeEhParticipante = ConversaParticipante::where('participante_type', User::class)
            ->where('participante_id', $user->id)
            ->whereNull('data_saida')
            ->whereHas('conversa', function ($q) {
                $q->whereNull('deleted_at');
            })
            ->pluck('conversa_id');

        $totalNaoLidas = Mensagem::whereIn('conversa_id', $conversasOndeEhParticipante)
            ->where(function ($q) use ($user) {
                $q->where('remetente_type', '!=', User::class)
                    ->orWhere('remetente_id', '!=', $user->id);
            })
            ->where('lida', false)
            ->whereNull('deleted_at')
            ->count();

        return response()->json(['count' => $totalNaoLidas]);
    }
}