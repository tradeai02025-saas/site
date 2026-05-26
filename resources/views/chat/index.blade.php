@extends('layouts.app')

@section('title', 'Mensagens')

@section('content')
    <div class="container-fluid">
        <link href="{{ asset('css/pages/chat.css') }}" rel="stylesheet">
        <div class="page-header d-flex align-items-center justify-content-between">
            <div>
                <h1 class="page-title">
                    <i class="bi bi-chat-dots-fill"></i>
                    Mensagens
                </h1>
                <p class="page-subtitle">Gerencie suas conversas e comunicações</p>
            </div>
            <button type="button" class="btn-create" data-bs-toggle="modal" data-bs-target="#modalNovaConversa">
                <i class="bi bi-plus-circle-fill"></i>
                <span>Nova Conversa</span>
            </button>
        </div>

        <div class="stats-filters">
            <a href="{{ route('chat.index', array_filter(['busca' => $busca ?? null])) }}"
                class="filter-stat-card filter-stat-link {{ ($filtro ?? 'todas') === 'todas' ? 'active' : '' }}"
                data-filter="todas">
                <div class="filter-stat-content">
                    <div class="filter-stat-icon primary">
                        <i class="bi bi-chat-fill"></i>
                    </div>
                    <div class="filter-stat-info">
                        <div class="filter-stat-label">Todas</div>
                        <div class="filter-stat-value">{{ $contagens['total'] ?? 0 }}</div>
                    </div>
                </div>
            </a>

            <a href="{{ route('chat.index', array_filter(['filtro' => 'nao-lidas', 'busca' => $busca ?? null])) }}"
                class="filter-stat-card filter-stat-link {{ ($filtro ?? '') === 'nao-lidas' ? 'active' : '' }}"
                data-filter="nao-lidas">
                <div class="filter-stat-content">
                    <div class="filter-stat-icon danger">
                        <i class="bi bi-envelope-fill"></i>
                    </div>
                    <div class="filter-stat-info">
                        <div class="filter-stat-label">Não Lidas</div>
                        <div class="filter-stat-value" id="totalNaoLidas">{{ $contagens['nao_lidas'] ?? 0 }}</div>
                    </div>
                </div>
            </a>

            <a href="{{ route('chat.index', array_filter(['filtro' => 'abertas', 'busca' => $busca ?? null])) }}"
                class="filter-stat-card filter-stat-link {{ ($filtro ?? '') === 'abertas' ? 'active' : '' }}"
                data-filter="abertas">
                <div class="filter-stat-content">
                    <div class="filter-stat-icon info">
                        <i class="bi bi-chat-left-dots-fill"></i>
                    </div>
                    <div class="filter-stat-info">
                        <div class="filter-stat-label">Abertas</div>
                        <div class="filter-stat-value">{{ $contagens['abertas'] ?? 0 }}</div>
                    </div>
                </div>
            </a>

            <a href="{{ route('chat.index', array_filter(['filtro' => 'resolvidas', 'busca' => $busca ?? null])) }}"
                class="filter-stat-card filter-stat-link {{ ($filtro ?? '') === 'resolvidas' ? 'active' : '' }}"
                data-filter="resolvidas">
                <div class="filter-stat-content">
                    <div class="filter-stat-icon success">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <div class="filter-stat-info">
                        <div class="filter-stat-label">Resolvidas</div>
                        <div class="filter-stat-value">{{ $contagens['resolvidas'] ?? 0 }}</div>
                    </div>
                </div>
            </a>

            <a href="{{ route('chat.index', array_filter(['filtro' => 'escaladas', 'busca' => $busca ?? null])) }}"
                class="filter-stat-card filter-stat-link {{ ($filtro ?? '') === 'escaladas' ? 'active' : '' }}"
                data-filter="escaladas">
                <div class="filter-stat-content">
                    <div class="filter-stat-icon warning">
                        <i class="bi bi-arrow-up-circle-fill"></i>
                    </div>
                    <div class="filter-stat-info">
                        <div class="filter-stat-label">Escaladas</div>
                        <div class="filter-stat-value">{{ $contagens['escaladas'] ?? 0 }}</div>
                    </div>
                </div>
            </a>
        </div>

        <div class="search-section">
            <form method="GET" action="{{ route('chat.index') }}" id="formBuscaConversas">
                @if (!empty($filtro) && $filtro !== 'todas')
                    <input type="hidden" name="filtro" value="{{ $filtro }}">
                @endif
                <div class="search-box">
                    <i class="bi bi-search"></i>
                    <input type="text" id="searchConversas" name="busca"
                        class="form-control" value="{{ $busca ?? '' }}"
                        placeholder="Buscar por assunto, PI ou mensagens...">
                </div>
            </form>
        </div>

        <div class="conversas-container" id="conversasList">
            @forelse($conversas as $conversa)
                @php
                    $mensagensNaoLidas = $conversa->mensagens_nao_lidas_count ?? 0;
                    $ultimaMensagem = $conversa->mensagens->first();
                    $tempoDecorrido = $conversa->updated_at->diffForHumans();
                    $isCriador = $conversa->criador_id === auth()->id();

                    $role = auth()->user()->roles->first();
                    $userRole = $role ? $role->name : null;
                    $isAdmin = $userRole === 'admin';
                    $podeGerenciar = in_array($userRole, ['operacional', 'gerencial', 'admin']);
                @endphp

                <div class="conversa-card {{ $mensagensNaoLidas > 0 ? 'nao-lida' : '' }} {{ $conversa->status_conversa === 'resolvida' ? 'resolvida' : '' }}"
                    data-status="{{ $conversa->status_conversa }}" data-conversa-id="{{ $conversa->id }}">

                    <div class="conversa-main">
                        @if ($podeGerenciar || $isAdmin)
                            <div class="conversa-actions">
                                @if ($conversa->status_conversa === 'aberta' || $conversa->status_conversa === 'escalada')
                                    @if ($podeGerenciar)
                                        <button class="btn-action btn-resolver" data-id="{{ $conversa->id }}"
                                            title="Marcar como resolvida">
                                            <i class="bi bi-check-circle"></i>
                                        </button>
                                    @endif
                                @elseif($conversa->status_conversa === 'resolvida')
                                    @if ($podeGerenciar)
                                        <button class="btn-action btn-reabrir" data-id="{{ $conversa->id }}"
                                            title="Reabrir conversa">
                                            <i class="bi bi-arrow-clockwise"></i>
                                        </button>
                                    @endif
                                @endif
                                @if ($isAdmin)
                                    <button class="btn-action danger btn-excluir" data-id="{{ $conversa->id }}"
                                        title="Excluir conversa">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                @endif
                            </div>
                        @endif

                        <div class="conversa-top">
                            <div class="conversa-header-info">
                                <h3 class="conversa-titulo">
                                    @if ($conversa->pi_id)
                                        <i class="bi bi-file-earmark-text-fill"></i>
                                    @endif
                                    {{ $conversa->assunto }}
                                </h3>
                                <div class="conversa-meta-info">
                                    @if ($conversa->pi_id)
                                        <span class="meta-item">
                                            <i class="bi bi-hash"></i>
                                            PI {{ $conversa->pi->numero_pi ?? $conversa->pi_id }}
                                        </span>
                                    @endif
                                    <span class="meta-item">
                                        <i class="bi bi-person-fill"></i>
                                        {{ $conversa->participantes->count() }}
                                        {{ $conversa->participantes->count() === 1 ? 'participante' : 'participantes' }}
                                    </span>
                                    @if ($isCriador)
                                        <span class="badge-criador">
                                            <i class="bi bi-star-fill"></i>
                                            Criador
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="conversa-status-group">
                                <span class="badge-status {{ $conversa->status_conversa }}">
                                    @if ($conversa->status_conversa === 'aberta')
                                        <i class="bi bi-circle-fill"></i>
                                    @elseif($conversa->status_conversa === 'resolvida')
                                        <i class="bi bi-check-circle-fill"></i>
                                    @else
                                        <i class="bi bi-arrow-up-circle-fill"></i>
                                    @endif
                                    {{ ucfirst($conversa->status_conversa) }}
                                </span>
                            </div>
                        </div>

                        @if ($ultimaMensagem)
                            <p class="conversa-mensagem">
                                <strong>{{ $ultimaMensagem->remetente?->name ?? 'Usuário Removido' }}:</strong>
                                {{ Str::limit($ultimaMensagem->mensagem, 150) }}
                            </p>
                        @endif
                    </div>

                    <div class="conversa-footer">
                        <div class="participantes-section">
                            <div class="participantes-avatars">
                                @foreach ($conversa->participantes->take(4) as $participante)
                                    @php
                                        $nomeParticipante = $participante->participante?->name ?? 'Usuário Removido';
                                        $iniciais = $participante->participante
                                            ? mb_strtoupper(mb_substr($participante->participante->name, 0, 2, 'UTF-8'))
                                            : '??';
                                    @endphp
                                    <div class="avatar-item" title="{{ $nomeParticipante }}">
                                        {{ $iniciais }}
                                    </div>
                                @endforeach
                                @if ($conversa->participantes->count() > 4)
                                    <div class="avatar-item" title="{{ $conversa->participantes->count() - 4 }} mais">
                                        +{{ $conversa->participantes->count() - 4 }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="conversa-info-section">
                            <div class="tempo-info">
                                <i class="bi bi-clock-fill"></i>
                                {{ $tempoDecorrido }}
                            </div>
                            @if ($mensagensNaoLidas > 0)
                                <span class="badge-nao-lidas-destaque">
                                    <i class="bi bi-envelope-fill"></i>
                                    {{ $mensagensNaoLidas }} {{ $mensagensNaoLidas === 1 ? 'nova' : 'novas' }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    <i class="bi bi-chat-left-dots big-icon"></i>
                    <h3>Nenhuma conversa encontrada</h3>
                    <p>Comece uma nova conversa para manter contato com sua equipe</p>
                    <button type="button" class="btn-create m-0-auto" data-bs-toggle="modal"
                        data-bs-target="#modalNovaConversa">
                        <i class="bi bi-plus-circle-fill"></i>
                        <span>Iniciar Primeira Conversa</span>
                    </button>
                </div>
            @endforelse
        </div>

        @include('layouts.trade-pagination', ['paginator' => $conversas])

    </div>

    <div class="modal fade" id="modalNovaConversa" tabindex="-1" aria-labelledby="modalNovaConversaLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalNovaConversaLabel">
                        <i class="bi bi-plus-circle-fill"></i>
                        Nova Conversa
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <form id="formNovaConversa">
                    @csrf
                    <div class="modal-body">
                        <div class="alert-duplicate" id="alertDuplicate">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <div>
                                <strong>Atenção!</strong> Já existe uma conversa ativa com este usuário.
                                <a href="#" id="linkConversaExistente" target="_blank">Acessar conversa
                                    existente</a>
                            </div>
                        </div>

                        @php
                            $userRole = auth()->user()->roles->first()?->name ?? 'fornecedor';
                        @endphp

                        @if ($userRole !== 'fornecedor')
                            <div class="mb-4">
                                <label class="form-label">
                                    <i class="bi bi-person-fill"></i>
                                    Destinatário
                                    <span class="text-danger">*</span>
                                </label>

                                <div class="destinatario-selector">
                                    <input type="text" class="search-input-destinatario" id="searchDestinatario"
                                        placeholder="Digite pelo menos 2 caracteres..." autocomplete="off">
                                    <div class="spinner-border spinner-border-sm search-spinner" id="loadingDestinatario">
                                    </div>

                                    <div class="dropdown-usuarios" id="dropdownUsuarios">
                                        <div class="dropdown-empty">
                                            <i class="bi bi-search"></i>
                                            <p>Digite pelo menos 2 caracteres para buscar</p>
                                        </div>
                                    </div>
                                </div>

                                <input type="hidden" name="destinatario_id" id="destinatario_id">

                                <div class="selected-destinatario-preview" id="previewDestinatario">
                                    <div class="selected-destinatario-avatar" id="previewAvatar">JD</div>
                                    <div class="selected-destinatario-info">
                                        <div class="selected-destinatario-name" id="previewNome">João da Silva</div>
                                        <div class="selected-destinatario-role" id="previewDetalhes">joao@empresa.com
                                        </div>
                                    </div>
                                    <button type="button" class="btn-remove-destinatario" id="btnRemoveDestinatario">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                            </div>
                        @else
                            <div class="mb-4">
                                <div class="alert alert-info d-flex align-items-center gap-3"
                                    style="border-radius: 12px;">
                                    <i class="bi bi-info-circle-fill" style="font-size: 1.5rem;"></i>
                                    <div>
                                        <strong>Sistema Automático</strong><br>
                                        Sua conversa será direcionada automaticamente para um operacional disponível.
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="mb-4">
                            <label for="assunto" class="form-label">
                                <i class="bi bi-chat-text-fill"></i>
                                Assunto
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="assunto" name="assunto"
                                placeholder="Ex: Dúvida sobre documentação" maxlength="255">
                            <div class="char-counter">
                                <span id="assuntoCount">0</span>/255
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="pi_selector" class="form-label">
                                <i class="bi bi-file-earmark-text-fill"></i>
                                PI Relacionado (Opcional)
                            </label>
                            <div class="pi-selector">
                                <input type="text" class="form-control" id="searchPi"
                                    placeholder="Digite para buscar um PI..." autocomplete="off">
                                <div class="dropdown-pis" id="dropdownPis"></div>
                            </div>
                            <input type="hidden" name="pi_id" id="pi_id">
                            <small class="form-text">Vincule a um PI ou deixe em branco para conversas gerais</small>
                        </div>

                        <div class="mb-3">
                            <label for="mensagem_inicial" class="form-label">
                                <i class="bi bi-chat-left-text-fill"></i>
                                Mensagem Inicial
                                <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" id="mensagem_inicial" name="mensagem_inicial" placeholder="Digite sua mensagem..."
                                maxlength="5000" rows="5"></textarea>
                            <div class="char-counter">
                                <span id="mensagemCount">0</span>/5000
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle"></i>
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary" id="btnSubmitConversa">
                            <i class="bi bi-send-fill"></i>
                            Iniciar Conversa
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            const userRole = window.AppRoutes.userRole;
            let destinatarioSelecionado = null;
            let piSelecionado = null;
            let timeoutBusca = null;
            let timeoutPi = null;
            let mensagensJaNotificadas = new Set();
            let primeiraCarga = true;

            $(document).on('click', '#btnAtivarNotificacoes', function() {
                if (typeof Notification !== 'undefined' && Notification.permission === 'default') {
                    Notification.requestPermission().then(function(perm) {
                        if (perm === 'granted') {
                            $('#btnAtivarNotificacoes').remove();
                        }
                    });
                }
            });

            carregarNaoLidas();
            setInterval(carregarNaoLidas, 15000);

            $('#assunto').on('input', function() {
                const count = $(this).val().length;
                $('#assuntoCount').text(count);
                if (count > 255) {
                    $(this).addClass('is-invalid');
                } else {
                    $(this).removeClass('is-invalid');
                }
            });

            $('#mensagem_inicial').on('input', function() {
                const count = $(this).val().length;
                $('#mensagemCount').text(count);
                if (count > 5000) {
                    $(this).addClass('is-invalid');
                } else {
                    $(this).removeClass('is-invalid');
                }
            });

            $(document).on('click', '.conversa-card', function(e) {
                if (!$(e.target).closest('.conversa-actions').length) {
                    const conversaId = $(this).data('conversa-id');
                    if (conversaId) {
                        window.location.href = window.AppRoutes.show.replace(':id', conversaId);
                    }
                }
            });

            $(document).on('click', '.conversa-actions', function(e) {
                e.stopPropagation();
            });

            $('#searchConversas').on('keyup', debounce(function(e) {
                if (e.key === 'Enter') {
                    $('#formBuscaConversas').submit();
                }
            }, 300));

            $('#searchConversas').on('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    $('#formBuscaConversas').submit();
                }
            });

            if (userRole !== 'fornecedor') {
                $('#searchDestinatario').on('input', function() {
                    const search = $(this).val().trim();
                    clearTimeout(timeoutBusca);

                    if (search.length < 2) {
                        $('#dropdownUsuarios').html(`
                    <div class="dropdown-empty">
                        <i class="bi bi-info-circle"></i>
                        <p>Digite pelo menos 2 caracteres</p>
                    </div>
                `).removeClass('show');
                        $('#loadingDestinatario').removeClass('show');
                        return;
                    }

                    $('#loadingDestinatario').addClass('show');
                    timeoutBusca = setTimeout(() => buscarUsuarios(search), 400);
                });

                $('#btnRemoveDestinatario').on('click', function(e) {
                    e.preventDefault();
                    destinatarioSelecionado = null;
                    $('#destinatario_id').val('');
                    $('#previewDestinatario').removeClass('show');
                    $('#searchDestinatario').val('').focus();
                    $('#alertDuplicate').removeClass('show');
                });
            }

            $('#searchPi').on('input', function() {
                const search = $(this).val().trim();
                clearTimeout(timeoutPi);

                if (search.length === 0) {
                    $('#dropdownPis').removeClass('show');
                    return;
                }

                timeoutPi = setTimeout(() => buscarPis(search), 400);
            });

            $(document).on('click', function(e) {
                if (!$(e.target).closest('.destinatario-selector').length) {
                    $('#dropdownUsuarios').removeClass('show');
                }
                if (!$(e.target).closest('.pi-selector').length) {
                    $('#dropdownPis').removeClass('show');
                }
            });

            $(document).on('click', '.btn-resolver', function(e) {
                e.stopPropagation();
                const conversaId = $(this).data('id');

                Swal.fire({
                    title: 'Resolver conversa?',
                    text: 'Deseja marcar esta conversa como resolvida?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: '<i class="bi bi-check-circle"></i> Sim, resolver',
                    cancelButtonText: '<i class="bi bi-x-circle"></i> Cancelar',
                    customClass: {
                        popup: 'swal-modern',
                        confirmButton: 'swal-btn swal-btn-success',
                        cancelButton: 'swal-btn swal-btn-secondary'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        resolverConversa(conversaId);
                    }
                });
            });

            $(document).on('click', '.btn-reabrir', function(e) {
                e.stopPropagation();
                const conversaId = $(this).data('id');

                Swal.fire({
                    title: 'Reabrir conversa?',
                    text: 'Deseja reabrir esta conversa?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: '<i class="bi bi-arrow-clockwise"></i> Sim, reabrir',
                    cancelButtonText: '<i class="bi bi-x-circle"></i> Cancelar',
                    customClass: {
                        popup: 'swal-modern',
                        confirmButton: 'swal-btn swal-btn-success',
                        cancelButton: 'swal-btn swal-btn-secondary'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        reabrirConversa(conversaId);
                    }
                });
            });

            $(document).on('click', '.btn-excluir', function(e) {
                e.stopPropagation();
                const conversaId = $(this).data('id');

                Swal.fire({
                    title: 'Excluir conversa?',
                    html: `
                <div class="swal-content">
                    <p class="swal-text">Esta ação não pode ser desfeita!</p>
                    <div class="swal-info-box swal-warning">
                        <i class="bi bi-info-circle-fill"></i>
                        <span>Todas as mensagens serão permanentemente removidas.</span>
                    </div>
                </div>
            `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: '<i class="bi bi-trash"></i> Sim, excluir',
                    cancelButtonText: '<i class="bi bi-x-circle"></i> Cancelar',
                    customClass: {
                        popup: 'swal-modern',
                        confirmButton: 'swal-btn swal-btn-danger',
                        cancelButton: 'swal-btn swal-btn-secondary'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        excluirConversa(conversaId);
                    }
                });
            });

            $('#modalNovaConversa').on('shown.bs.modal', function() {
                if (userRole === 'fornecedor') {
                    $('#assunto').focus();
                } else {
                    $('#searchDestinatario').focus();
                }
            });

            $('#modalNovaConversa').on('hidden.bs.modal', function() {
                resetarModal();
            });

            function buscarUsuarios(search) {
                $.ajax({
                    url: window.AppRoutes.buscarUsuarios,
                    method: 'GET',
                    data: {
                        search: search
                    },
                    success: function(response) {
                        $('#loadingDestinatario').removeClass('show');

                        if (response.success && response.usuarios.length > 0) {
                            const usuarios = response.usuarios.slice(0, 5);
                            let html = usuarios.map(u => {
                                return `
                            <div class="dropdown-item-usuario" data-usuario='${JSON.stringify(u)}'>
                                <div class="dropdown-usuario-avatar">${u.iniciais}</div>
                                <div class="dropdown-usuario-info">
                                    <div class="dropdown-usuario-name">${u.name}</div>
                                    <div class="dropdown-usuario-details">${u.email}</div>
                                </div>
                            </div>
                        `;
                            }).join('');

                            if (response.usuarios.length > 5) {
                                html +=
                                    `<div class="dropdown-empty" style="padding: 0.75rem;"><small style="opacity: 0.7;">Mostrando 5 de ${response.usuarios.length} resultados</small></div>`;
                            }

                            $('#dropdownUsuarios').html(html).addClass('show');

                            $('.dropdown-item-usuario').on('click', function() {
                                const usuario = JSON.parse($(this).attr('data-usuario'));
                                selecionarDestinatario(usuario);
                            });
                        } else {
                            $('#dropdownUsuarios').html(`
                        <div class="dropdown-empty">
                            <i class="bi bi-person-x"></i>
                            <p>Nenhum usuário encontrado</p>
                        </div>
                    `).addClass('show');
                        }
                    },
                    error: function() {
                        $('#loadingDestinatario').removeClass('show');
                        Swal.fire('Erro', 'Erro ao buscar usuários', 'error');
                    }
                });
            }

            function buscarPis(search) {
                $.ajax({
                    url: window.AppRoutes.buscarPis,
                    method: 'GET',
                    data: {
                        search: search
                    },
                    success: function(response) {
                        if (response.success && response.pis.length > 0) {
                            let html = response.pis.map(pi => `
                        <div class="dropdown-item-pi" data-pi='${JSON.stringify(pi)}'>
                            <div class="dropdown-pi-icon">
                                <i class="bi bi-file-earmark-text-fill"></i>
                            </div>
                            <div class="dropdown-pi-info">
                                <div class="dropdown-pi-numero">#${pi.numero_pi}</div>
                                <div class="dropdown-pi-details">${pi.cliente} • ${pi.status}</div>
                            </div>
                        </div>
                    `).join('');

                            $('#dropdownPis').html(html).addClass('show');

                            $('.dropdown-item-pi').on('click', function() {
                                const pi = JSON.parse($(this).attr('data-pi'));
                                selecionarPi(pi);
                            });
                        } else {
                            $('#dropdownPis').html(`
                        <div class="dropdown-empty">
                            <i class="bi bi-file-x"></i>
                            <p>Nenhum PI encontrado</p>
                        </div>
                    `).addClass('show');
                        }
                    },
                    error: function() {
                        Swal.fire('Erro', 'Erro ao buscar PIs', 'error');
                    }
                });
            }

            function selecionarDestinatario(usuario) {
                destinatarioSelecionado = usuario;
                $('#destinatario_id').val(usuario.id);
                $('#previewAvatar').text(usuario.iniciais);
                $('#previewNome').text(usuario.name);
                $('#previewDetalhes').text(usuario.email);
                $('#previewDestinatario').addClass('show');
                $('#dropdownUsuarios').removeClass('show');
                $('#searchDestinatario').val('');
                setTimeout(() => $('#assunto').focus(), 100);
            }

            function selecionarPi(pi) {
                piSelecionado = pi;
                $('#pi_id').val(pi.id);
                $('#searchPi').val(`#${pi.numero_pi} - ${pi.cliente}`);
                $('#dropdownPis').removeClass('show');
            }

            function resolverConversa(conversaId) {
                $.ajax({
                    url: window.AppRoutes.resolverConversa.replace(':id', conversaId),
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        Swal.fire('Sucesso', response.message, 'success');
                        setTimeout(() => location.reload(), 1000);
                    },
                    error: function(xhr) {
                        Swal.fire('Erro', xhr.responseJSON && xhr.responseJSON.message ||
                            'Erro ao resolver conversa', 'error');
                    }
                });
            }

            function reabrirConversa(conversaId) {
                $.ajax({
                    url: window.AppRoutes.reabrirConversa.replace(':id', conversaId),
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        Swal.fire('Sucesso', response.message, 'success');
                        setTimeout(() => location.reload(), 1000);
                    },
                    error: function(xhr) {
                        Swal.fire('Erro', xhr.responseJSON && xhr.responseJSON.message ||
                            'Erro ao reabrir conversa', 'error');
                    }
                });
            }

            function excluirConversa(conversaId) {
                $.ajax({
                    url: window.AppRoutes.excluirConversa.replace(':id', conversaId),
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        $(`.conversa-card[data-conversa-id="${conversaId}"]`).fadeOut(300, function() {
                            $(this).remove();

                            Swal.fire({
                                title: 'Conversa Excluída!',
                                html: `
                <div class="swal-content">
                    <p class="swal-text">${response.message}</p>
                    <div class="swal-info-box">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>A conversa foi removida com sucesso do sistema.</span>
                    </div>
                </div>
            `,
                                icon: 'success',
                                timer: 2500,
                                timerProgressBar: true,
                                showConfirmButton: false,
                                allowOutsideClick: false,
                                allowEscapeKey: false,
                                customClass: {
                                    popup: 'swal-modern'
                                }
                            }).then(() => {
                                if ($('.conversa-card').length === 0) {
                                    $('#conversasList').html(`
                    <div class="empty-state">
                        <i class="bi bi-chat-left-dots big-icon"></i>
                        <h3>Nenhuma conversa encontrada</h3>
                        <p>Comece uma nova conversa para manter contato com sua equipe</p>
                        <button type="button" class="btn-create" data-bs-toggle="modal" data-bs-target="#modalNovaConversa">
                            <i class="bi bi-plus-circle-fill"></i>
                            <span>Iniciar Primeira Conversa</span>
                        </button>
                    </div>
                `);
                                }
                            });
                        });
                    },
                    error: function(xhr) {
                        Swal.fire('Erro', xhr.responseJSON && xhr.responseJSON.message ||
                            'Erro ao excluir conversa', 'error');
                    }
                });
            }

            function resetarModal() {
                $('#formNovaConversa')[0].reset();
                destinatarioSelecionado = null;
                piSelecionado = null;
                $('#destinatario_id').val('');
                $('#pi_id').val('');
                $('#searchDestinatario').val('');
                $('#searchPi').val('');
                $('#previewDestinatario').removeClass('show');
                $('#loadingDestinatario').removeClass('show');
                $('#alertDuplicate').removeClass('show');
                $('#assuntoCount').text('0');
                $('#mensagemCount').text('0');
                $('#dropdownUsuarios').html(`
            <div class="dropdown-empty">
                <i class="bi bi-search"></i>
                <p>Digite pelo menos 2 caracteres para buscar</p>
            </div>
        `).removeClass('show');
                $('#dropdownPis').removeClass('show');
                $('#btnSubmitConversa').prop('disabled', false).html(
                    '<i class="bi bi-send-fill"></i> Iniciar Conversa');
            }

            function carregarNaoLidas() {
                $.ajax({
                    url: window.AppRoutes.conversasComNaoLidas,
                    method: 'GET',
                    success: function(response) {
                        if (!response.success) return;

                        const idsAtuais = (response.conversas_ids || []).map(String);
                        const idsAnteriores = Array.from(mensagensJaNotificadas);

                        $('#totalNaoLidas').text(idsAtuais.length);

                        const novosIds = idsAtuais.filter(function(id) {
                            return !mensagensJaNotificadas.has(id);
                        });

                        idsAtuais.forEach(function(id) {
                            mensagensJaNotificadas.add(id);
                        });

                        idsAnteriores.forEach(function(id) {
                            if (!idsAtuais.includes(id)) {
                                mensagensJaNotificadas.delete(id);
                            }
                        });

                        idsAtuais.forEach(function(id) {
                            const $card = $(`.conversa-card[data-conversa-id="${id}"]`);
                            if ($card.length && !$card.hasClass('nao-lida')) {
                                $card.addClass('nao-lida');
                            }
                        });

                        $('.conversa-card.nao-lida').each(function() {
                            const cardId = String($(this).data('conversa-id'));
                            if (!idsAtuais.includes(cardId)) {
                                $(this).removeClass('nao-lida');
                            }
                        });

                        if (!primeiraCarga && novosIds.length > 0) {
                            if (typeof Notification !== 'undefined' && Notification.permission === 'granted' && document.hidden) {
                                novosIds.forEach(function(id) {
                                    const $card = $(`.conversa-card[data-conversa-id="${id}"]`);
                                    const titulo = $card.find('.conversa-titulo').text().trim() || 'Nova mensagem';
                                    new Notification('Nova mensagem no chat', {
                                        body: titulo,
                                        icon: '/favicon.ico',
                                        tag: 'chat-' + id
                                    });
                                });
                            }
                        }

                        primeiraCarga = false;
                    }
                });
            }

            function limparMensagensOrfas() {
                $.ajax({
                    url: window.AppRoutes.limparMsgOrfas,
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            setTimeout(carregarNaoLidas, 500);
                        }
                    },
                    error: function(xhr) {
                        console.error('Erro ao limpar mensagens órfãs:', xhr);
                    }
                });
            }

            $('#formNovaConversa').on('submit', function(e) {
                e.preventDefault();

                if (userRole !== 'fornecedor' && !$('#destinatario_id').val()) {
                    Swal.fire('Atenção', 'Selecione um destinatário', 'warning');
                    $('#searchDestinatario').focus();
                    return;
                }

                if (!$('#assunto').val().trim()) {
                    Swal.fire('Atenção', 'Digite um assunto para a conversa', 'warning');
                    $('#assunto').focus();
                    return;
                }

                if (!$('#mensagem_inicial').val().trim()) {
                    Swal.fire('Atenção', 'Digite uma mensagem inicial', 'warning');
                    $('#mensagem_inicial').focus();
                    return;
                }

                const btn = $('#btnSubmitConversa');
                btn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-2"></span>Criando...');

                $.ajax({
                    url: window.AppRoutes.criarConversa,
                    method: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Sucesso', response.message, 'success');
                            setTimeout(() => window.location.href = response.redirect_url, 500);
                        }
                    },
                    error: function(xhr) {
                        btn.prop('disabled', false).html(
                            '<i class="bi bi-send-fill"></i> Iniciar Conversa');

                        if (xhr.status === 409) {
                            $('#alertDuplicate').addClass('show');
                            $('#linkConversaExistente').attr('href', xhr.responseJSON
                                .redirect_url);

                            Swal.fire({
                                title: 'Conversa Existente',
                                text: 'Já existe uma conversa ativa com este usuário.',
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonText: '<i class="bi bi-box-arrow-right"></i> Ir para conversa',
                                cancelButtonText: '<i class="bi bi-x-circle"></i> Cancelar',
                                customClass: {
                                    popup: 'swal-modern',
                                    confirmButton: 'swal-btn swal-btn-success',
                                    cancelButton: 'swal-btn swal-btn-secondary'
                                },
                                buttonsStyling: false
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = xhr.responseJSON
                                        .redirect_url;
                                }
                            });
                        } else if (xhr.status === 403) {
                            Swal.fire('Sem Permissão',
                                'Você não pode conversar diretamente com este usuário. Tente escalar através do nível apropriado.',
                                'error');
                        } else if (xhr.status === 503) {
                            Swal.fire('Serviço Indisponível', xhr.responseJSON && xhr
                                .responseJSON.message ||
                                'Nenhum operacional disponível no momento.', 'warning');
                        } else {
                            const msg = xhr.responseJSON && xhr.responseJSON.message ||
                                'Erro ao criar conversa';
                            Swal.fire('Erro', msg, 'error');
                        }
                    }
                });
            });

            function debounce(func, wait) {
                let timeout;
                return function(...args) {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => func.apply(this, args), wait);
                };
            }
        });
    </script>
    <script>
        Object.assign(window.AppRoutes, {
            chatIndex: "{{ route('chat.index') }}",
            show: "{{ route('chat.show', ':id') }}",
            buscarUsuarios: "{{ route('chat.buscar_usuarios') }}",
            buscarPis: "{{ route('chat.buscar_pis') }}",
            resolverConversa: "{{ route('chat.resolver_conversa', ':id') }}",
            reabrirConversa: "{{ route('chat.reabrir_conversa', ':id') }}",
            excluirConversa: "{{ route('chat.excluir_conversa', ':id') }}",
            conversasNaoLidas: "{{ route('chat.conversas_nao_lidas') }}",
            conversasComNaoLidas: "{{ route('chat.conversas_com_nao_lidas') }}",
            limparMsgOrfas: "{{ route('chat.limpar_mensagens_orfas') }}",
            criarConversa: "{{ route('chat.criar_conversa') }}",
            userRole: "{{ $userRole }}"
        });
    </script>
@endpush

@push('styles')
    <link href="{{ asset('css/pages/chat.css') }}" rel="stylesheet">
    <style>
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes shimmer {
            0% {
                background-position: -1000px 0;
            }

            100% {
                background-position: 1000px 0;
            }
        }

        @keyframes dropdownFadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes swalFadeIn {
            from {
                opacity: 0;
                transform: scale(0.95) translateY(20px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .page-header {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 18px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
            animation: fadeInDown 0.5s ease;
        }

        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-color);
            margin: 0 0 0.5rem 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .page-title i {
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 1.85rem;
        }

        [data-theme="dark"] .page-title i {
            background: none !important;
            -webkit-background-clip: unset !important;
            background-clip: unset !important;
            -webkit-text-fill-color: white !important;
            color: white !important;
        }

        .page-subtitle {
            color: var(--text-muted);
            font-size: 0.95rem;
            margin: 0;
        }

        .btn-create {
            background: var(--primary-gradient);
            color: white;
            border: none;
            padding: 0.875rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 14px rgba(0, 94, 130, 0.3);
            position: relative;
            overflow: hidden;
        }

        .btn-create::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn-create:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 94, 130, 0.4);
        }

        .btn-create:hover::before {
            left: 100%;
        }

        .btn-create:active {
            transform: translateY(0);
        }

        .m-0-auto {
            margin: 0 auto;
        }

        .stats-filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.25rem;
            margin-bottom: 2rem;
        }

        .filter-stat-card {
            background: var(--bg-card);
            border: 2px solid var(--border-color);
            border-radius: 16px;
            padding: 1.75rem 1.5rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: var(--shadow-sm);
            cursor: pointer;
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.6s ease backwards;
            user-select: none;
        }

        .filter-stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .filter-stat-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-color);
        }

        .filter-stat-card:hover::before {
            transform: scaleX(1);
        }

        .filter-stat-card.active {
            background: linear-gradient(135deg, rgba(0, 94, 130, 0.08) 0%, rgba(0, 94, 130, 0.04) 100%);
            border-color: var(--primary-color);
            box-shadow: 0 4px 20px rgba(0, 94, 130, 0.25);
            transform: translateY(-6px);
        }

        .filter-stat-card.active::before {
            transform: scaleX(1);
        }

        .filter-stat-card:nth-child(1) {
            animation-delay: 0.1s;
        }

        .filter-stat-card:nth-child(2) {
            animation-delay: 0.15s;
        }

        .filter-stat-card:nth-child(3) {
            animation-delay: 0.2s;
        }

        .filter-stat-card:nth-child(4) {
            animation-delay: 0.25s;
        }

        .filter-stat-card:nth-child(5) {
            animation-delay: 0.3s;
        }

        .filter-stat-content {
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        .filter-stat-icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            flex-shrink: 0;
            transition: all 0.3s ease;
        }

        .filter-stat-card:hover .filter-stat-icon {
            transform: scale(1.1) rotate(5deg);
        }

        .filter-stat-card.active .filter-stat-icon {
            transform: scale(1.15);
        }

        .filter-stat-icon.primary {
            background: linear-gradient(135deg, rgba(0, 94, 130, 0.15) 0%, rgba(0, 94, 130, 0.08) 100%);
            color: var(--primary-color);
        }

        .filter-stat-icon.danger {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.15) 0%, rgba(239, 68, 68, 0.08) 100%);
            color: #ef4444;
        }

        .filter-stat-icon.success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(16, 185, 129, 0.08) 100%);
            color: var(--success-color);
        }

        .filter-stat-icon.warning {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.15) 0%, rgba(245, 158, 11, 0.08) 100%);
            color: var(--warning-color);
        }

        .filter-stat-icon.info {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.15) 0%, rgba(59, 130, 246, 0.08) 100%);
            color: #3b82f6;
        }

        .filter-stat-info {
            flex: 1;
            min-width: 0;
        }

        .filter-stat-label {
            font-size: 0.875rem;
            color: var(--text-muted);
            font-weight: 600;
            letter-spacing: 0.02em;
            margin-bottom: 0.5rem;
        }

        .filter-stat-value {
            font-size: 2.25rem;
            font-weight: 800;
            color: var(--text-color);
            line-height: 1;
            font-feature-settings: 'tnum';
        }

        .search-section {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-sm);
        }

        .search-box {
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 1rem 1rem 1rem 3.25rem;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: var(--bg-body);
            color: var(--text-color);
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(0, 94, 130, 0.1);
            background: var(--bg-card);
        }

        .search-box i {
            position: absolute;
            left: 1.125rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 1.15rem;
        }

        .conversas-container {
            display: grid;
            gap: 1.25rem;
        }

        .conversa-card {
            background: var(--bg-card);
            border: 2px solid var(--border-color);
            border-radius: 18px;
            padding: 0;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.5s ease backwards;
            cursor: pointer;
        }

        .conversa-card::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 18px;
            padding: 2px;
            background: var(--primary-gradient);
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .conversa-card:hover {
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
            transform: translateY(-4px);
            border-color: transparent;
        }

        .conversa-card:hover::after {
            opacity: 1;
        }

        .conversa-card.nao-lida {
            background: linear-gradient(135deg, rgba(0, 94, 130, 0.04) 0%, rgba(0, 94, 130, 0.01) 100%);
            border-color: rgba(0, 94, 130, 0.3);
        }

        .conversa-card.nao-lida::after {
            opacity: 0.5;
        }

        .conversa-card.nao-lida:hover::after {
            opacity: 1;
        }

        .conversa-card.resolvida {
            opacity: 0.7;
            background: linear-gradient(135deg, rgba(107, 114, 128, 0.04) 0%, rgba(107, 114, 128, 0.01) 100%);
        }

        .conversa-main {
            padding: 1.75rem;
            cursor: pointer;
            position: relative;
        }

        .conversa-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1.25rem;
            margin-bottom: 1.25rem;
            padding-top: 2.5rem;
        }

        .conversa-header-info {
            flex: 1;
            min-width: 0;
        }

        .conversa-titulo {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-color);
            margin: 0 0 0.75rem 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            line-height: 1.3;
        }

        .conversa-titulo i {
            color: var(--primary-color);
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .conversa-meta-info {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            flex-wrap: wrap;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .meta-item i {
            font-size: 1rem;
            color: var(--primary-color);
        }

        .conversa-status-group {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.75rem;
        }

        .conversa-actions {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            display: flex;
            gap: 0.375rem;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 10;
            background: rgba(255, 255, 255, 0.95);
            padding: 0.375rem;
            border-radius: 10px;
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
        }

        [data-theme="dark"] .conversa-actions {
            background: rgba(29, 33, 40, 0.95);
        }

        .conversa-card:hover .conversa-actions {
            opacity: 1;
        }

        .btn-action {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            width: 28px;
            height: 28px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        .btn-action:hover {
            background: var(--bg-body);
            border-color: var(--primary-color);
            color: var(--primary-color);
            transform: scale(1.1);
        }

        .btn-action.btn-resolver:hover {
            border-color: var(--success-color);
            color: var(--success-color);
        }

        .btn-action.danger:hover {
            border-color: #ef4444;
            color: #ef4444;
        }

        .badge-group {
            display: flex;
            gap: 0.625rem;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .badge-pill {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            display: inline-flex;
            align-items: center;
            white-space: nowrap;
        }

        .badge-status {
            font-weight: 700;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .badge-status.aberta {
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
            border: 1.5px solid rgba(16, 185, 129, 0.2);
        }

        .badge-status.resolvida {
            background: rgba(107, 114, 128, 0.1);
            color: #4b5563;
            border: 1.5px solid rgba(107, 114, 128, 0.2);
        }

        .badge-status.escalada {
            background: rgba(245, 158, 11, 0.1);
            color: #d97706;
            border: 1.5px solid rgba(245, 158, 11, 0.2);
        }

        .badge-status i {
            font-size: 0.65rem;
        }

        .badge-criador {
            background: rgba(139, 92, 246, 0.1);
            color: #7c3aed;
            border: 1.5px solid rgba(139, 92, 246, 0.2);
            padding: 0.375rem 0.75rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
        }

        .conversa-mensagem {
            color: var(--text-muted);
            font-size: 0.95rem;
            line-height: 1.6;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            margin: 0 0 1.25rem 0;
            padding-left: 0.25rem;
        }

        .conversa-mensagem strong {
            color: var(--text-color);
            font-weight: 600;
        }

        .conversa-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.25rem 1.75rem;
            background: linear-gradient(180deg, transparent 0%, rgba(0, 94, 130, 0.02) 100%);
            border-top: 1px solid var(--border-color);
        }

        .participantes-section {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .participantes-avatars {
            display: flex;
            align-items: center;
        }

        .avatar-item {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: var(--primary-gradient);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 700;
            border: 2px solid var(--bg-card);
            margin-left: -12px;
            transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            position: relative;
        }

        .avatar-item:first-child {
            margin-left: 0;
        }

        .avatar-item:hover {
            transform: scale(1.25) translateY(-4px);
            z-index: 10;
            box-shadow: 0 6px 16px rgba(0, 94, 130, 0.4);
        }

        .participantes-count {
            font-size: 0.875rem;
            color: var(--text-muted);
            font-weight: 600;
        }

        .conversa-info-section {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .tempo-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: var(--text-muted);
            font-weight: 600;
            padding: 0.375rem 0.875rem;
            background: rgba(0, 94, 130, 0.05);
            border-radius: 8px;
        }

        .tempo-info i {
            font-size: 0.9rem;
        }

        .badge-nao-lidas-destaque {
            background: rgba(239, 68, 68, 0.12);
            color: var(--danger-color);
            border: 1.5px solid rgba(239, 68, 68, 0.25);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 800;
            min-width: 32px;
            text-align: center;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .badge-nao-lidas-destaque i {
            font-size: 0.7rem;
        }

        .empty-state {
            text-align: center;
            padding: 5rem 2rem;
            background: var(--bg-card);
            border-radius: 20px;
            border: 2px dashed var(--border-color);
        }

        .empty-state i.big-icon {
            font-size: 5rem;
            margin-bottom: 1rem;
        }

        .empty-state i {
            font-size: 1rem;
            color: var(--text-muted);
            opacity: 0.3;
        }

        .empty-state h3 {
            font-size: 1.375rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 0.75rem;
        }

        .empty-state p {
            color: var(--text-muted);
            margin-bottom: 2rem;
            font-size: 1rem;
        }

        .modal-backdrop.show {
            backdrop-filter: blur(8px);
            background-color: rgb(0, 0, 0);
            opacity: 0.7;
        }

        .modal.fade .modal-dialog {
            transform: scale(0.8) translateY(-100px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .modal.show .modal-dialog {
            transform: scale(1) translateY(0);
        }

        .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .modal-header {
            background: var(--primary-gradient);
            color: white;
            padding: 2rem;
            border: none;
            position: relative;
            overflow: hidden;
        }

        .modal-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
        }

        .modal-header .btn-close {
            filter: brightness(0) invert(1);
            opacity: 0.9;
            transition: all 0.3s ease;
            width: 32px;
            height: 32px;
            background-size: 14px;
        }

        .modal-header .btn-close:hover {
            opacity: 1;
            transform: rotate(90deg);
        }

        .modal-title {
            font-weight: 700;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            position: relative;
            z-index: 1;
            color: white;
        }

        .modal-title i {
            font-size: 1.75rem;
            color: white;
        }

        .modal-body {
            padding: 2.5rem;
            background: var(--bg-body);
        }

        [data-theme="dark"] .modal-body {
            background: #1d2128;
        }

        .form-label {
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 0.75rem;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-label i {
            color: var(--primary-color);
            font-size: 1.1rem;
        }

        .form-control,
        .form-select {
            border: 2px solid var(--border-color);
            border-radius: 14px;
            padding: 1rem 1.25rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: white;
            font-weight: 500;
            color: var(--text-color);
        }

        [data-theme="dark"] .form-control,
        [data-theme="dark"] .form-select {
            background: var(--bg-card);
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(0, 94, 130, 0.1);
            background: white;
        }

        [data-theme="dark"] .form-control:focus,
        [data-theme="dark"] .form-select:focus {
            background: var(--bg-card);
        }

        .form-control.is-invalid,
        .form-select.is-invalid {
            border-color: #ef4444;
        }

        .form-control.is-invalid:focus,
        .form-select.is-invalid:focus {
            border-color: #ef4444;
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1);
        }

        textarea.form-control {
            min-height: 140px;
            resize: vertical;
            line-height: 1.6;
        }

        .form-text {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-top: 0.5rem;
            font-weight: 500;
        }

        .char-counter {
            font-size: 0.75rem;
            color: var(--text-muted);
            text-align: right;
            margin-top: 0.375rem;
            font-weight: 600;
        }

        .destinatario-selector,
        .pi-selector {
            position: relative;
            margin-bottom: 0.75rem;
        }

        .search-input-destinatario {
            width: 100%;
            padding: 1rem 3.5rem 1rem 1.25rem;
            border: 2px solid var(--border-color);
            border-radius: 14px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: white;
            font-weight: 500;
            color: var(--text-color);
        }

        [data-theme="dark"] .search-input-destinatario {
            background: var(--bg-card);
        }

        .search-input-destinatario:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(0, 94, 130, 0.1);
        }

        .search-spinner {
            position: absolute;
            right: 1.25rem;
            top: 50%;
            transform: translateY(-50%);
            width: 22px;
            height: 22px;
            display: none;
            color: var(--primary-color);
        }

        .search-spinner.show {
            display: block;
        }

        .dropdown-usuarios,
        .dropdown-pis {
            position: absolute;
            top: calc(100% + 0.75rem);
            left: 0;
            right: 0;
            background: white;
            border: 2px solid var(--border-color);
            border-radius: 14px;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
            max-height: 360px;
            overflow-y: auto;
            z-index: 1060;
            display: none;
        }

        [data-theme="dark"] .dropdown-usuarios,
        [data-theme="dark"] .dropdown-pis {
            background: var(--bg-card);
        }

        .dropdown-usuarios.show,
        .dropdown-pis.show {
            display: block;
            animation: dropdownFadeIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .dropdown-usuarios::-webkit-scrollbar,
        .dropdown-pis::-webkit-scrollbar {
            width: 8px;
        }

        .dropdown-usuarios::-webkit-scrollbar-track,
        .dropdown-pis::-webkit-scrollbar-track {
            background: #f8fafc;
            border-radius: 10px;
        }

        [data-theme="dark"] .dropdown-usuarios::-webkit-scrollbar-track,
        [data-theme="dark"] .dropdown-pis::-webkit-scrollbar-track {
            background: var(--bg-body);
        }

        .dropdown-usuarios::-webkit-scrollbar-thumb,
        .dropdown-pis::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 10px;
        }

        .dropdown-item-usuario,
        .dropdown-item-pi {
            padding: 1rem 1.25rem;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .dropdown-item-usuario:last-child,
        .dropdown-item-pi:last-child {
            border-bottom: none;
        }

        .dropdown-item-usuario:hover,
        .dropdown-item-pi:hover {
            background: linear-gradient(135deg, rgba(0, 94, 130, 0.06) 0%, rgba(0, 94, 130, 0.03) 100%);
            padding-left: 1.5rem;
        }

        .dropdown-usuario-avatar {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: var(--primary-gradient);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            font-weight: 700;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(0, 94, 130, 0.3);
        }

        .dropdown-pi-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.15) 0%, rgba(59, 130, 246, 0.08) 100%);
            color: #3b82f6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .dropdown-usuario-info,
        .dropdown-pi-info {
            flex: 1;
            min-width: 0;
        }

        .dropdown-usuario-name,
        .dropdown-pi-numero {
            font-weight: 700;
            font-size: 0.95rem;
            color: var(--text-color);
            margin-bottom: 0.375rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .dropdown-usuario-details,
        .dropdown-pi-details {
            font-size: 0.85rem;
            color: var(--text-muted);
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .dropdown-empty {
            text-align: center;
            padding: 3rem 1.5rem;
            color: var(--text-muted);
        }

        .dropdown-empty i {
            font-size: 3rem;
            opacity: 0.25;
            margin-bottom: 1rem;
            display: block;
        }

        .dropdown-empty p {
            margin: 0;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .selected-destinatario-preview {
            margin-top: 1rem;
            padding: 1.25rem;
            background: var(--primary-gradient);
            border-radius: 14px;
            color: white;
            display: none;
            align-items: center;
            gap: 1.25rem;
            animation: slideIn 0.3s ease;
            box-shadow: 0 4px 14px rgba(0, 94, 130, 0.3);
        }

        .selected-destinatario-preview.show {
            display: flex;
        }

        .selected-destinatario-avatar {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            font-weight: 700;
            flex-shrink: 0;
            backdrop-filter: blur(10px);
        }

        .selected-destinatario-info {
            flex: 1;
            min-width: 0;
        }

        .selected-destinatario-name {
            font-weight: 700;
            font-size: 1.125rem;
            margin-bottom: 0.375rem;
        }

        .selected-destinatario-role {
            opacity: 0.9;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .btn-remove-destinatario {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            flex-shrink: 0;
            backdrop-filter: blur(10px);
        }

        .btn-remove-destinatario:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1) rotate(90deg);
        }

        .modal-footer {
            padding: 1.5rem 2.5rem;
            border: none;
            background: white;
            gap: 1rem;
            justify-content: center;
        }

        [data-theme="dark"] .modal-footer {
            background: #1d2128;
        }

        .modal-footer .btn {
            padding: 0.875rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 0.625rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
        }

        .modal-footer .btn-secondary {
            background: var(--bg-body);
            color: var(--text-color);
            border: 2px solid var(--border-color);
        }

        .modal-footer .btn-secondary:hover {
            background: var(--bg-card);
            border-color: var(--text-muted);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .modal-footer .btn-primary {
            background: var(--primary-gradient);
            color: white;
            box-shadow: 0 4px 14px rgba(0, 94, 130, 0.3);
        }

        .modal-footer .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 94, 130, 0.4);
        }

        .modal-footer .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .alert-duplicate {
            background: rgba(245, 158, 11, 0.1);
            border: 1.5px solid rgba(245, 158, 11, 0.25);
            color: #d97706;
            border-radius: 12px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.25rem;
            display: none;
            align-items: center;
            gap: 0.75rem;
            animation: slideIn 0.3s ease;
        }

        .alert-duplicate.show {
            display: flex;
        }

        .alert-duplicate i {
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .alert-duplicate a {
            color: #d97706;
            font-weight: 700;
            text-decoration: underline;
        }

        .swal2-popup {
            font-family: inherit !important;
            border-radius: 16px !important;
            padding: 2rem !important;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15) !important;
        }

        .swal2-title {
            font-size: 1.75rem !important;
            font-weight: 700 !important;
            color: #1f2937 !important;
            margin-bottom: 1.5rem !important;
            padding: 0 1rem !important;
        }

        .swal2-html-container {
            margin: 0 !important;
            padding: 0 !important;
            font-size: 1rem !important;
            line-height: 1.6 !important;
            color: #4b5563 !important;
        }

        .swal2-icon {
            margin: 1.5rem auto !important;
            border-width: 3px !important;
        }

        .swal2-icon.swal2-warning {
            border-color: #fbbf24 !important;
            color: #f59e0b !important;
        }

        .swal2-icon.swal2-error {
            border-color: #f87171 !important;
            color: #ef4444 !important;
        }

        .swal2-icon.swal2-question {
            border-color: #60a5fa !important;
            color: #3b82f6 !important;
        }

        .swal2-icon.swal2-success {
            border-color: #34d399 !important;
            color: #10b981 !important;
        }

        .swal2-icon.swal2-info {
            border-color: #60a5fa !important;
            color: #3b82f6 !important;
        }

        .swal2-actions {
            margin-top: 2rem !important;
            gap: 1rem !important;
            padding: 0 1rem !important;
        }

        .swal2-styled:focus {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3) !important;
        }

        .swal2-container.swal2-backdrop-show {
            background: rgba(0, 0, 0, 0.5) !important;
            backdrop-filter: blur(4px);
        }

        .swal2-show {
            animation: swalFadeIn 0.3s ease !important;
        }

        .swal-modern {
            border-radius: 20px;
            padding: 2rem;
        }

        .swal-content {
            text-align: left;
        }

        .swal-text {
            font-size: 1.05rem;
            color: var(--text-color);
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .swal-info-box {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            padding: 1.25rem;
            border-radius: 10px;
            border-left: 4px solid #10b981;
        }

        .swal-info-box.swal-warning {
            border-left-color: var(--warning-color);
        }

        .swal-info-box i {
            color: var(--success-color);
            font-size: 1.125rem;
            margin-right: 0.625rem;
        }

        .swal-info-box span {
            color: var(--text-color);
            font-size: 0.9375rem;
            line-height: 1.6;
        }

        .swal-info-item {
            margin: 0.5rem 0;
            color: var(--text-color);
            font-size: 0.9375rem;
        }

        .swal-btn {
            margin: 0 !important;
            padding: 1rem 2rem !important;
            border-radius: 10px !important;
            font-weight: 600 !important;
            font-size: 1rem !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 0.625rem !important;
            transition: all 0.3s ease !important;
            border: none !important;
            min-width: 140px !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1) !important;
        }

        .swal-btn i {
            font-size: 1.125rem !important;
        }

        .swal-btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
            color: white !important;
        }

        .swal-btn-success:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 20px rgba(5, 150, 105, 0.4) !important;
        }

        .swal-btn-success:active {
            transform: translateY(0) !important;
        }

        .swal-btn-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
            color: white !important;
        }

        .swal-btn-danger:hover {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 20px rgba(220, 38, 38, 0.35) !important;
        }

        .swal-btn-danger:active {
            transform: translateY(0) !important;
        }

        .swal-btn-restore {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
            color: white !important;
        }

        .swal-btn-restore:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 20px rgba(5, 150, 105, 0.4) !important;
        }

        .swal-btn-restore:active {
            transform: translateY(0) !important;
        }

        .swal-btn-secondary {
            background: linear-gradient(135deg, #9ca3af 0%, #6b7280 100%) !important;
            color: white !important;
        }

        .swal-btn-secondary:hover {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 20px rgba(107, 114, 128, 0.3) !important;
        }

        .swal-btn-secondary:active {
            transform: translateY(0) !important;
        }

        [data-theme="dark"] .swal2-popup {
            background: #1f2937 !important;
        }

        [data-theme="dark"] .swal2-title {
            color: #f9fafb !important;
        }

        [data-theme="dark"] .swal2-html-container {
            color: #d1d5db !important;
        }

        [data-theme="dark"] .swal-text {
            color: #e5e7eb;
        }

        [data-theme="dark"] .swal-info-box {
            background: rgba(16, 185, 129, 0.1) !important;
            border-left-color: #10b981 !important;
        }

        [data-theme="dark"] .swal-info-box.swal-warning {
            background: rgba(245, 158, 11, 0.1) !important;
            border-left-color: #fbbf24 !important;
        }

        [data-theme="dark"] .swal-info-box span {
            color: #d1d5db;
        }

        [data-theme="dark"] .swal-info-item {
            color: #d1d5db;
        }

        @media (max-width: 992px) {
            .modal-dialog {
                margin: 0.5rem;
            }

            .conversa-actions {
                position: static !important;
                opacity: 1 !important;
                margin-bottom: 1rem;
                background: transparent !important;
                padding: 0 !important;
                box-shadow: none !important;
            }

            .conversa-top {
                padding-top: 0;
            }

            .btn-action {
                width: 36px;
                height: 36px;
                font-size: 1rem;
            }
        }

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 1.25rem;
                padding: 1.5rem;
            }

            .btn-create {
                width: 100%;
                justify-content: center;
            }

            .stats-filters {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }

            .filter-stat-card {
                padding: 1.25rem;
            }

            .filter-stat-icon {
                width: 48px;
                height: 48px;
                font-size: 1.5rem;
            }

            .filter-stat-value {
                font-size: 1.75rem;
            }

            .conversa-top {
                flex-direction: column;
                gap: 1rem;
            }

            .conversa-status-group {
                width: 100%;
                align-items: flex-start;
            }

            .badge-group {
                justify-content: flex-start;
            }

            .conversa-info-section {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.75rem;
            }

            .modal-dialog {
                max-width: 100%;
                margin: 0;
                height: 100%;
            }

            .modal-content {
                border-radius: 0;
                height: 100%;
                display: flex;
                flex-direction: column;
            }

            .modal-body {
                flex: 1;
                overflow-y: auto;
                padding: 2rem 1.5rem;
            }

            .modal-header,
            .modal-footer {
                padding: 1.5rem;
            }

            .form-control,
            .form-select {
                font-size: 16px;
            }

            .swal2-popup {
                padding: 1.5rem !important;
                width: calc(100% - 2rem) !important;
                max-width: 500px !important;
            }

            .swal2-title {
                font-size: 1.5rem !important;
                margin-bottom: 1.25rem !important;
            }

            .swal2-html-container {
                font-size: 0.9375rem !important;
            }

            .swal2-actions {
                flex-direction: column !important;
                width: 100% !important;
                gap: 0.75rem !important;
                margin-top: 1.5rem !important;
            }

            .swal-btn {
                width: 100% !important;
                min-width: 100% !important;
                padding: 0.875rem 1.5rem !important;
            }

            .swal-text {
                font-size: 1rem;
            }

            .swal-info-box {
                padding: 1rem;
            }

            .swal-info-box span {
                font-size: 0.875rem;
            }

            .swal-info-item {
                font-size: 0.875rem;
            }
        }

        @media (max-width: 576px) {
            .stats-filters {
                grid-template-columns: 1fr;
            }

            .page-title {
                font-size: 1.5rem;
            }

            .conversa-main {
                padding: 1.5rem;
            }

            .conversa-footer {
                padding: 1rem 1.5rem;
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }

            .conversa-info-section {
                width: 100%;
            }

            .modal-footer {
                flex-direction: column;
            }

            .modal-footer .btn {
                width: 100%;
                justify-content: center;
            }

            .swal2-popup {
                padding: 1.25rem !important;
            }

            .swal2-title {
                font-size: 1.375rem !important;
                margin-bottom: 1rem !important;
            }

            .swal-btn {
                padding: 0.75rem 1.25rem !important;
                font-size: 0.9375rem !important;
            }
        }
    </style>
@endpush