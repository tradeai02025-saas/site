window.escapeHtml = function(text) {
    if (text === null || text === undefined) return '';
    var map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
    return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
};

$.ajaxSetup({
    timeout: 30000,
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

$(function () {
    'use strict';

    const isToastPersistent = window.flashMessagePersistent || false;

    toastr.options = {
        closeButton: true,
        newestOnTop: true,
        progressBar: !isToastPersistent,
        positionClass: 'toast-top-right',
        preventDuplicates: true,
        timeOut: isToastPersistent ? 0 : 5000,
        extendedTimeOut: isToastPersistent ? 0 : 1000,
        showEasing: 'swing',
        hideEasing: 'linear',
        showMethod: 'slideDown',
        hideMethod: 'slideUp',
        showDuration: 400,
        hideDuration: 400,
        tapToDismiss: !isToastPersistent,
        closeHtml: '<button type="button">&times;</button>',
        escapeHtml: false
    };

    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');
    const navbarLogo = document.getElementById('navbarLogo');
    const menuToggle = document.getElementById('menuToggle');
    const mobileSubmenu = document.getElementById('mobileSubmenu');

    function initTheme() {
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
        updateThemeIcon(savedTheme);
        updateLogo(savedTheme);
    }

    function updateThemeIcon(theme) {
        if (themeIcon) {
            themeIcon.className = theme === 'dark' ?
                'bi bi-sun-fill' :
                'bi bi-moon-stars-fill';
        }
    }

    function updateLogo(theme) {
        if (navbarLogo) {
            const logoLight = navbarLogo.getAttribute('data-logo-light');
            const logoDark = navbarLogo.getAttribute('data-logo-dark');
            if (logoLight && logoDark) {
                navbarLogo.src = theme === 'dark' ? logoDark : logoLight;
            }
        }
    }

    if (themeToggle) {
        themeToggle.addEventListener('click', function () {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeIcon(newTheme);
            updateLogo(newTheme);
            toastr.info('Tema ' + (newTheme === 'dark' ? 'escuro' : 'claro') + ' ativado!', 'Sucesso');
        });
    }

    initTheme();

    if (menuToggle && mobileSubmenu) {
        menuToggle.addEventListener('click', function (e) {
            e.preventDefault();
            mobileSubmenu.classList.toggle('active');
            menuToggle.classList.toggle('active');
        });

        const submenuLinks = mobileSubmenu.querySelectorAll('.mobile-submenu-link');
        submenuLinks.forEach(link => {
            link.addEventListener('click', function () {
                mobileSubmenu.classList.remove('active');
                menuToggle.classList.remove('active');
            });
        });

        document.addEventListener('click', function (e) {
            if (!mobileSubmenu.contains(e.target) && !menuToggle.contains(e.target)) {
                mobileSubmenu.classList.remove('active');
                menuToggle.classList.remove('active');
            }
        });
    }

    if (window.flashMessages) {
        if (window.flashMessages.success) {
            toastr.success(window.flashMessages.success, 'Sucesso!');
        }
        if (window.flashMessages.error) {
            toastr.error(window.flashMessages.error, 'Erro!');
        }
        if (window.flashMessages.info) {
            toastr.info(window.flashMessages.info, 'Informação');
        }
        if (window.flashMessages.warning) {
            toastr.warning(window.flashMessages.warning, 'Atenção!');
        }
    }

    var cacheIcon = document.getElementById('cacheIcon');
    var csrfToken = document.querySelector('meta[name="csrf-token"]');

    function executarCache(url, acao) {
        if (!csrfToken) return;
        if (cacheIcon) {
            cacheIcon.className = 'bi bi-arrow-repeat spin-animation';
        }
        $.ajax({
            url: url,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken.getAttribute('content') },
            success: function (res) {
                if (res.success) {
                    toastr.success(res.mensagem, 'Cache');
                } else {
                    toastr.error(res.mensagem || 'Erro desconhecido.', 'Cache');
                }
            },
            error: function (xhr) {
                var msg = xhr.responseJSON ? xhr.responseJSON.mensagem : 'Erro ao ' + acao + '.';
                toastr.error(msg, 'Cache');
            },
            complete: function () {
                if (cacheIcon) {
                    cacheIcon.className = 'bi bi-arrow-repeat';
                }
            }
        });
    }

    $('#btnLimparCaches').on('click', function () {
        if (window.AppRoutes && window.AppRoutes.limparCaches) {
            executarCache(window.AppRoutes.limparCaches, 'limpar caches');
        }
    });

    $('#btnReconstruirCaches').on('click', function () {
        if (window.AppRoutes && window.AppRoutes.reconstruirCaches) {
            executarCache(window.AppRoutes.reconstruirCaches, 'reconstruir caches');
        }
    });
});

window.addEventListener('pageshow', function(event) {
    if (event.persisted) {
        var spinnerEmBotao = document.querySelector('button .spinner-border');
        var overlayAtivo = document.querySelector('.loading-overlay.active');
        if (spinnerEmBotao || overlayAtivo) {
            window.location.reload();
        }
    }
});