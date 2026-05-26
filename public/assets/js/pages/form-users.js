
(function ($) {
    'use strict';

    const UserFormManager = {
        cache: {},
        isGenerating: false,

        init() {
            this.cacheElements();
            this.initMasks();
            this.bindEvents();
            this.loadInitialState();
        },

        cacheElements() {
            this.cache = {
                $form: $('#userForm'),
                $step1: $('#step1'),
                $step2: $('#step2'),
                $progressStep1: $('.progress-step[data-step="1"]'),
                $progressStep2: $('.progress-step[data-step="2"]'),
                $name: $('#name'),
                $email: $('#email'),
                $password: $('#password'),
                $passwordConfirm: $('#password_confirmation'),
                $passwordMatchMessage: $('#passwordMatchMessage'),
                $passwordRequirements: $('#passwordRequirements'),
                $generatePassword: $('#generatePassword'),
                $roleInputs: $('input[name="role"]'),
                $genOpSection: $('#genOpSection'),
                $fornecedorSection: $('#fornecedorSection'),
                $representanteSection: $('#representanteSection'),
                $adminSection: $('#adminSection'),
                $nomeCompleto: $('#nome_completo'),
                $razaoSocial: $('#razao_social'),
                $cnpj: $('#cnpj'),
                $cnpjFeedback: $('#cnpjFeedback'),
                $cep: $('#cep'),
                $buscarCep: $('#buscarCep'),
                $estado: $('#estado'),
                $cidade: $('#cidade'),
                $bairro: $('#bairro'),
                $logradouro: $('#logradouro'),
                $numero: $('#numero'),
                $submitBtn: $('#submitBtn')
            };
        },

        initMasks() {
            $('.telefone-mask').mask('(00) 00000-0000');
            $('.cnpj-mask').mask('00.000.000/0000-00');
            $('.cep-mask').mask('00000-000');

            const SPMaskBehavior = function (val) {
                return val.replace(/\D/g, '').length > 11 ? '00.000.000/0000-00' : '000.000.000-009';
            };
            const spOptions = {
                onKeyPress: function (val, e, field, options) {
                    field.mask(SPMaskBehavior.apply({}, arguments), options);
                }
            };

            $(document).on('focus', '.cpf-cnpj-mask', function () {
                $(this).mask(SPMaskBehavior($(this).val()), spOptions);
            });

            $(document).on('paste', '.cpf-cnpj-mask', function (e) {
                e.preventDefault();
                const pastedData = (e.originalEvent.clipboardData || window.clipboardData).getData('text');
                const digits = pastedData.replace(/\D/g, '');
                $(this).val('');
                $(this).mask(SPMaskBehavior(digits), spOptions);
                $(this).val(digits).trigger('input');
            });

            $('.cpf-cnpj-mask').each(function () {
                $(this).mask(SPMaskBehavior($(this).val()), spOptions);
            });
        },

        bindEvents() {
            this.cache.$roleInputs.on('change', (e) => this.handleRoleChange(e));
            this.cache.$form.on('click', '#nextStep, #submitBtnStep1', (e) => this.handleNextStep(e));
            this.cache.$form.on('click', '#prevStep', () => this.handlePrevStep());

            $('.toggle-password').on('click', (e) => this.togglePasswordVisibility(e));
            this.cache.$generatePassword.on('click', () => this.generatePassword());
            this.cache.$password.on('input', () => this.handlePasswordInput());
            this.cache.$password.on('blur', () => this.hidePasswordField(this.cache.$password));
            this.cache.$passwordConfirm.on('input', () => this.checkPasswordMatch());
            this.cache.$passwordConfirm.on('blur', () => this.hidePasswordField(this.cache.$passwordConfirm));
            this.cache.$password.on('focus', () => this.handlePasswordFocus());
            this.cache.$passwordConfirm.on('focus', () => this.handlePasswordFocus());

            this.cache.$cnpj.on('blur', () => this.validateCNPJ());
            this.cache.$buscarCep.on('click', (e) => this.buscarCEP(e));
            this.cache.$form.on('submit', (e) => this.handleSubmit(e));
        },

        loadInitialState() {
            const $checkedRole = this.cache.$roleInputs.filter(':checked');
            if ($checkedRole.length) {
                this.showRoleFields($checkedRole.val());
            }
        },

        handleRoleChange(e) {
            const role = $(e.target).val();
            this.showRoleFields(role);
            this.updateNextButton();
        },

        showRoleFields(role) {
            const sections = [this.cache.$genOpSection, this.cache.$fornecedorSection, this.cache.$representanteSection, this.cache.$adminSection];
            sections.forEach($section => $section.hide());

            this.cache.$nomeCompleto.add(this.cache.$razaoSocial).add(this.cache.$cnpj).removeAttr('required');

            const roleMap = {
                'gerencial': () => {
                    this.cache.$genOpSection.show();
                    this.cache.$nomeCompleto.attr('required', 'required');
                },
                'operacional': () => {
                    this.cache.$genOpSection.show();
                    this.cache.$nomeCompleto.attr('required', 'required');
                },
                'fornecedor': () => {
                    this.cache.$fornecedorSection.show();
                    this.cache.$razaoSocial.add(this.cache.$cnpj).attr('required', 'required');
                },
                'representante': () => {
                    this.cache.$representanteSection.show();
                },
                'admin': () => {
                    this.cache.$adminSection.show();
                }
            };

            if (roleMap[role]) {
                roleMap[role]();
            }
        },

        updateNextButton() {
            const role = this.cache.$roleInputs.filter(':checked').val();
            const $nextBtn = $('#nextStep');
            const isEdit = this.cache.$form.find('input[name="_method"][value="PUT"]').length > 0;
            const submitText = isEdit ? 'Atualizar Usuário' : 'Salvar Usuário';

            if (role === 'admin' || role === 'representante') {
                $nextBtn.html(`<i class="bi bi-check-circle-fill"></i> <span>${submitText}</span>`)
                    .attr('type', 'submit')
                    .attr('id', 'submitBtnStep1');
            } else {
                $nextBtn.html('<span>Continuar</span> <i class="bi bi-arrow-right"></i>')
                    .attr('type', 'button')
                    .attr('id', 'nextStep');
            }
        },

        handleNextStep(e) {
            const $btn = $(e.currentTarget);

            if ($btn.attr('type') === 'submit') {
                if (!this.validateStep1()) {
                    e.preventDefault();
                    return false;
                }

                const role = this.cache.$roleInputs.filter(':checked').val();
                if (role === 'admin') {
                    $btn.prop('disabled', true).html(
                        '<span class="spinner-border spinner-border-sm" style="width: 1rem; height: 1rem; border-width: 2px;"></span> <span>Salvando...</span>'
                    );
                    this.cache.$form.submit();
                }
                return;
            }

            if (!this.validateStep1()) {
                e.preventDefault();
                return;
            }

            this.goToStep2();
        },

        validateStep1() {
            const name = this.cache.$name.val().trim();
            const email = this.cache.$email.val().trim();
            const role = this.cache.$roleInputs.filter(':checked').val();
            const isEdit = this.cache.$form.find('input[name="_method"][value="PUT"]').length > 0;

            if (!name) {
                toastr.error('Preencha o nome do usuário');
                this.cache.$name.focus();
                return false;
            }

            if (!email) {
                toastr.error('Preencha o email do usuário');
                this.cache.$email.focus();
                return false;
            }

            if (!isEdit) {
                const password = this.cache.$password.val();
                const passwordConfirm = this.cache.$passwordConfirm.val();

                if (!password) {
                    toastr.error('A senha é obrigatória');
                    this.cache.$password.focus();
                    return false;
                }

                if (!this.isPasswordValid(password)) {
                    toastr.error('A senha não atende aos requisitos de segurança');
                    this.cache.$password.focus();
                    return false;
                }

                if (password !== passwordConfirm) {
                    toastr.error('As senhas não coincidem');
                    this.cache.$passwordConfirm.focus();
                    return false;
                }
            }

            if (!role) {
                toastr.error('Selecione um perfil de acesso');
                return false;
            }

            return true;
        },

        isPasswordValid(password) {
            return password.length >= 8 &&
                /[A-Z]/.test(password) &&
                /[a-z]/.test(password) &&
                /[0-9]/.test(password) &&
                /[^A-Za-z0-9\s]/.test(password);
        },

        goToStep2() {
            this.cache.$step1.removeClass('active');
            this.cache.$step2.addClass('active');
            this.cache.$progressStep1.addClass('completed');
            this.cache.$progressStep2.addClass('active');

            const role = this.cache.$roleInputs.filter(':checked').val();
            this.showRoleFields(role);

            requestAnimationFrame(() => {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });

            toastr.success('Preencha os dados complementares do perfil');
        },

        handlePrevStep() {
            this.cache.$step2.removeClass('active');
            this.cache.$step1.addClass('active');
            this.cache.$progressStep2.removeClass('active');
            this.cache.$progressStep1.removeClass('completed');

            requestAnimationFrame(() => {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        },

        togglePasswordVisibility(e) {
            const $button = $(e.currentTarget);
            const target = $button.data('target');
            const $input = $(`#${target}`);

            if ($button.prop('disabled')) return;

            $button.prop('disabled', true);

            const newType = $input.attr('type') === 'password' ? 'text' : 'password';
            $input.attr('type', newType);

            const $icon = $button.find('i');
            $button.addClass('animating');

            if (newType === 'text') {
                $icon.removeClass('bi-eye').addClass('bi-eye-slash');
                $button.attr('title', 'Ocultar senha');
            } else {
                $icon.removeClass('bi-eye-slash').addClass('bi-eye');
                $button.attr('title', 'Mostrar senha');
            }

            setTimeout(() => {
                $button.removeClass('animating').prop('disabled', false);
            }, 300);
        },

        hidePasswordField($input) {
            if ($input.attr('type') === 'text') {
                $input.attr('type', 'password');

                const inputId = $input.attr('id');
                const $button = $(`.toggle-password[data-target="${inputId}"]`);
                const $icon = $button.find('i');

                $icon.removeClass('bi-eye-slash').addClass('bi-eye');
                $button.attr('title', 'Mostrar senha');
            }
        },

        handlePasswordFocus() {
            const currentId = $(document.activeElement).attr('id');
            $('#password, #password_confirmation').each((_, el) => {
                const $el = $(el);
                if ($el.attr('id') !== currentId) {
                    this.hidePasswordField($el);
                }
            });
        },

        generatePassword() {
            if (this.isGenerating) return;

            this.isGenerating = true;
            const $button = this.cache.$generatePassword;

            $button.prop('disabled', true).addClass('generating');

            const generatedPassword = this.createSecurePassword();

            this.cache.$password.val(generatedPassword).attr('type', 'text');
            this.cache.$passwordConfirm.val(generatedPassword).attr('type', 'password');

            const $passIcon = $(`.toggle-password[data-target="password"] i`);
            $passIcon.removeClass('bi-eye').addClass('bi-eye-slash');

            const $confIcon = $(`.toggle-password[data-target="password_confirmation"] i`);
            $confIcon.removeClass('bi-eye-slash').addClass('bi-eye');

            this.checkPasswordStrength(generatedPassword);
            this.checkPasswordMatch();

            toastr.success('Senha forte gerada com sucesso!', 'Gerador de Senha', { timeOut: 4000 });

            setTimeout(() => {
                $button.removeClass('generating').prop('disabled', false);
                this.isGenerating = false;
            }, 600);
        },

        createSecurePassword(length = 16) {
            const uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            const lowercase = 'abcdefghijklmnopqrstuvwxyz';
            const numbers = '0123456789';
            const special = '@$!%*#?&^_-~.+=';
            const all = uppercase + lowercase + numbers + special;

            let password = '';
            password += uppercase[Math.floor(Math.random() * uppercase.length)];
            password += lowercase[Math.floor(Math.random() * lowercase.length)];
            password += numbers[Math.floor(Math.random() * numbers.length)];
            password += special[Math.floor(Math.random() * special.length)];

            for (let i = password.length; i < length; i++) {
                password += all[Math.floor(Math.random() * all.length)];
            }

            return password.split('').sort(() => Math.random() - 0.5).join('');
        },

        handlePasswordInput() {
            const password = this.cache.$password.val();

            if (password.length > 0) {
                this.cache.$passwordRequirements.show();
                this.checkPasswordStrength(password);
            } else {
                this.cache.$passwordRequirements.hide();
            }

            this.checkPasswordMatch();
        },

        checkPasswordStrength(password) {
            const requirements = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /[0-9]/.test(password),
                special: /[^A-Za-z0-9\s]/.test(password)
            };

            $('#req-length').toggleClass('valid', requirements.length);
            $('#req-uppercase').toggleClass('valid', requirements.uppercase);
            $('#req-lowercase').toggleClass('valid', requirements.lowercase);
            $('#req-number').toggleClass('valid', requirements.number);
            $('#req-special').toggleClass('valid', requirements.special);

            let strength = 0;
            Object.values(requirements).forEach(valid => {
                if (valid) strength++;
            });

            return {
                score: strength,
                isValid: strength === 5
            };
        },

        checkPasswordMatch() {
            const password = this.cache.$password.val();
            const confirmation = this.cache.$passwordConfirm.val();
            const $message = this.cache.$passwordMatchMessage;

            if (confirmation.length === 0) {
                $message.text('').removeClass('match no-match');
                return false;
            }

            if (password === confirmation) {
                $message.text('✓ As senhas coincidem').removeClass('no-match').addClass('match');
                return true;
            } else {
                $message.text('✗ As senhas não coincidem').removeClass('match').addClass('no-match');
                return false;
            }
        },

        validateCNPJ() {
            const cnpj = this.cache.$cnpj.val();

            if (!cnpj) {
                this.cache.$cnpjFeedback.html('');
                return;
            }

            if (cnpj.length === 18) {
                const isValid = this.isCNPJValid(cnpj);
                const color = isValid ? '#059669' : '#dc2626';
                const icon = isValid ? 'check-circle' : 'x-circle';
                const text = isValid ? 'CNPJ válido' : 'CNPJ inválido';

                this.cache.$cnpjFeedback.html(
                    `<small style="color: ${color}; display: flex; align-items: center; gap: 0.375rem; font-size: 0.8125rem; margin-top: 0.25rem;"><i class="bi bi-${icon}"></i> ${text}</small>`
                );
            } else {
                this.cache.$cnpjFeedback.html(
                    '<small style="color: #d97706; display: flex; align-items: center; gap: 0.375rem; font-size: 0.8125rem; margin-top: 0.25rem;"><i class="bi bi-exclamation-circle"></i> CNPJ incompleto</small>'
                );
            }
        },

        isCNPJValid(cnpj) {
            cnpj = cnpj.replace(/[^\d]+/g, '');
            if (cnpj.length !== 14 || /^(\d)\1+$/.test(cnpj)) return false;

            const calcDigit = (base, weights) => {
                const sum = base.split('').reduce((acc, num, idx) => acc + parseInt(num) * weights[idx], 0);
                const remainder = sum % 11;
                return remainder < 2 ? 0 : 11 - remainder;
            };

            const weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
            const weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

            const base = cnpj.substring(0, 12);
            const digit1 = calcDigit(base, weights1);
            const digit2 = calcDigit(base + digit1, weights2);

            return cnpj === base + digit1 + digit2;
        },

        buscarCEP(e) {
            e.preventDefault();
            const cep = this.cache.$cep.val().replace(/\D/g, '');

            if (cep.length !== 8) {
                toastr.warning('Digite um CEP válido com 8 dígitos');
                return;
            }

            const $icon = this.cache.$buscarCep.find('i');
            $icon.removeClass('bi-search').addClass('bi-arrow-clockwise');
            this.cache.$buscarCep.prop('disabled', true);

            $.ajax({
                url: `https://viacep.com.br/ws/${cep}/json/`,
                dataType: 'json',
                timeout: 5000,
                success: (data) => {
                    if (!data.erro) {
                        this.cache.$logradouro.val(data.logradouro);
                        this.cache.$bairro.val(data.bairro);
                        this.cache.$cidade.val(data.localidade);
                        this.cache.$estado.val(data.uf);
                        this.cache.$numero.focus();
                        toastr.success('CEP encontrado com sucesso');
                    } else {
                        toastr.error('CEP não encontrado');
                    }
                },
                error: () => {
                    toastr.error('Erro ao buscar CEP');
                },
                complete: () => {
                    $icon.removeClass('bi-arrow-clockwise').addClass('bi-search');
                    this.cache.$buscarCep.prop('disabled', false);
                }
            });
        },

        handleSubmit(e) {
            const role = this.cache.$roleInputs.filter(':checked').val();

            if (!role) {
                e.preventDefault();
                toastr.error('Selecione um perfil de acesso');
                this.cache.$step2.removeClass('active');
                this.cache.$step1.addClass('active');
                this.cache.$progressStep2.removeClass('active');
                this.cache.$progressStep1.removeClass('completed');
                return false;
            }

            if ((role === 'gerencial' || role === 'operacional') && !this.cache.$nomeCompleto.val()) {
                e.preventDefault();
                toastr.error('Nome completo é obrigatório');
                this.cache.$nomeCompleto.focus();
                return false;
            }

            if (role === 'fornecedor') {
                if (!this.cache.$razaoSocial.val()) {
                    e.preventDefault();
                    toastr.error('Razão Social é obrigatória');
                    this.cache.$razaoSocial.focus();
                    return false;
                }

                const cnpj = this.cache.$cnpj.val();
                if (!cnpj || !this.isCNPJValid(cnpj)) {
                    e.preventDefault();
                    toastr.error('CNPJ inválido');
                    this.cache.$cnpj.focus();
                    return false;
                }
            }

            this.cache.$submitBtn.prop('disabled', true).html(
                '<span class="spinner-border spinner-border-sm" style="width: 1rem; height: 1rem; border-width: 2px;"></span> <span>Salvando...</span>'
            );

            return true;
        }
    };

    $(document).ready(() => {
        UserFormManager.init();
    });

    window.UserFormManager = UserFormManager;

})(jQuery);