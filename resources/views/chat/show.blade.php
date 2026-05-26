@extends('layouts.app')

@section('title', $conversa->assunto)

@section('content')
    <link href="{{ asset('css/pages/chat-show.css') }}" rel="stylesheet">
    <div class="container-fluid">
        <div class="chat-page-breadcrumb">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="{{ route('chat.index') }}">
                            <i class="bi bi-chat-dots-fill me-1"></i>
                            Mensagens
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        {{ Str::limit($conversa->assunto, 50) }}
                    </li>
                </ol>
            </nav>
        </div>

        <div class="row">
            <div class="col-lg-8 col-xl-9">
                <div class="chat-page-container">
                    <div class="chat-page-header">
                        <div class="d-flex justify-content-between align-items-start chat-page-header-flex">
                            <div class="flex-grow-1">
                                <h1 class="chat-page-title">{{ $conversa->assunto }}</h1>
                                <div class="chat-page-badges">
                                    <span class="chat-page-badge chat-status-{{ $conversa->status_conversa }}">
                                        @if ($conversa->status_conversa === 'aberta')
                                            <i class="bi bi-circle-fill"></i>
                                        @elseif($conversa->status_conversa === 'resolvida')
                                            <i class="bi bi-check-circle-fill"></i>
                                        @else
                                            <i class="bi bi-arrow-up-circle-fill"></i>
                                        @endif
                                        {{ ucfirst($conversa->status_conversa) }}
                                    </span>
                                    @if ($conversa->pi_id && $conversa->pi)
                                        <span class="chat-page-badge chat-pi-badge">
                                            <i class="bi bi-link-45deg"></i>
                                            PI #{{ $conversa->pi->numero_pi }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="chat-page-actions">
                                @php
                                    $role = auth()->user()->roles->first();
                                    if (!$role) {
                                        abort(403, 'Usuário sem permissões definidas.');
                                    }
                                    $userRole = $role->name;
                                    $isCriador = $conversa->criador_id === auth()->id();
                                    $isAdmin = $userRole === 'admin';
                                    $podeGerenciar = in_array($userRole, ['operacional', 'gerencial', 'admin']);

                                    $ehParticipante = $participantes->contains(function ($p) {
                                        return $p->participante_type === \App\Models\User::class
                                            && $p->participante_id === auth()->id();
                                    });

                                    $podeAssumir = !$ehParticipante
                                        && in_array($userRole, ['gerencial', 'admin'])
                                        && $conversa->status_conversa !== 'resolvida';
                                @endphp

                                @if ($podeAssumir)
                                    <button type="button" class="chat-page-btn warning" id="btnAssumir">
                                        <i class="bi bi-hand-index-thumb-fill"></i>
                                        Assumir Conversa
                                    </button>
                                @endif

                                @if ($ehParticipante)
                                    @if ($conversa->status_conversa === 'aberta' || $conversa->status_conversa === 'escalada')
                                        @if ($podeGerenciar)
                                            <button type="button" class="chat-page-btn success" id="btnResolver">
                                                <i class="bi bi-check-circle-fill"></i>
                                                Resolver
                                            </button>
                                        @endif
                                    @elseif($conversa->status_conversa === 'resolvida')
                                        @if ($podeGerenciar)
                                            <button type="button" class="chat-page-btn" id="btnReabrir">
                                                <i class="bi bi-arrow-clockwise"></i>
                                                Reabrir
                                            </button>
                                        @endif
                                    @endif

                                    @if (in_array($userRole, ['operacional', 'gerencial']) &&
                                            $conversa->nivel_atual !== 'admin' &&
                                            $conversa->status_conversa !== 'resolvida')
                                        <button type="button" class="chat-page-btn warning" id="btnEscalar">
                                            <i class="bi bi-arrow-up-circle-fill"></i>
                                            Escalar
                                        </button>
                                    @endif

                                    @if ($userRole === 'operacional' && $conversa->status_conversa !== 'resolvida')
                                        <button type="button" class="chat-page-btn" id="btnTransferir">
                                            <i class="bi bi-arrow-left-right"></i>
                                            Transferir
                                        </button>
                                    @elseif(!in_array($userRole, ['operacional']) && $conversa->status_conversa !== 'resolvida')
                                        <button type="button" class="chat-page-btn danger" id="btnSairConversa">
                                            <i class="bi bi-box-arrow-right"></i>
                                            Sair
                                        </button>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="chat-page-messages" id="chatMensagens">
                        @foreach ($mensagens as $mensagem)
                            @php
                                $isMinha = $mensagem->remetente_id === auth()->id();
                                $nomeRemetente = $mensagem->remetente?->name ?? 'Usuário Removido';
                                $iniciaisRemetente = $mensagem->remetente
                                    ? mb_strtoupper(mb_substr($mensagem->remetente->name, 0, 2, 'UTF-8'))
                                    : '??';
                            @endphp

                            <div class="chat-page-message {{ $isMinha ? 'chat-message-mine' : 'chat-message-other' }}"
                                data-mensagem-id="{{ $mensagem->id }}">
                                @if (!$isMinha)
                                    <div class="chat-page-avatar">
                                        {{ $iniciaisRemetente }}
                                    </div>
                                @endif

                                <div class="chat-page-message-content">
                                    @if (!$isMinha)
                                        <div class="chat-page-message-sender">
                                            <strong>{{ $nomeRemetente }}</strong>
                                        </div>
                                    @endif

                                    @if (!empty($mensagem->mensagem))
                                        <div class="chat-page-message-text">{{ $mensagem->mensagem }}</div>
                                    @endif

                                    @if ($mensagem->anexos->count() > 0)
                                        @foreach ($mensagem->anexos as $anexo)
                                            <div class="chat-page-message-attachment">
                                                <i
                                                    class="bi bi-{{ $anexo->tipo_arquivo === 'imagem' ? 'image' : ($anexo->tipo_arquivo === 'pdf' ? 'file-pdf' : 'file-word') }}-fill"></i>
                                                <a href="{{ route('chat.preview_anexo', $anexo->id) }}"
                                                    target="_blank">{{ $anexo->nome_original }}</a>
                                                <small>({{ number_format($anexo->tamanho / 1024, 2) }} KB)</small>
                                                <div class="chat-page-attachment-actions">
                                                    <a href="{{ route('chat.download_anexo', $anexo->id) }}"
                                                        class="chat-page-attachment-btn" title="Download">
                                                        <i class="bi bi-download"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        @endforeach
                                    @endif

                                    <div class="chat-page-message-info">
                                        <small>{{ $mensagem->created_at->format('d/m/Y H:i') }}</small>
                                        @if ($isMinha && $mensagem->lida)
                                            <i class="bi bi-check-all"
                                                title="Lida em {{ $mensagem->data_leitura->format('d/m/Y H:i') }}"></i>
                                        @endif
                                    </div>
                                </div>

                                @if ($isMinha)
                                    <div class="chat-page-avatar">
                                        {{ $iniciaisRemetente }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <div class="chat-page-footer {{ $conversa->status_conversa === 'resolvida' || !$ehParticipante ? 'blocked' : '' }}">
                        @if ($conversa->status_conversa === 'resolvida')
                            <div class="chat-page-blocked-message">
                                <i class="bi bi-lock-fill"></i>
                                <span>Esta conversa está resolvida. Reabra para enviar mensagens.</span>
                            </div>
                        @elseif (!$ehParticipante)
                            <div class="chat-page-blocked-message">
                                <i class="bi bi-eye-fill"></i>
                                <span>Modo somente leitura. Assuma a conversa para enviar mensagens.</span>
                            </div>
                        @else
                            <form id="formEnviarMensagem" enctype="multipart/form-data">
                                @csrf
                                <div class="chat-page-input-group">
                                    <button type="button" class="chat-page-btn-attach" id="btnAnexar"
                                        title="Anexar arquivo">
                                        <i class="bi bi-paperclip"></i>
                                    </button>
                                    <input type="file" id="inputAnexo" name="anexo" class="d-none"
                                        accept=".jpg,.jpeg,.png,.gif,.bmp,.webp,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.mp4,.avi,.mov,.wmv,.flv,.mkv,.webm,.mpeg">

                                    <div class="chat-page-input-wrapper">
                                        <textarea class="chat-page-input" id="inputMensagem" name="mensagem" rows="1" placeholder="Digite sua mensagem..."
                                            maxlength="5000"></textarea>

                                        <div id="anexoPreview" class="chat-page-attachment-preview d-none">
                                            <div class="chat-page-attachment-info">
                                                <i class="bi bi-paperclip"></i>
                                                <span id="anexoNome"></span>
                                            </div>
                                            <button type="button" class="chat-page-btn-remove" id="btnRemoverAnexo">
                                                <i class="bi bi-x-circle-fill"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <button type="submit" class="chat-page-btn-send" id="btnEnviar">
                                        <i class="bi bi-send-fill"></i>
                                    </button>
                                </div>

                                <div class="chat-page-info-text">
                                    Máximo 45MB • Enter envia, Shift+Enter quebra linha
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-xl-3">
                <div class="chat-page-sidebar-card">
                    <div class="chat-page-sidebar-header">
                        <h2 class="chat-page-sidebar-title">
                            <i class="bi bi-people-fill"></i>
                            Participantes
                        </h2>
                    </div>
                    <div class="chat-page-sidebar-body">
                        @foreach ($participantes as $participante)
                            @php
                                $nomeParticipante = $participante->participante?->name ?? 'Usuário Removido';
                                $iniciais = $participante->participante
                                    ? mb_strtoupper(mb_substr($participante->participante->name, 0, 2, 'UTF-8'))
                                    : '??';
                            @endphp
                            <div class="chat-page-participant">
                                <div class="chat-page-participant-avatar">
                                    {{ $iniciais }}
                                </div>
                                <div class="chat-page-participant-info">
                                    <div class="chat-page-participant-name">{{ $nomeParticipante }}</div>
                                </div>
                            </div>
                        @endforeach
                        @if (in_array($userRole, ['gerencial', 'admin']) && $conversa->status_conversa !== 'resolvida')
                            <button type="button" class="chat-page-btn-add-participant" id="btnAdicionarParticipante">
                                <i class="bi bi-person-plus-fill"></i>
                                Adicionar Participante
                            </button>
                        @endif
                    </div>
                </div>

                @if ($conversa->pi_id && $conversa->pi)
                    <div class="chat-page-sidebar-card mt-3">
                        <div class="chat-page-sidebar-header">
                            <h2 class="chat-page-sidebar-title">
                                <i class="bi bi-file-earmark-text-fill"></i>
                                PI Relacionado
                            </h2>
                        </div>
                        <div class="chat-page-sidebar-body">
                            <div class="chat-page-pi-info">
                                <div class="chat-page-pi-label">Número</div>
                                <div class="chat-page-pi-value">#{{ $conversa->pi->numero_pi }}</div>
                            </div>
                            @if ($conversa->pi->cliente)
                                <div class="chat-page-pi-info">
                                    <div class="chat-page-pi-label">Cliente</div>
                                    <div class="chat-page-pi-value">{{ $conversa->pi->cliente->razao_social }}</div>
                                </div>
                            @endif
                            @if ($conversa->pi->status)
                                <div class="chat-page-pi-info">
                                    <div class="chat-page-pi-label">Status</div>
                                    <span class="chat-page-pi-status">{{ $conversa->pi->status->nome }}</span>
                                </div>
                            @endif
                            <button type="button" class="chat-page-btn-view-pi btn-view-pi-chat"
                                data-pi-id="{{ $conversa->pi->id }}" title="Ver Detalhes do PI">
                                <i class="bi bi-eye-fill"></i>
                                Ver Detalhes
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEscalar" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-arrow-up-circle-fill"></i>
                        Escalar Conversa
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formEscalar">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-info d-flex align-items-center gap-3" style="border-radius: 12px;">
                            <i class="bi bi-info-circle-fill" style="font-size: 1.5rem;"></i>
                            <div>
                                <strong>Escalonamento</strong><br>
                                Esta conversa será direcionada para um nível superior de atendimento.
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">
                                <i class="bi bi-person-fill"></i>
                                Selecionar Usuário (Opcional)
                            </label>

                            <div class="usuario-selector">
                                <input type="text" class="search-input-usuario" id="searchEscalar"
                                    placeholder="Digite pelo menos 2 caracteres para buscar..." autocomplete="off">
                                <div class="spinner-border spinner-border-sm search-spinner-modal" id="loadingEscalar">
                                </div>

                                <div class="dropdown-usuarios-modal" id="dropdownEscalar">
                                    <div class="dropdown-empty-modal">
                                        <i class="bi bi-search"></i>
                                        <p>Digite pelo menos 2 caracteres para buscar</p>
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" name="usuario_id" id="usuario_escalar_id">

                            <div class="selected-usuario-preview" id="previewEscalar">
                                <div class="selected-usuario-avatar" id="previewEscalarAvatar">JD</div>
                                <div class="selected-usuario-info">
                                    <div class="selected-usuario-name" id="previewEscalarNome">João da Silva</div>
                                    <div class="selected-usuario-details" id="previewEscalarDetalhes">joao@empresa.com
                                    </div>
                                </div>
                                <button type="button" class="btn-remove-usuario" id="btnRemoveEscalar">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>

                            <small class="form-text">Deixe em branco para escalar sem selecionar usuário específico</small>
                        </div>

                        <div class="mb-3">
                            <label for="motivo_escalar" class="form-label">
                                <i class="bi bi-chat-left-text-fill"></i>
                                Motivo (Opcional)
                            </label>
                            <textarea class="form-control" id="motivo_escalar" name="motivo" rows="3"
                                placeholder="Descreva o motivo do escalonamento..." maxlength="500"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle"></i>
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-warning" id="btnSubmitEscalar">
                            <i class="bi bi-arrow-up-circle-fill"></i>
                            Escalar Conversa
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalTransferir" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-arrow-left-right"></i>
                        Transferir Conversa
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formTransferir">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-warning d-flex align-items-center gap-3" style="border-radius: 12px;">
                            <i class="bi bi-exclamation-triangle-fill" style="font-size: 1.5rem;"></i>
                            <div>
                                <strong>Transferência de Responsabilidade</strong><br>
                                Ao transferir, você deixará de participar desta conversa.
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">
                                <i class="bi bi-person-fill"></i>
                                Transferir para
                                <span class="text-danger">*</span>
                            </label>

                            <div class="usuario-selector">
                                <input type="text" class="search-input-usuario" id="searchTransferir"
                                    placeholder="Digite pelo menos 2 caracteres para buscar..." autocomplete="off">
                                <div class="spinner-border spinner-border-sm search-spinner-modal" id="loadingTransferir">
                                </div>

                                <div class="dropdown-usuarios-modal" id="dropdownTransferir">
                                    <div class="dropdown-empty-modal">
                                        <i class="bi bi-search"></i>
                                        <p>Digite pelo menos 2 caracteres para buscar</p>
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" name="para_usuario_id" id="usuario_transferir_id">

                            <div class="selected-usuario-preview" id="previewTransferir">
                                <div class="selected-usuario-avatar" id="previewTransferirAvatar">JD</div>
                                <div class="selected-usuario-info">
                                    <div class="selected-usuario-name" id="previewTransferirNome">João da Silva</div>
                                    <div class="selected-usuario-details" id="previewTransferirDetalhes">joao@empresa.com
                                    </div>
                                </div>
                                <button type="button" class="btn-remove-usuario" id="btnRemoveTransferir">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="motivo_transferir" class="form-label">
                                <i class="bi bi-chat-left-text-fill"></i>
                                Motivo (Opcional)
                            </label>
                            <textarea class="form-control" id="motivo_transferir" name="motivo" rows="3"
                                placeholder="Descreva o motivo da transferência..." maxlength="500"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle"></i>
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary" id="btnSubmitTransferir">
                            <i class="bi bi-arrow-left-right"></i>
                            Confirmar Transferência
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalAdicionarParticipante" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-person-plus-fill"></i>
                        Adicionar Participante
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formAdicionarParticipante">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-4">
                            <label class="form-label">
                                <i class="bi bi-person-fill"></i>
                                Selecione o Usuário
                                <span class="text-danger">*</span>
                            </label>

                            <div class="usuario-selector">
                                <input type="text" class="search-input-usuario" id="searchAdicionar"
                                    placeholder="Digite pelo menos 2 caracteres para buscar..." autocomplete="off">
                                <div class="spinner-border spinner-border-sm search-spinner-modal" id="loadingAdicionar">
                                </div>

                                <div class="dropdown-usuarios-modal" id="dropdownAdicionar">
                                    <div class="dropdown-empty-modal">
                                        <i class="bi bi-search"></i>
                                        <p>Digite pelo menos 2 caracteres para buscar</p>
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" name="usuario_id" id="usuario_adicionar_id">

                            <div class="selected-usuario-preview" id="previewAdicionar">
                                <div class="selected-usuario-avatar" id="previewAdicionarAvatar">JD</div>
                                <div class="selected-usuario-info">
                                    <div class="selected-usuario-name" id="previewAdicionarNome">João da Silva</div>
                                    <div class="selected-usuario-details" id="previewAdicionarDetalhes">joao@empresa.com
                                    </div>
                                </div>
                                <button type="button" class="btn-remove-usuario" id="btnRemoveAdicionar">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle"></i>
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary" id="btnSubmitAdicionar">
                            <i class="bi bi-person-plus-fill"></i>
                            Adicionar Participante
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="piModalChat" tabindex="-1" aria-labelledby="piModalChatLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="modal-header-content">
                        <div class="modal-icon">
                            <i class="bi bi-file-earmark-text-fill"></i>
                        </div>
                        <div>
                            <h5 class="modal-title">Detalhes do PI</h5>
                            <p class="modal-subtitle">Visualização completa das informações</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close-modal" data-bs-dismiss="modal" aria-label="Close">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="modal-body" id="piModalChatBody">
                    <div class="loading-container">
                        <div class="spinner-border"></div>
                        <p class="loading-text">Carregando dados do PI...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/pages/chat-show.js') }}"></script>
    <script>
        const isFornecedor = {{ auth()->user()->hasRole('fornecedor') ? 'true' : 'false' }};

        $(document).ready(function() {
            const chatConfig = {
                conversaId: {{ $conversa->id }},
                ultimaMensagemId: {{ $mensagens->last()->id ?? 0 }},
                conversaStatus: '{{ $conversa->status_conversa }}',

                excludeIds: {{ json_encode($participantes->pluck('participante_id')->toArray()) }},

                chatUrls: {
                    marcarComoLida: '{{ route('chat.marcar_como_lida', $conversa->id) }}',
                    buscarMensagens: '{{ route('chat.buscar_mensagens_novas', $conversa->id) }}',
                    enviarMensagem: '{{ route('chat.enviar_mensagem', $conversa->id) }}',
                    resolverConversa: '{{ route('chat.resolver_conversa', $conversa->id) }}',
                    reabrirConversa: '{{ route('chat.reabrir_conversa', $conversa->id) }}',
                    sairConversa: '{{ route('chat.sair_conversa', $conversa->id) }}',
                    assumirConversa: '{{ route('chat.assumir_conversa', $conversa->id) }}'
                },

                modalUrls: {
                    escalarConversa: '{{ route('chat.escalar_conversa', $conversa->id) }}',
                    transferirConversa: '{{ route('chat.transferir_conversa', $conversa->id) }}',
                    adicionarParticipante: '{{ route('chat.adicionar_participante', $conversa->id) }}'
                },

                urlBuscarUsuarios: '{{ route('chat.buscar_usuarios') }}'
            };

            window.chatPageInstance = initChatPage(chatConfig);

            $('.btn-view-pi-chat').on('click', function() {
                const piId = $(this).data('pi-id');
                const modal = new bootstrap.Modal(document.getElementById('piModalChat'));

                modal.show();

                $.ajax({
                    url: '{{ route('pis.detalhes', ':id') }}'.replace(':id', piId),
                    method: 'GET',
                    success: function(response) {
                        if (response.success) {
                            renderPiDetailsInChat(response.pi);
                        }
                    },
                    error: function() {
                        $('#piModalChatBody').html(`
                            <div class="empty-message">
                                <i class="bi bi-exclamation-triangle"></i>
                                <p>Erro ao carregar os dados do PI.</p>
                            </div>
                        `);
                        Swal.fire('Erro', 'Erro ao carregar dados do PI', 'error');
                    }
                });
            });

            $(document).on('click', '.btn-close-modal', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const modalElement = document.getElementById('piModalChat');
                const modal = bootstrap.Modal.getInstance(modalElement);
                if (modal) {
                    modal.hide();
                }
            });

            $('#piModalChat').on('click', function(e) {
                if ($(e.target).hasClass('modal')) {
                    const modalElement = document.getElementById('piModalChat');
                    const modal = bootstrap.Modal.getInstance(modalElement);
                    if (modal) {
                        modal.hide();
                    }
                }
            });

            $(document).on('click', '.documento-download-clickable', function(e) {
                e.preventDefault();
                const downloadUrl = $(this).data('download-url');
                window.location.href = downloadUrl;
            });

            function renderPiDetailsInChat(pi) {
                let html = '';

                html += `
                    <div class="pi-details-section">
                        <h3 class="section-title">
                            <i class="bi bi-info-circle-fill"></i>
                            Informações Básicas
                        </h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <div class="detail-label"><i class="bi bi-hash"></i> Número PI</div>
                                <div class="detail-value">${pi.numero_pi}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="bi bi-calendar3"></i> Período</div>
                                <div class="detail-value ${!pi.periodo ? 'empty' : ''}">${pi.periodo || 'Não informado'}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="bi bi-calendar-check"></i> Data Cadastro</div>
                                <div class="detail-value">${formatarDataHora(pi.data_cadastro)}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="bi bi-calendar-event"></i> Data Conclusão</div>
                                <div class="detail-value ${!pi.data_conclusao ? 'empty' : ''}">${pi.data_conclusao ? formatarDataHora(pi.data_conclusao) : 'Não definida'}</div>
                            </div>
                            ${!isFornecedor ? `
                                    <div class="detail-item">
                                        <div class="detail-label"><i class="bi bi-file-text"></i> PIT Nº</div>
                                        <div class="detail-value ${!pi.PIT_n ? 'empty' : ''}">${pi.PIT_n || 'Não informado'}</div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="detail-label"><i class="bi bi-clipboard-data"></i> Plano Nº</div>
                                        <div class="detail-value ${!pi.plano_n ? 'empty' : ''}">${pi.plano_n || 'Não informado'}</div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="detail-label"><i class="bi bi-table"></i> Planilha Nº</div>
                                        <div class="detail-value ${!pi.planilha_n ? 'empty' : ''}">${pi.planilha_n || 'Não informado'}</div>
                                    </div>
                                ` : ''}
                            <div class="detail-item">
                                <div class="detail-label"><i class="bi bi-check-circle"></i> Status</div>
                                <div class="detail-value">
                                    <span style="background: ${pi.status.cor}15; color: ${pi.status.cor}; border: 1px solid ${pi.status.cor}30; padding: 0.25rem 0.75rem; border-radius: 0.5rem; font-size: 0.85rem; font-weight: 600;">
                                        ${pi.status.nome}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                if (pi.cliente) {
                    html += `
                        <div class="pi-details-section">
                            <h3 class="section-title">
                                <i class="bi bi-building-fill"></i>
                                Cliente
                            </h3>
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <div class="detail-label"><i class="bi bi-building"></i> Razão Social</div>
                                    <div class="detail-value">${pi.cliente.razao_social}</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label"><i class="bi bi-shop"></i> Nome Fantasia</div>
                                    <div class="detail-value ${!pi.cliente.nome_fantasia ? 'empty' : ''}">${pi.cliente.nome_fantasia || 'Não informado'}</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label"><i class="bi bi-card-text"></i> CNPJ</div>
                                    <div class="detail-value">${formatarCNPJ(pi.cliente.cnpj)}</div>
                                </div>
                                ${!isFornecedor && pi.cliente.telefone ? `
                                        <div class="detail-item">
                                            <div class="detail-label"><i class="bi bi-telephone"></i> Telefone</div>
                                            <div class="detail-value">${pi.cliente.telefone}</div>
                                        </div>
                                    ` : ''}
                            </div>
                        </div>
                    `;
                }

                if (pi.fornecedor) {
                    html += `
                        <div class="pi-details-section">
                            <h3 class="section-title">
                                <i class="bi bi-collection-play"></i>
                                Fornecedor
                            </h3>
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <div class="detail-label"><i class="bi bi-building"></i> Razão Social</div>
                                    <div class="detail-value">${pi.fornecedor.razao_social}</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label"><i class="bi bi-shop"></i> Nome Fantasia</div>
                                    <div class="detail-value ${!pi.fornecedor.nome_fantasia ? 'empty' : ''}">${pi.fornecedor.nome_fantasia || 'Não informado'}</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label"><i class="bi bi-card-text"></i> CNPJ</div>
                                    <div class="detail-value">${formatarCNPJ(pi.fornecedor.cnpj)}</div>
                                </div>
                            </div>
                        </div>
                    `;
                }

                if (pi.status && !isFornecedor) {
                    html += `
                        <div class="pi-details-section">
                            <h3 class="section-title">
                                <i class="bi bi-activity"></i>
                                Status e Responsáveis
                            </h3>
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <div class="detail-label"><i class="bi bi-tag"></i> Status Atual</div>
                                    <div class="detail-value">
                                        <span class="status-badge" style="background: ${pi.status.cor}15; color: ${pi.status.cor}; border: 1px solid ${pi.status.cor}30;">
                                            ${pi.status.nome}
                                        </span>
                                    </div>
                                </div>
                    `;

                    if (pi.gerencial) {
                        html += `
                            <div class="detail-item">
                                <div class="detail-label"><i class="bi bi-person-badge"></i> Gerencial</div>
                                <div class="detail-value">${pi.gerencial.nome_completo}</div>
                            </div>
                        `;
                    }

                    if (pi.atribuicoes_operacionais && pi.atribuicoes_operacionais.length > 0) {
                        const operacionalPrincipal = pi.atribuicoes_operacionais.find(a => a.principal && a
                            .operacional);
                        if (operacionalPrincipal) {
                            html += `
                                <div class="detail-item">
                                    <div class="detail-label"><i class="bi bi-person-check"></i> Operacional Principal</div>
                                    <div class="detail-value">${operacionalPrincipal.operacional.nome_completo}</div>
                                </div>
                            `;
                        }
                    }

                    html += `
                            </div>
                        </div>
                    `;
                } else if (pi.status && isFornecedor) {
                    html += `
                        <div class="pi-details-section">
                            <h3 class="section-title">
                                <i class="bi bi-activity"></i>
                                Status
                            </h3>
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <div class="detail-label"><i class="bi bi-tag"></i> Status Atual</div>
                                    <div class="detail-value">
                                        <span class="status-badge" style="background: ${pi.status.cor}15; color: ${pi.status.cor}; border: 1px solid ${pi.status.cor}30;">
                                            ${pi.status.nome}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                }

                if (!isFornecedor && pi.atribuicoes_operacionais && pi.atribuicoes_operacionais.length > 0) {
                    html += `
                        <div class="pi-details-section">
                            <h3 class="section-title">
                                <i class="bi bi-people-fill"></i>
                                Operacionais Atribuídos (${pi.atribuicoes_operacionais.length})
                            </h3>
                    `;

                    pi.atribuicoes_operacionais.forEach(atrib => {
                        if (atrib.operacional) {
                            const iniciais = atrib.operacional.nome_completo.split(' ').map(n => n[0])
                                .slice(0, 2).join('').toUpperCase();
                            const isPrincipal = atrib.principal;

                            html += `
                                <div class="operacional-card ${isPrincipal ? 'principal' : ''}">
                                    <div class="operacional-info">
                                        <div class="operacional-avatar">${iniciais}</div>
                                        <div class="operacional-details">
                                            <div class="operacional-nome">${atrib.operacional.nome_completo}</div>
                                            <div class="operacional-email">
                                                <i class="bi bi-envelope"></i>
                                                ${atrib.operacional.user ? atrib.operacional.user.email : 'Email não disponível'}
                                            </div>
                                        </div>
                                    </div>
                                    ${isPrincipal ? '<div class="badge-principal"><i class="bi bi-star-fill"></i> Principal</div>' : ''}
                                </div>
                            `;
                        }
                    });

                    html += `</div>`;
                }

                if (pi.pi_documentos && pi.pi_documentos.length > 0) {
                    const docsPendentes = pi.pi_documentos.filter(d => !d.documento_enviado).length;
                    const docsAtendidos = pi.pi_documentos.filter(d => d.documento_enviado).length;
                    const docsReprovados = pi.pi_documentos.filter(d => d.documento_enviado && ['Reprovado',
                        'Aguardando Reenvio'
                    ].includes(d.documento_enviado.status.nome)).length;

                    html += `
                        <div class="pi-details-section">
                            <h3 class="section-title">
                                <i class="bi bi-file-earmark-text-fill"></i>
                                Documentos do PI (${pi.pi_documentos.length})
                            </h3>
                            <div class="docs-summary-cards">
                                ${docsReprovados > 0 ? `
                                        <div class="docs-summary-card docs-reprovados-card">
                                            <div class="docs-summary-icon">
                                                <i class="bi bi-exclamation-triangle-fill"></i>
                                            </div>
                                            <div class="docs-summary-info">
                                                <div class="docs-summary-value">${docsReprovados}</div>
                                                <div class="docs-summary-label">Reprovados</div>
                                            </div>
                                        </div>
                                    ` : ''}
                                <div class="docs-summary-card docs-pending-card">
                                    <div class="docs-summary-icon">
                                        <i class="bi bi-hourglass-split"></i>
                                    </div>
                                    <div class="docs-summary-info">
                                        <div class="docs-summary-value">${docsPendentes}</div>
                                        <div class="docs-summary-label">Pendentes</div>
                                    </div>
                                </div>
                                <div class="docs-summary-card docs-attended-card">
                                    <div class="docs-summary-icon">
                                        <i class="bi bi-check-circle"></i>
                                    </div>
                                    <div class="docs-summary-info">
                                        <div class="docs-summary-value">${docsAtendidos}</div>
                                        <div class="docs-summary-label">Atendidos</div>
                                    </div>
                                </div>
                            </div>
                    `;

                    if (isFornecedor && docsReprovados > 0) {
                        html += `
                            <div class="modal-alert-box">
                                <div class="modal-alert-icon">
                                    <i class="bi bi-exclamation-triangle-fill"></i>
                                </div>
                                <div class="modal-alert-content">
                                    <strong>Atenção!</strong> Você possui ${docsReprovados} ${docsReprovados === 1 ? 'documento reprovado' : 'documentos reprovados'} neste PI que precisa${docsReprovados === 1 ? '' : 'm'} ser reenviado${docsReprovados === 1 ? '' : 's'}.
                                </div>
                            </div>
                        `;
                    }

                    pi.pi_documentos.forEach(piDoc => {
                        const tipoDoc = piDoc.tipo_documento;
                        const docEnviado = piDoc.documento_enviado;
                        const statusDoc = piDoc.status;

                        html += `
                            <div class="documento-card-pi ${docEnviado && ['Reprovado', 'Aguardando Reenvio'].includes(docEnviado.status.nome) ? 'documento-reprovado' : ''}">
                                <div class="documento-main-content">
                                    <div class="documento-icon-wrapper ${docEnviado ? 'attended' : 'pending'}">
                                        <i class="bi ${docEnviado ? 'bi-file-earmark-check-fill' : 'bi-file-earmark-text-fill'}"></i>
                                    </div>
                                    <div class="documento-text-content">
                                        <div class="documento-nome-pi">${tipoDoc.nome}</div>
                                        ${tipoDoc.descricao ? `<div class="documento-descricao">${tipoDoc.descricao}</div>` : ''}
                                        <div class="documento-meta-info">
                                            ${statusDoc ? `
                                                    <div class="meta-item">
                                                        <span class="status-badge" style="background: ${statusDoc.cor}15; color: ${statusDoc.cor}; border: 1px solid ${statusDoc.cor}30; font-size: 0.75rem; padding: 0.25rem 0.625rem;">
                                                            ${statusDoc.nome}
                                                        </span>
                                                    </div>
                                                ` : ''}
                                            ${docEnviado && docEnviado.data_validade_documento ? `
                                                    <div class="meta-item">
                                                        <i class="bi bi-calendar-check" style="color: var(--success-color);"></i>
                                                        <span>Validade: ${new Date(docEnviado.data_validade_documento).toLocaleDateString('pt-BR')}</span>
                                                    </div>
                                                ` : ''}
                                        </div>
                                        ${docEnviado && docEnviado.validacoes && docEnviado.validacoes.length > 0 && docEnviado.validacoes[0].observacoes && ['Reprovado', 'Aguardando Reenvio'].includes(docEnviado.status.nome) ? `
                                                <div class="documento-reprovacao-motivo">
                                                    <strong><i class="bi bi-exclamation-circle-fill"></i> Motivo da Reprovação:</strong>
                                                    <p>${escapeHtml(docEnviado.validacoes[0].observacoes)}</p>
                                                </div>
                                            ` : ''}
                                    </div>
                                </div>
                            </div>
                        `;
                    });

                    html += `</div>`;
                }

                if (pi.conversas && pi.conversas.length > 0) {
                    html += `
                        <div class="pi-details-section">
                            <h3 class="section-title">
                                <i class="bi bi-chat-dots-fill"></i>
                                Conversas (${pi.conversas.length})
                            </h3>
                    `;

                    pi.conversas.forEach(conversa => {
                        const statusClass = conversa.status_conversa === 'aberta' ? 'conversa-aberta' :
                            conversa.status_conversa === 'resolvida' ? 'conversa-resolvida' :
                            'conversa-escalada';
                        const statusText = conversa.status_conversa === 'aberta' ? 'Aberta' :
                            conversa.status_conversa === 'resolvida' ? 'Resolvida' :
                            'Escalada';
                        const statusIcon = conversa.status_conversa === 'aberta' ? 'bi-chat-dots' :
                            conversa.status_conversa === 'resolvida' ? 'bi-check-circle' :
                            'bi-arrow-up-circle';

                        html += `
                            <div class="conversa-card ${statusClass}">
                                <div class="conversa-header">
                                    <div class="conversa-title">
                                        <i class="bi ${statusIcon}"></i>
                                        ${escapeHtml(conversa.assunto)}
                                    </div>
                                    <span class="conversa-status-badge">${statusText}</span>
                                </div>
                                <div class="conversa-body">
                                    <div class="conversa-info-item">
                                        <i class="bi bi-calendar3"></i>
                                        <span>Criada em: ${new Date(conversa.created_at).toLocaleDateString('pt-BR')} às ${new Date(conversa.created_at).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })}</span>
                                    </div>
                                    <div class="conversa-info-item">
                                        <i class="bi bi-chat-square-text"></i>
                                        <span>${conversa.mensagens_count || 0} mensagens</span>
                                    </div>
                                    ${conversa.ultima_mensagem ? `
                                            <div class="conversa-ultima-mensagem">
                                                <strong>Última mensagem:</strong>
                                                <p>${escapeHtml(conversa.ultima_mensagem.mensagem.substring(0, 100))}${conversa.ultima_mensagem.mensagem.length > 100 ? '...' : ''}</p>
                                            </div>
                                        ` : ''}
                                </div>
                            </div>
                        `;
                    });

                    html += `</div>`;
                }

                if (!isFornecedor && pi.valores) {
                    html += `
                        <div class="pi-details-section">
                            <h3 class="section-title">
                                <i class="bi bi-currency-dollar"></i>
                                Valores e Condições Comerciais
                            </h3>
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <div class="detail-label"><i class="bi bi-cash-stack"></i> Total Bruto</div>
                                    <div class="detail-value">${pi.valores.total_bruto ? formatarDinheiro(pi.valores.total_bruto) : '<span class="empty">Não informado</span>'}</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label"><i class="bi bi-percent"></i> Comissão</div>
                                    <div class="detail-value">${pi.valores.comissao ? formatarDinheiro(pi.valores.comissao) : '<span class="empty">Não informado</span>'}</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label"><i class="bi bi-calculator"></i> Líquido</div>
                                    <div class="detail-value">${pi.valores.liquido ? formatarDinheiro(pi.valores.liquido) : '<span class="empty">Não informado</span>'}</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label"><i class="bi bi-tag"></i> Custo Unitário</div>
                                    <div class="detail-value">${pi.valores.custo_unitario ? formatarDinheiro(pi.valores.custo_unitario) : '<span class="empty">Não informado</span>'}</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label"><i class="bi bi-arrow-down-circle"></i> Desconto</div>
                                    <div class="detail-value">${pi.valores.desconto ? pi.valores.desconto + '%' : '<span class="empty">Não informado</span>'}</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label"><i class="bi bi-cash"></i> Custo Total</div>
                                    <div class="detail-value">${pi.valores.custo_total ? formatarDinheiro(pi.valores.custo_total) : '<span class="empty">Não informado</span>'}</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label"><i class="bi bi-geo-alt"></i> Local Cobrança</div>
                                    <div class="detail-value ${!pi.valores.local_cobranca ? 'empty' : ''}">${pi.valores.local_cobranca || 'Não informado'}</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label"><i class="bi bi-clock-history"></i> Prazo Pagamento</div>
                                    <div class="detail-value ${!pi.valores.prazo_pagamento ? 'empty' : ''}">${pi.valores.prazo_pagamento || 'Não informado'}</div>
                                </div>
                            </div>
                        </div>
                    `;
                }

                if (pi.observacoes) {
                    html += `
                        <div class="pi-details-section">
                            <h3 class="section-title">
                                <i class="bi bi-sticky-fill"></i>
                                Observações
                            </h3>
                            <div class="observacoes-box">${escapeHtml(pi.observacoes)}</div>
                        </div>
                    `;
                }

                if (!isFornecedor && pi.file_path) {
                    const downloadUrl = '{{ route('pis.download_documento', ':id') }}'.replace(':id', pi.id);

                    html += `
                        <div class="pi-details-section">
                            <h3 class="section-title">
                                <i class="bi bi-file-pdf-fill"></i>
                                Documento PI
                            </h3>
                            <div class="documento-download-box documento-download-clickable" data-download-url="${downloadUrl}">
                                <div class="documento-download-info">
                                    <i class="bi bi-file-pdf" style="font-size: 2.5rem; color: #ef4444;"></i>
                                    <div>
                                        <div style="font-weight: 600; color: var(--text-color); margin-bottom: 0.25rem;">Documento PI Anexado</div>
                                        <div style="font-size: 0.875rem; color: var(--text-muted);">Clique para baixar o documento</div>
                                    </div>
                                </div>
                                <div class="documento-download-icon">
                                    <i class="bi bi-download"></i>
                                </div>
                            </div>
                        </div>
                    `;
                }

                $('#piModalChatBody').html(html);
            }

            function formatarCNPJ(cnpj) {
                if (!cnpj) return 'Não informado';
                return cnpj.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
            }

            function formatarDataHora(dataHora) {
                if (!dataHora) return 'Não informado';
                const data = new Date(dataHora);
                return data.toLocaleDateString('pt-BR') + ' ' + data.toLocaleTimeString('pt-BR', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }

            function formatarDinheiro(valor) {
                if (!valor) return 'R$ 0,00';
                return 'R$ ' + parseFloat(valor).toLocaleString('pt-BR', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }
        });
    </script>
@endpush

@push('styles')
    <style>
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

        .pi-details-section {
            margin-bottom: 3rem;
            animation: fadeInUp 0.4s ease;
        }

        .pi-details-section:last-child {
            margin-bottom: 0;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text-color);
            margin-bottom: 1.75rem;
            padding-bottom: 1rem;
            border-bottom: 3px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 0.875rem;
        }

        .section-title i {
            color: var(--primary-color);
            font-size: 1.75rem;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .detail-item {
            background: var(--bg-body);
            padding: 1.5rem;
            border-radius: 14px;
            border: 2px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .detail-item:hover {
            border-color: var(--primary-color);
            box-shadow: 0 6px 16px rgba(0, 94, 130, 0.12);
            transform: translateY(-2px);
        }

        .detail-label {
            font-size: 0.875rem;
            font-weight: 700;
            color: var(--text-muted);
            margin-bottom: 0.625rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .detail-value {
            font-size: 1.0625rem;
            color: var(--text-color);
            font-weight: 600;
            word-break: break-word;
        }

        .detail-value.empty {
            color: var(--text-muted);
            font-style: italic;
            font-weight: 400;
        }

        .status-concluido {
            color: var(--success-color);
            font-weight: 700;
        }

        .status-andamento {
            color: var(--warning-color);
            font-weight: 700;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.375rem 0.75rem;
            border-radius: 8px;
            font-size: 0.8125rem;
            font-weight: 600;
        }

        .operacional-card {
            background: var(--bg-body);
            padding: 1.5rem 2rem;
            border-radius: 14px;
            border: 2px solid var(--border-color);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.3s ease;
        }

        .operacional-card:last-child {
            margin-bottom: 0;
        }

        .operacional-card:hover {
            border-color: var(--primary-color);
            box-shadow: 0 6px 16px rgba(0, 94, 130, 0.12);
            transform: translateX(4px);
        }

        .operacional-card.principal {
            border-color: var(--success-color);
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.06) 0%, rgba(16, 185, 129, 0.03) 100%);
        }

        .operacional-info {
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        .operacional-avatar {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 800;
            font-size: 1.25rem;
            box-shadow: 0 6px 16px rgba(0, 94, 130, 0.25);
        }

        .operacional-card.principal .operacional-avatar {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.35);
        }

        .operacional-details {
            display: flex;
            flex-direction: column;
            gap: 0.375rem;
        }

        .operacional-nome {
            font-weight: 700;
            color: var(--text-color);
            font-size: 1.0625rem;
        }

        .operacional-email {
            font-size: 0.9375rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .operacional-email i {
            font-size: 0.875rem;
        }

        .badge-principal {
            padding: 0.5rem 1.25rem;
            border-radius: 24px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            font-size: 0.875rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.35);
        }

        .badge-principal i {
            font-size: 1rem;
        }

        .docs-summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.25rem;
            margin-bottom: 2rem;
        }

        .docs-summary-card {
            background: var(--bg-body);
            border: 2px solid var(--border-color);
            border-radius: 14px;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1.25rem;
            transition: all 0.3s ease;
        }

        .docs-summary-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }

        .docs-reprovados-card {
            border-color: rgba(239, 68, 68, 0.3);
        }

        .docs-reprovados-card .docs-summary-icon {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }

        .docs-pending-card {
            border-color: rgba(245, 158, 11, 0.3);
        }

        .docs-pending-card .docs-summary-icon {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }

        .docs-attended-card {
            border-color: rgba(16, 185, 129, 0.3);
        }

        .docs-attended-card .docs-summary-icon {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .docs-summary-icon {
            width: 64px;
            height: 64px;
            min-width: 64px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
        }

        .docs-summary-info {
            display: flex;
            flex-direction: column;
            gap: 0.375rem;
        }

        .docs-summary-value {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--text-color);
            line-height: 1;
        }

        .docs-summary-label {
            font-size: 0.9375rem;
            color: var(--text-muted);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .modal-alert-box {
            background: linear-gradient(135deg, #fee2e2 0%, #fff 100%);
            border-left: 4px solid #ef4444;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            gap: 1.25rem;
            align-items: flex-start;
        }

        .modal-alert-icon {
            width: 48px;
            height: 48px;
            background: #ef4444;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .modal-alert-content {
            color: var(--danger-color);
            line-height: 1.6;
            font-size: 1rem;
        }

        .documento-card-pi {
            background: var(--bg-body);
            border: 2px solid var(--border-color);
            border-radius: 14px;
            padding: 1.5rem;
            margin-bottom: 1.25rem;
            transition: all 0.3s ease;
        }

        .documento-card-pi:last-child {
            margin-bottom: 0;
        }

        .documento-card-pi:hover {
            border-color: var(--primary-color);
            box-shadow: 0 6px 16px rgba(0, 94, 130, 0.12);
            transform: translateY(-2px);
        }

        .documento-card-pi.documento-reprovado {
            border-left: 4px solid #ef4444;
            background: rgba(254, 242, 242, 0.5);
        }

        .documento-main-content {
            display: flex;
            gap: 1.5rem;
        }

        .documento-icon-wrapper {
            width: 64px;
            height: 64px;
            min-width: 64px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.75rem;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
        }

        .documento-icon-wrapper.pending {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }

        .documento-icon-wrapper.attended {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .documento-text-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 0.625rem;
        }

        .documento-nome-pi {
            font-weight: 700;
            font-size: 1.125rem;
            color: var(--text-color);
            line-height: 1.3;
        }

        .documento-descricao {
            font-size: 0.9375rem;
            color: var(--text-muted);
            line-height: 1.5;
        }

        .documento-meta-info {
            display: flex;
            flex-wrap: wrap;
            gap: 1.25rem;
            margin-top: 0.375rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9375rem;
            color: var(--text-color);
            font-weight: 600;
        }

        .meta-item i {
            font-size: 1.125rem;
        }

        .documento-reprovacao-motivo {
            margin-top: 1rem;
            padding: 1rem;
            background: rgba(239, 68, 68, 0.08);
            border-left: 4px solid #ef4444;
            border-radius: 8px;
        }

        .documento-reprovacao-motivo strong {
            display: block;
            color: var(--danger-color);
            margin-bottom: 0.625rem;
            font-size: 0.9375rem;
        }

        .documento-reprovacao-motivo p {
            margin: 0;
            color: var(--text-color);
            font-size: 0.9375rem;
            line-height: 1.6;
        }

        .conversa-card {
            background: var(--bg-body);
            border: 2px solid var(--border-color);
            border-radius: 14px;
            padding: 0;
            margin-bottom: 1.25rem;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .conversa-card:last-child {
            margin-bottom: 0;
        }

        .conversa-card:hover {
            border-color: var(--primary-color);
            box-shadow: 0 6px 16px rgba(0, 94, 130, 0.12);
            transform: translateY(-2px);
        }

        .conversa-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.5rem 2rem;
            background: linear-gradient(135deg, rgba(0, 94, 130, 0.06) 0%, rgba(0, 94, 130, 0.03) 100%);
            border-bottom: 2px solid var(--border-color);
        }

        .conversa-title {
            font-weight: 700;
            font-size: 1.125rem;
            color: var(--text-color);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .conversa-title i {
            font-size: 1.375rem;
            color: var(--primary-color);
        }

        .conversa-status-badge {
            padding: 0.5rem 1.25rem;
            border-radius: 24px;
            font-size: 0.875rem;
            font-weight: 700;
        }

        .conversa-aberta .conversa-status-badge {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .conversa-resolvida .conversa-status-badge {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
            color: white;
        }

        .conversa-escalada .conversa-status-badge {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }

        .conversa-body {
            padding: 1.5rem 2rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .conversa-info-item {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            font-size: 1rem;
            color: var(--text-color);
        }

        .conversa-info-item i {
            color: var(--primary-color);
            font-size: 1.125rem;
        }

        .conversa-ultima-mensagem {
            margin-top: 0.75rem;
            padding: 1.25rem;
            background: rgba(0, 94, 130, 0.06);
            border-radius: 12px;
            border-left: 4px solid var(--primary-color);
        }

        .conversa-ultima-mensagem strong {
            display: block;
            margin-bottom: 0.625rem;
            color: var(--text-color);
            font-size: 0.9375rem;
        }

        .conversa-ultima-mensagem p {
            margin: 0;
            color: var(--text-muted);
            font-size: 0.9375rem;
            line-height: 1.6;
        }

        .observacoes-box {
            background: var(--bg-body);
            border: 2px solid var(--border-color);
            border-radius: 14px;
            padding: 2rem;
            color: var(--text-color);
            line-height: 1.7;
            white-space: pre-wrap;
        }

        .documento-download-box {
            background: var(--bg-body);
            border: 2px solid var(--border-color);
            border-radius: 14px;
            padding: 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.3s ease;
        }

        .documento-download-box:hover {
            border-color: var(--primary-color);
            box-shadow: 0 6px 16px rgba(0, 94, 130, 0.12);
        }

        .documento-download-info {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .documento-download-clickable {
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .documento-download-clickable::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(0, 94, 130, 0.1), transparent);
            transition: left 0.5s ease;
        }

        .documento-download-clickable:hover::before {
            left: 100%;
        }

        .documento-download-clickable:hover {
            border-color: var(--primary-color);
            box-shadow: 0 8px 24px rgba(0, 94, 130, 0.2);
            transform: translateY(-4px);
        }

        .documento-download-clickable:active {
            transform: translateY(-2px);
        }

        .documento-download-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 94, 130, 0.25);
            transition: all 0.3s ease;
        }

        .documento-download-clickable:hover .documento-download-icon {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(0, 94, 130, 0.35);
        }

        .documento-download-clickable:active .documento-download-icon {
            transform: scale(0.95);
        }

        .loading-container {
            text-align: center;
            padding: 5rem 2rem;
        }

        .loading-container .spinner-border {
            width: 4rem;
            height: 4rem;
            border-width: 0.4rem;
            color: var(--primary-color);
        }

        .loading-text {
            margin-top: 2rem;
            color: var(--text-muted);
            font-size: 1.125rem;
            font-weight: 600;
        }

        .empty-message {
            text-align: center;
            padding: 4rem 2rem;
        }

        .empty-message i {
            font-size: 4rem;
            color: #ef4444;
            margin-bottom: 1rem;
        }

        .empty-message p {
            font-size: 1.125rem;
            color: var(--text-color);
            font-weight: 600;
        }

        .chat-page-blocked-message {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            padding: 1.5rem;
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(239, 68, 68, 0.05) 100%);
            border: 2px solid rgba(239, 68, 68, 0.3);
            border-radius: 12px;
        }

        .chat-page-blocked-message i {
            font-size: 1.5rem;
            color: #ef4444;
        }

        .chat-page-blocked-message span {
            color: var(--text-color);
            font-weight: 600;
        }

        #piModalChat .modal-dialog {
            max-width: 1200px;
        }

        #piModalChat .modal-content {
            border-radius: 16px;
            border: 1px solid var(--border-color);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            background: var(--bg-card);
        }

        #piModalChat .modal-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            color: white;
            border-radius: 16px 16px 0 0;
            padding: 1.75rem 2rem;
            border-bottom: none;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-header-content {
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        .modal-icon {
            width: 56px;
            height: 56px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
        }

        #piModalChat .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
            color: white;
        }

        .modal-subtitle {
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.9);
            margin: 0.25rem 0 0 0;
        }

        .btn-close-modal {
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            padding: 0;
            position: relative;
            z-index: 1060;
        }

        .btn-close-modal:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.4);
            transform: scale(1.05);
        }

        .btn-close-modal:active {
            transform: scale(0.95);
        }

        .btn-close-modal i {
            font-size: 1.25rem;
            pointer-events: none;
        }

        #piModalChat .modal-body {
            padding: 2.5rem;
            max-height: calc(100vh - 350px);
            overflow-y: auto;
            background: var(--bg-card);
        }

        [data-theme="dark"] #piModalChat .modal-content {
            background: #1f2937;
            border-color: rgba(255, 255, 255, 0.1);
        }

        [data-theme="dark"] #piModalChat .modal-body {
            background: #1f2937;
        }

        [data-theme="dark"] .detail-item {
            background: #111827;
            border-color: rgba(255, 255, 255, 0.1);
        }

        [data-theme="dark"] .modal-alert-box {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.2) 0%, rgba(239, 68, 68, 0.1) 100%);
        }

        @media (max-width: 768px) {
            #piModalChat .modal-dialog {
                max-width: calc(100% - 2rem);
                margin: 1rem;
            }

            #piModalChat .modal-header {
                padding: 1.5rem;
            }

            #piModalChat .modal-body {
                padding: 1.5rem;
            }

            .detail-grid {
                grid-template-columns: 1fr;
            }

            .section-title {
                font-size: 1.25rem;
            }

            .docs-summary-cards {
                grid-template-columns: 1fr;
            }

            .documento-main-content {
                flex-direction: column;
            }

            .documento-download-info {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
@endpush