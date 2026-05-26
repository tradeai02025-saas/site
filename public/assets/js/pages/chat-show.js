class ChatManager {
    constructor(conversaId, ultimaMensagemId, config = {}) {
        this.conversaId = conversaId;
        this.ultimaMensagemId = ultimaMensagemId;
        this.pollingInterval = null;
        this.pollingAtivo = true;
        this.enviandoMensagem = false;
        this.mensagensExistentes = new Set();
        this.minhasMensagensNaoLidas = new Set();
        this.conversaStatus = config.conversaStatus || 'aberta';
        this.usuarioId = config.usuarioId || null;
        this.marcarComoLidaTimeout = null;
        this.estavaNoFundo = true;

        this.urls = {
            marcarComoLida: config.urls && config.urls.marcarComoLida || '',
            buscarMensagens: config.urls && config.urls.buscarMensagens || '',
            enviarMensagem: config.urls && config.urls.enviarMensagem || '',
            resolverConversa: config.urls && config.urls.resolverConversa || '',
            reabrirConversa: config.urls && config.urls.reabrirConversa || '',
            sairConversa: config.urls && config.urls.sairConversa || '',
            assumirConversa: config.urls && config.urls.assumirConversa || ''
        };

        this.elementos = {
            chatMensagens: $('#chatMensagens'),
            formEnviar: $('#formEnviarMensagem'),
            inputMensagem: $('#inputMensagem'),
            inputAnexo: $('#inputAnexo'),
            btnAnexar: $('#btnAnexar'),
            btnRemoverAnexo: $('#btnRemoverAnexo'),
            btnEnviar: $('#btnEnviar'),
            anexoPreview: $('#anexoPreview'),
            anexoNome: $('#anexoNome'),
            btnEscalar: $('#btnEscalar'),
            btnTransferir: $('#btnTransferir'),
            btnResolver: $('#btnResolver'),
            btnReabrir: $('#btnReabrir'),
            btnSairConversa: $('#btnSairConversa'),
            btnAdicionarParticipante: $('#btnAdicionarParticipante'),
            btnAssumir: $('#btnAssumir')
        };

        this.init();
    }

    init() {
        this.carregarMensagensExistentes();
        this.scrollParaUltima();
        this.estavaNoFundo = true;
        this.marcarComoLida();
        this.iniciarPolling();
        this.bindEventos();
    }

    carregarMensagensExistentes() {
        this.elementos.chatMensagens.find('[data-mensagem-id]').each((index, element) => {
            const $el = $(element);
            const id = parseInt($el.data('mensagem-id'));
            this.mensagensExistentes.add(id);

            if ($el.hasClass('chat-message-mine') && !$el.find('.bi-check-all').length) {
                this.minhasMensagensNaoLidas.add(id);
            }
        });
    }

    scrollParaUltima() {
        const container = this.elementos.chatMensagens[0];
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    }

    estaNoFundoDoChat() {
        const container = this.elementos.chatMensagens[0];
        if (!container) return true;
        const distancia = container.scrollHeight - container.scrollTop - container.clientHeight;
        return distancia < 80;
    }

    marcarComoLida() {
        if (!this.urls.marcarComoLida) return;
        if (this.marcarComoLidaTimeout) {
            clearTimeout(this.marcarComoLidaTimeout);
        }
        this.marcarComoLidaTimeout = setTimeout(() => {
            $.ajax({
                url: this.urls.marcarComoLida,
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                error: () => {}
            });
        }, 400);
    }

    iniciarPolling() {
        $(document).on('visibilitychange.chatPolling', () => {
            this.pollingAtivo = !document.hidden;
            if (this.pollingAtivo && !this.enviandoMensagem && !this._buscandoMensagens) {
                this.buscarNovasMensagens();
            }
        });

        this.pollingInterval = setInterval(() => {
            if (this.pollingAtivo && !this.enviandoMensagem && !this._buscandoMensagens && !document.hidden) {
                this.buscarNovasMensagens();
            }
        }, 3000);
    }

    pararPolling() {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
            this.pollingInterval = null;
        }
        $(document).off('visibilitychange.chatPolling');
    }
buscarNovasMensagens() {
    if (!this.urls.buscarMensagens) return;

    this._buscandoMensagens = true;
    this.estavaNoFundo = this.estaNoFundoDoChat();

    const idsParaChecar = Array.from(this.minhasMensagensNaoLidas);

    $.ajax({
        url: this.urls.buscarMensagens,
        method: 'GET',
        timeout: 10000,
        data: {
            ultima_mensagem_id: this.ultimaMensagemId,
            ids_para_checar_leitura: idsParaChecar
        },
        success: (response) => {
            this._pollingErrors = 0;
            if (response.success) {
                if (response.status_conversa && response.status_conversa !== this.conversaStatus) {
                    this.conversaStatus = response.status_conversa;
                    this.atualizarStatusConversa(response.status_conversa);
                }

                if (Array.isArray(response.atualizacoes_leitura) && response.atualizacoes_leitura.length > 0) {
                    response.atualizacoes_leitura.forEach((upd) => {
                        this.aplicarStatusLeitura(upd);
                    });
                }

                if (response.mensagens.length > 0) {
                    let adicionouNova = false;
                    let recebiNovaDeOutro = false;

                    response.mensagens.forEach((msg) => {
                        if (!this.mensagensExistentes.has(msg.id)) {
                            this.adicionarMensagem(msg);
                            this.ultimaMensagemId = msg.id;
                            this.mensagensExistentes.add(msg.id);
                            adicionouNova = true;
                            if (!msg.is_mine) {
                                recebiNovaDeOutro = true;
                            }
                            if (msg.is_mine && !msg.lida) {
                                this.minhasMensagensNaoLidas.add(msg.id);
                            }
                        }
                    });

                    if (adicionouNova && this.estavaNoFundo) {
                        this.scrollParaUltima();
                    }

                    if (recebiNovaDeOutro && this.estaNoFundoDoChat() && !document.hidden) {
                        this.marcarComoLida();
                    }
                }
            }
        },
        error: (xhr) => {
            if (xhr.status === 403 || xhr.status === 404) {
                this.pararPolling();
                let msg = 'Conversa indisponível. Recarregue a página.';
                if (xhr.status === 403) msg = 'Sua permissão nesta conversa foi revogada.';
                if (xhr.status === 404) msg = 'Esta conversa foi removida.';
                toastr.warning(msg, 'Chat');
                return;
            }
            this._pollingErrors = (this._pollingErrors || 0) + 1;
            if (this._pollingErrors >= 5) {
                this.pararPolling();
                toastr.warning('Conexão com o chat perdida. Recarregue a página.', 'Chat');
            }
        },
        complete: () => {
            this._buscandoMensagens = false;
        }
    });
}

aplicarStatusLeitura(upd) {
    const $msg = this.elementos.chatMensagens.find(`[data-mensagem-id="${upd.id}"]`);
    if (!$msg.length) return;

    const $info = $msg.find('.chat-page-message-info');
    if (!$info.length) return;

    if (!$info.find('.bi-check-all').length) {
        const titulo = upd.data_leitura ? `Lida em ${upd.data_leitura}` : 'Lida';
        $info.append(`<i class="bi bi-check-all" title="${titulo}"></i>`);
    }
    this.minhasMensagensNaoLidas.delete(upd.id);
}

atualizarStatusConversa(novoStatus) {
    if (novoStatus === 'resolvida') {
        this.conversaStatus = 'resolvida';
        
        const footer = this.elementos.formEnviar.closest('.chat-page-footer');
        
        if (!footer.hasClass('blocked')) {
            footer.addClass('blocked');

            const textoPreservado = this.elementos.inputMensagem.val();

            this.elementos.inputMensagem.prop('disabled', true);
            this.elementos.btnEnviar.prop('disabled', true);

            if (!$('#blockedMessage').length) {
                footer.prepend(`
                    <div class="chat-page-blocked-message" id="blockedMessage">
                        <i class="bi bi-lock-fill"></i>
                        <span>Esta conversa foi resolvida e não aceita mais mensagens. Seu rascunho foi preservado.</span>
                    </div>
                `);
            }

            if (textoPreservado) {
                this.elementos.inputMensagem.val(textoPreservado);
            }
            
            if (this.elementos.btnResolver && this.elementos.btnResolver.length) {
                this.elementos.btnResolver.hide();
            }
            
            toastr.warning('Esta conversa foi marcada como resolvida. Seu texto foi preservado.', 'Conversa Resolvida', {
                timeOut: 5000,
                closeButton: true,
                positionClass: 'toast-top-right'
            });
        }
    } else if (novoStatus === 'aberta' || novoStatus === 'escalada') {
        this.conversaStatus = novoStatus;
        
        const footer = this.elementos.formEnviar.closest('.chat-page-footer');
        
        if (footer.hasClass('blocked')) {
            footer.removeClass('blocked');
            
            $('#blockedMessage').remove();
            
            this.elementos.inputMensagem.prop('disabled', false);
            this.elementos.btnEnviar.prop('disabled', false);
            
            if (this.elementos.btnReabrir && this.elementos.btnReabrir.length) {
                this.elementos.btnReabrir.hide();
            }
            
            if (this.elementos.btnResolver && this.elementos.btnResolver.length) {
                this.elementos.btnResolver.show();
            }
            
            toastr.success('Esta conversa foi reaberta. Você pode enviar mensagens novamente.', 'Conversa Reaberta', {
                timeOut: 5000,
                closeButton: true,
                positionClass: 'toast-top-right'
            });
            
            this.elementos.inputMensagem.focus();
        }
    }
}

    adicionarMensagem(msg) {
        if (this.mensagensExistentes.has(msg.id)) {
            return;
        }

        const isMinha = msg.is_mine;
        const html = this.construirHtmlMensagem(msg, isMinha);

        this.elementos.chatMensagens.append(html);
        this.mensagensExistentes.add(msg.id);
    }

    construirHtmlMensagem(msg, isMinha) {
        const classMensagem = isMinha ? 'chat-message-mine' : 'chat-message-other';
        const avatarHtml = `<div class="chat-page-avatar">${msg.remetente_iniciais}</div>`;

        const senderHtml = !isMinha ? `
            <div class="chat-page-message-sender">
                <strong>${this.escapeHtml(msg.remetente_nome)}</strong>
            </div>
        ` : '';

        const mensagemTextoHtml = msg.mensagem ? `
            <div class="chat-page-message-text">${this.escapeHtml(msg.mensagem)}</div>
        ` : '';

        const anexosHtml = this.construirHtmlAnexos(msg.anexos);

        return `
            <div class="chat-page-message ${classMensagem}" data-mensagem-id="${msg.id}">
                ${!isMinha ? avatarHtml : ''}
                <div class="chat-page-message-content">
                    ${senderHtml}
                    ${mensagemTextoHtml}
                    ${anexosHtml}
                    <div class="chat-page-message-info">
                        <small>${this.escapeHtml(msg.created_at)}</small>
                        ${isMinha && msg.lida ? '<i class="bi bi-check-all"></i>' : ''}
                    </div>
                </div>
                ${isMinha ? avatarHtml : ''}
            </div>
        `;
    }

    construirHtmlAnexos(anexos) {
        if (!anexos || anexos.length === 0) {
            return '';
        }

        return anexos.map(anexo => {
            const tipoIcone = this.obterIconeAnexo(anexo.tipo_arquivo);
            const tamanhoKB = (anexo.tamanho / 1024).toFixed(2);

            return `
                <div class="chat-page-message-attachment">
                    <i class="bi bi-${tipoIcone}-fill"></i>
                    <a href="${anexo.preview_url}" target="_blank">${this.escapeHtml(anexo.nome_original)}</a>
                    <small>(${tamanhoKB} KB)</small>
                    <div class="chat-page-attachment-actions">
                        <a href="${anexo.download_url}" class="chat-page-attachment-btn" title="Download">
                            <i class="bi bi-download"></i>
                        </a>
                    </div>
                </div>
            `;
        }).join('');
    }

    obterIconeAnexo(tipoArquivo) {
        const icones = {
            'imagem': 'image',
            'pdf': 'file-pdf',
            'doc': 'file-word'
        };
        return icones[tipoArquivo] || 'file-earmark';
    }

    escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, (m) => map[m]);
    }

    bindEventos() {
        this.elementos.btnAnexar.on('click', () => {
            this.elementos.inputAnexo.click();
        });

        this.elementos.inputAnexo.on('change', (e) => {
            this.handleAnexoSelecionado(e.target.files[0]);
        });

        this.elementos.btnRemoverAnexo.on('click', () => {
            this.removerAnexo();
        });

        this.elementos.formEnviar.on('submit', (e) => {
            e.preventDefault();
            this.enviarMensagem();
        });

        this.elementos.inputMensagem.on('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.elementos.formEnviar.submit();
            }
        }).on('input', (e) => {
            this.autoResizeTextarea(e.target);
        });

        if (this.elementos.btnEscalar.length) {
            this.elementos.btnEscalar.on('click', () => {
                this.abrirModalEscalar();
            });
        }

        if (this.elementos.btnTransferir.length) {
            this.elementos.btnTransferir.on('click', () => {
                this.abrirModalTransferir();
            });
        }

        if (this.elementos.btnResolver.length) {
            this.elementos.btnResolver.on('click', () => {
                this.resolverConversa();
            });
        }

        if (this.elementos.btnReabrir.length) {
            this.elementos.btnReabrir.on('click', () => {
                this.reabrirConversa();
            });
        }

        if (this.elementos.btnSairConversa.length) {
            this.elementos.btnSairConversa.on('click', () => {
                this.sairConversa();
            });
        }

        if (this.elementos.btnAdicionarParticipante.length) {
            this.elementos.btnAdicionarParticipante.on('click', () => {
                this.abrirModalAdicionarParticipante();
            });
        }

        if (this.elementos.btnAssumir.length) {
            this.elementos.btnAssumir.on('click', () => {
                this.assumirConversa();
            });
        }

        $(window).on('beforeunload', () => {
            this.pararPolling();
        });
    }

    handleAnexoSelecionado(arquivo) {
        if (!arquivo) return;

        if (arquivo.size > 45 * 1024 * 1024) {
            Swal.fire('Erro', 'Arquivo muito grande! Máximo 45MB.', 'error');
            this.elementos.inputAnexo.val('');
            return;
        }

        this.elementos.anexoNome.text(arquivo.name);
        this.elementos.anexoPreview.removeClass('d-none');
    }

    removerAnexo() {
        this.elementos.inputAnexo.val('');
        this.elementos.anexoPreview.addClass('d-none');
    }

    autoResizeTextarea(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
    }

enviarMensagem() {
    if (this.conversaStatus === 'resolvida') {
        Swal.fire('Atenção', 'Esta conversa está resolvida. Não é possível enviar mensagens.', 'warning');
        return;
    }

    const mensagem = this.elementos.inputMensagem.val().trim();
    const temAnexo = this.elementos.inputAnexo[0].files.length > 0;

    if (!mensagem && !temAnexo) {
        Swal.fire('Atenção', 'Digite uma mensagem ou anexe um arquivo.', 'warning');
        return;
    }

    this.enviandoMensagem = true;
    this.elementos.btnEnviar.prop('disabled', true);

    const formData = new FormData(this.elementos.formEnviar[0]);

    $.ajax({
        url: this.urls.enviarMensagem,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: (response) => {
            if (response.success) {
                if (!this.mensagensExistentes.has(response.mensagem.id)) {
                    this.adicionarMensagem(response.mensagem);
                    this.ultimaMensagemId = response.mensagem.id;
                    this.mensagensExistentes.add(response.mensagem.id);
                    if (response.mensagem.is_mine) {
                        this.minhasMensagensNaoLidas.add(response.mensagem.id);
                    }
                    this.scrollParaUltima();
                }
                this.limparFormulario();
            }
        },
        error: (xhr) => {
            let mensagem = 'Erro ao enviar mensagem. Tente novamente.';

            if (xhr.responseJSON && xhr.responseJSON.message) {
                mensagem = xhr.responseJSON.message;
            } else if (xhr.status === 422) {
                mensagem = 'Dados inválidos. Verifique o arquivo e tente novamente.';
            } else if (xhr.status === 403) {
                mensagem = 'Você não tem permissão para enviar mensagens nesta conversa.';
            }

            Swal.fire('Erro', mensagem, 'error');
        },
        complete: () => {
            this.enviandoMensagem = false;
            this.elementos.btnEnviar.prop('disabled', false);
        }
    });
}

    limparFormulario() {
        this.elementos.inputMensagem.val('').css('height', 'auto');
        this.removerAnexo();
    }

    abrirModalEscalar() {
        $('#modalEscalar').modal('show');
    }

    abrirModalTransferir() {
        $('#modalTransferir').modal('show');
    }

    resolverConversa() {
        Swal.fire({
            title: 'Resolver conversa?',
            text: 'Deseja marcar esta conversa como resolvida?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sim, resolver',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#10b981'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: this.urls.resolverConversa,
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: (response) => {
                        Swal.fire('Sucesso', response.message, 'success');
                        setTimeout(() => location.reload(), 1500);
                    },
                    error: (xhr) => {
                        Swal.fire('Erro', xhr.responseJSON && xhr.responseJSON.message || 'Erro ao resolver conversa', 'error');
                    }
                });
            }
        });
    }

    reabrirConversa() {
        Swal.fire({
            title: 'Reabrir conversa?',
            text: 'Deseja reabrir esta conversa?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sim, reabrir',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#3b82f6'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: this.urls.reabrirConversa,
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: (response) => {
                        Swal.fire('Sucesso', response.message, 'success');
                        setTimeout(() => location.reload(), 1500);
                    },
                    error: (xhr) => {
                        Swal.fire('Erro', xhr.responseJSON && xhr.responseJSON.message || 'Erro ao reabrir conversa', 'error');
                    }
                });
            }
        });
    }

    sairConversa() {
        Swal.fire({
            title: 'Sair da conversa?',
            text: 'Você não poderá mais participar desta conversa.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sim, sair',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#ef4444'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: this.urls.sairConversa,
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: (response) => {
                        Swal.fire('Sucesso', response.message, 'success');
                        setTimeout(() => window.location.href = response.redirect_url, 1500);
                    },
                    error: (xhr) => {
                        Swal.fire('Erro', xhr.responseJSON && xhr.responseJSON.message || 'Erro ao sair da conversa', 'error');
                    }
                });
            }
        });
    }

    assumirConversa() {
        Swal.fire({
            title: 'Assumir conversa?',
            html: `
                <div class="swal-content">
                    <p>Você passará a ser o responsável por esta conversa.</p>
                    <p><strong>O operacional anterior será removido.</strong></p>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sim, assumir',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#f59e0b'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: this.urls.assumirConversa,
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: (response) => {
                        Swal.fire('Sucesso', response.message, 'success');
                        setTimeout(() => location.reload(), 1500);
                    },
                    error: (xhr) => {
                        Swal.fire('Erro', xhr.responseJSON && xhr.responseJSON.message || 'Erro ao assumir conversa', 'error');
                    }
                });
            }
        });
    }

    abrirModalAdicionarParticipante() {
        $('#modalAdicionarParticipante').modal('show');
    }
}

class UsuarioSelectorManager {
    constructor(modalId, tipo, config = {}) {
        this.modalId = modalId;
        this.tipo = tipo;
        this.usuarioSelecionado = null;
        this.timeoutBusca = null;
        this.excludeIds = config.excludeIds || [];
        this.urlBuscar = config.urlBuscar || '';

        this.elementos = {
            searchInput: $(`#search${tipo}`),
            dropdown: $(`#dropdown${tipo}`),
            loading: $(`#loading${tipo}`),
            preview: $(`#preview${tipo}`),
            previewAvatar: $(`#preview${tipo}Avatar`),
            previewNome: $(`#preview${tipo}Nome`),
            previewDetalhes: $(`#preview${tipo}Detalhes`),
            btnRemove: $(`#btnRemove${tipo}`),
            inputHidden: $(`#usuario_${modalId}_id`)
        };

        this.init();
    }

    init() {
        this.bindEventos();
    }

    bindEventos() {
        this.elementos.searchInput.on('input', (e) => {
            this.handleSearch(e.target.value);
        });

        this.elementos.btnRemove.on('click', () => {
            this.removerSelecao();
        });

        $(document).on('click', (e) => {
            if (!$(e.target).closest('.usuario-selector').length) {
                this.elementos.dropdown.removeClass('show');
            }
        });

        $(`#modal${this.tipo}`).on('hidden.bs.modal', () => {
            this.resetar();
        });
    }

    handleSearch(search) {
        clearTimeout(this.timeoutBusca);

        if (search.length < 2) {
            this.elementos.dropdown.html(`
                <div class="dropdown-empty-modal">
                    <i class="bi bi-search"></i>
                    <p>Digite pelo menos 2 caracteres</p>
                </div>
            `).removeClass('show');
            this.elementos.loading.removeClass('show');
            return;
        }

        this.elementos.loading.addClass('show');

        this.timeoutBusca = setTimeout(() => {
            this.buscarUsuarios(search);
        }, 400);
    }

    buscarUsuarios(search) {
        if (!this.urlBuscar) {
            toastr.error('Configuração de busca incompleta.', 'Chat');
            return;
        }

        $.ajax({
            url: this.urlBuscar,
            method: 'GET',
            data: {
                search: search,
                exclude: this.excludeIds
            },
            success: (response) => {
                this.elementos.loading.removeClass('show');

                if (response.success && response.usuarios.length > 0) {
                    const usuarios = response.usuarios.slice(0, 8);
                    let html = usuarios.map(u => {
                        return `
                            <div class="dropdown-item-usuario-modal" data-usuario='${JSON.stringify(u)}'>
                                <div class="dropdown-usuario-avatar-modal">${u.iniciais}</div>
                                <div class="dropdown-usuario-info-modal">
                                    <div class="dropdown-usuario-name-modal">${u.name}</div>
                                    <div class="dropdown-usuario-details-modal">${u.email}</div>
                                </div>
                            </div>
                        `;
                    }).join('');

                    this.elementos.dropdown.html(html).addClass('show');

                    this.elementos.dropdown.off('click', '.dropdown-item-usuario-modal').on('click', '.dropdown-item-usuario-modal', (e) => {
                        const usuario = JSON.parse($(e.currentTarget).attr('data-usuario'));
                        this.selecionarUsuario(usuario);
                    });
                } else {
                    this.elementos.dropdown.html(`
                        <div class="dropdown-empty-modal">
                            <i class="bi bi-person-x"></i>
                            <p>Nenhum usuário encontrado</p>
                        </div>
                    `).addClass('show');
                }
            },
            error: () => {
                this.elementos.loading.removeClass('show');
                Swal.fire('Erro', 'Erro ao buscar usuários', 'error');
            }
        });
    }

    selecionarUsuario(usuario) {
        this.usuarioSelecionado = usuario;
        this.elementos.inputHidden.val(usuario.id);
        this.elementos.previewAvatar.text(usuario.iniciais);
        this.elementos.previewNome.text(usuario.name);
        this.elementos.previewDetalhes.text(usuario.email);
        this.elementos.preview.addClass('show');
        this.elementos.dropdown.removeClass('show');
        this.elementos.searchInput.val('');
    }

    removerSelecao() {
        this.usuarioSelecionado = null;
        this.elementos.inputHidden.val('');
        this.elementos.preview.removeClass('show');
        this.elementos.searchInput.val('').focus();
    }

    resetar() {
        this.removerSelecao();
        this.elementos.dropdown.html(`
            <div class="dropdown-empty-modal">
                <i class="bi bi-search"></i>
                <p>Digite pelo menos 2 caracteres para buscar</p>
            </div>
        `).removeClass('show');
        this.elementos.loading.removeClass('show');
    }

    getUsuarioSelecionado() {
        return this.usuarioSelecionado;
    }
}

class ModalFormsManager {
    constructor(conversaId, urls) {
        this.conversaId = conversaId;
        this.urls = urls;
        this.init();
    }

    init() {
        this.bindFormEscalar();
        this.bindFormTransferir();
        this.bindFormAdicionarParticipante();
    }

    bindFormEscalar() {
        $('#formEscalar').on('submit', (e) => {
            e.preventDefault();

            const btn = $('#btnSubmitEscalar');
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Escalando...');

            const data = {
                usuario_id: $('#usuario_escalar_id').val() || null,
                motivo: $('#motivo_escalar').val()
            };

            $.ajax({
                url: this.urls.escalarConversa,
                method: 'POST',
                data: data,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: (response) => {
                    $('#modalEscalar').modal('hide');
                    Swal.fire('Sucesso', response.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                },
                error: (xhr) => {
                    btn.prop('disabled', false).html('<i class="bi bi-arrow-up-circle-fill"></i> Escalar Conversa');
                    Swal.fire('Erro', xhr.responseJSON && xhr.responseJSON.message || 'Erro ao escalar conversa', 'error');
                }
            });
        });
    }

    bindFormTransferir() {
        $('#formTransferir').on('submit', (e) => {
            e.preventDefault();

            const paraUsuarioId = $('#usuario_transferir_id').val();

            if (!paraUsuarioId) {
                Swal.fire('Atenção', 'Selecione um usuário para transferir', 'warning');
                return;
            }

            const btn = $('#btnSubmitTransferir');
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Transferindo...');

            const data = {
                para_usuario_id: paraUsuarioId,
                motivo: $('#motivo_transferir').val()
            };

            $.ajax({
                url: this.urls.transferirConversa,
                method: 'POST',
                data: data,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: (response) => {
                    $('#modalTransferir').modal('hide');
                    Swal.fire('Sucesso', response.message, 'success');
                    setTimeout(() => window.location.href = response.redirect_url, 1500);
                },
                error: (xhr) => {
                    btn.prop('disabled', false).html('<i class="bi bi-arrow-left-right"></i> Confirmar Transferência');
                    Swal.fire('Erro', xhr.responseJSON && xhr.responseJSON.message || 'Erro ao transferir conversa', 'error');
                }
            });
        });
    }

    bindFormAdicionarParticipante() {
        $('#formAdicionarParticipante').on('submit', (e) => {
            e.preventDefault();

            const usuarioId = $('#usuario_adicionar_id').val();

            if (!usuarioId) {
                Swal.fire('Atenção', 'Selecione um usuário', 'warning');
                return;
            }

            const btn = $('#btnSubmitAdicionar');
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Adicionando...');

            const data = { usuario_id: usuarioId };

            $.ajax({
                url: this.urls.adicionarParticipante,
                method: 'POST',
                data: data,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: (response) => {
                    $('#modalAdicionarParticipante').modal('hide');
                    Swal.fire('Sucesso', response.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                },
                error: (xhr) => {
                    btn.prop('disabled', false).html('<i class="bi bi-person-plus-fill"></i> Adicionar Participante');
                    Swal.fire('Erro', xhr.responseJSON && xhr.responseJSON.message || 'Erro ao adicionar participante', 'error');
                }
            });
        });
    }
}

function initChatPage(config) {
    if (!config.conversaId || !config.ultimaMensagemId) {
        toastr.error('Configuração inválida para inicialização do chat.', 'Chat');
        return;
    }

    const chatManager = new ChatManager(config.conversaId, config.ultimaMensagemId, {
        conversaStatus: config.conversaStatus,
        urls: config.chatUrls
    });

    const escalarSelector = new UsuarioSelectorManager('escalar', 'Escalar', {
        excludeIds: config.excludeIds,
        urlBuscar: config.urlBuscarUsuarios
    });

    const transferirSelector = new UsuarioSelectorManager('transferir', 'Transferir', {
        excludeIds: config.excludeIds,
        urlBuscar: config.urlBuscarUsuarios
    });

    const adicionarSelector = new UsuarioSelectorManager('adicionar', 'Adicionar', {
        excludeIds: config.excludeIds,
        urlBuscar: config.urlBuscarUsuarios
    });

    const modalFormsManager = new ModalFormsManager(config.conversaId, config.modalUrls);

    return {
        chatManager,
        escalarSelector,
        transferirSelector,
        adicionarSelector,
        modalFormsManager
    };
}

window.ChatManager = ChatManager;
window.UsuarioSelectorManager = UsuarioSelectorManager;
window.ModalFormsManager = ModalFormsManager;
window.initChatPage = initChatPage;