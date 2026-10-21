$(document).ready(function() {
    const StatusFormManager = {
        cache: {},
        defaultColor: '#005e82',

        init() {
            this.cacheElements();
            this.bindEvents();
            this.updatePreview();
            this.updateCharacterCount();
        },

        cacheElements() {
            this.cache = {
                $form: $('#statusForm'),
                $nome: $('#nome'),
                $tipo: $('#tipo'),
                $cor: $('#cor'),
                $corHex: $('#corHex'),
                $descricao: $('#descricao'),
                $ativo: $('#ativo'),
                $colorPreview: $('#colorPreview'),
                $statusBadgePreview: $('#statusBadgePreview'),
                $resetColor: $('#resetColor'),
                $submitBtn: $('#submitBtn'),
                $charCount: $('#charCount')
            };
        },

        bindEvents() {
            this.cache.$cor.on('input', () => this.handleColorChange());
            this.cache.$corHex.on('input', () => this.handleHexInput());
            this.cache.$corHex.on('blur', () => this.validateHexInput());
            this.cache.$colorPreview.on('click', () => this.cache.$cor.click());
            this.cache.$resetColor.on('click', () => this.resetColor());
            $('.preset-color').on('click', (e) => this.selectPreset(e));
            this.cache.$nome.on('input', () => this.updatePreview());
            this.cache.$descricao.on('input', () => this.updateCharacterCount());
            this.cache.$form.on('submit', (e) => this.handleSubmit(e));
        },

        handleColorChange() {
            const color = this.cache.$cor.val();
            this.cache.$corHex.val(color.toUpperCase());
            this.updateColorPreview(color);
        },

        handleHexInput() {
            let hex = this.cache.$corHex.val().trim();

            if (!hex.startsWith('#')) {
                hex = '#' + hex;
            }

            hex = hex.substring(0, 7);
            this.cache.$corHex.val(hex.toUpperCase());

            if (/^#[0-9A-Fa-f]{6}$/.test(hex)) {
                this.cache.$cor.val(hex);
                this.updateColorPreview(hex);
            }
        },

        validateHexInput() {
            const hex = this.cache.$corHex.val().trim();

            if (!/^#[0-9A-Fa-f]{6}$/.test(hex)) {
                const currentColor = this.cache.$cor.val();
                this.cache.$corHex.val(currentColor.toUpperCase());
                toastr.warning('Cor inválida. Use o formato #RRGGBB');
            }
        },

        updateColorPreview(color) {
            this.cache.$colorPreview.css('background', color);
            this.cache.$statusBadgePreview.css('background', color);
        },

        resetColor() {
            this.cache.$cor.val(this.defaultColor);
            this.cache.$corHex.val(this.defaultColor);
            this.updateColorPreview(this.defaultColor);
            toastr.info('Cor restaurada para o padrão');
        },

        selectPreset(e) {
            const color = $(e.currentTarget).data('color');
            this.cache.$cor.val(color);
            this.cache.$corHex.val(color.toUpperCase());
            this.updateColorPreview(color);

            $(e.currentTarget).css('transform', 'scale(0.9)');
            setTimeout(() => {
                $(e.currentTarget).css('transform', '');
            }, 150);
        },

        updatePreview() {
            const nome = this.cache.$nome.val() || 'Novo Status';
            this.cache.$statusBadgePreview.find('.badge-text').text(nome);
        },

        updateCharacterCount() {
            const length = this.cache.$descricao.val().length;
            this.cache.$charCount.text(length);

            if (length > 900) {
                this.cache.$charCount.css('color', '#dc2626');
            } else {
                this.cache.$charCount.css('color', 'var(--text-muted)');
            }
        },

        handleSubmit(e) {
            const nome = this.cache.$nome.val().trim();
            const tipo = this.cache.$tipo.val();
            const cor = this.cache.$cor.val();

            if (!nome) {
                e.preventDefault();
                toastr.error('O nome do status é obrigatório');
                this.cache.$nome.focus();
                return false;
            }

            if (!tipo) {
                e.preventDefault();
                toastr.error('Selecione o tipo do status');
                this.cache.$tipo.focus();
                return false;
            }

            if (!/^#[0-9A-Fa-f]{6}$/.test(cor)) {
                e.preventDefault();
                toastr.error('A cor deve estar no formato hexadecimal (#RRGGBB)');
                this.cache.$corHex.focus();
                return false;
            }

            this.cache.$submitBtn.prop('disabled', true).html(
                '<span class="spinner-border spinner-border-sm" style="width: 1rem; height: 1rem; border-width: 2px;"></span> <span>Salvando...</span>'
            );

            return true;
        }
    };

    StatusFormManager.init();
});