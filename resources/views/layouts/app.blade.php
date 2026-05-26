{{-- resources/views/layouts/app.blade.php --}}

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - {{ config('app.name') }}</title>

    @php $v = time(); @endphp

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css?v={{ $v }}"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css?v={{ $v }}"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/toastr@2.1.4/build/toastr.min.css?v={{ $v }}" rel="stylesheet">

    <link rel="icon" href="{{ asset('img/favicons/favicon.svg') }}" type="image/svg+xml">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('img/favicons/favicon-16.png') }}"
        media="(prefers-color-scheme: light)" />
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('img/favicons/favicon-16.png') }}"
        media="(prefers-color-scheme: light)">
    <link rel="icon" sizes="32x32" href="{{ asset('img/favicons/favicon-32.svg') }}"
        media="(prefers-color-scheme: light)">
    <link rel="icon" sizes="48x48" href="{{ asset('img/favicons/favicon-48.png') }}"
        media="(prefers-color-scheme: light)">
    <link rel="icon" sizes="192x192" href="{{ asset('img/favicons/favicon-192.png') }}"
        media="(prefers-color-scheme: light)">
    <link rel="icon" sizes="512x512" href="{{ asset('img/favicons/favicon-512.png') }}"
        media="(prefers-color-scheme: light)">

    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('img/favicons/favicon-16-dark.png') }}"
        media="(prefers-color-scheme: dark)" />
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('img/favicons/favicon-16-dark.png') }}"
        media="(prefers-color-scheme: dark)">
    <link rel="icon" sizes="32x32" href="{{ asset('img/favicons/favicon-32-dark.png') }}"
        media="(prefers-color-scheme: dark)">
    <link rel="icon" sizes="48x48" href="{{ asset('img/favicons/favicon-48-dark.png') }}"
        media="(prefers-color-scheme: dark)">
    <link rel="icon" sizes="192x192" href="{{ asset('img/favicons/favicon-192-dark.png') }}"
        media="(prefers-color-scheme: dark)">
    <link rel="icon" sizes="512x512" href="{{ asset('img/favicons/favicon-512-dark.png') }}"
        media="(prefers-color-scheme: dark)">

    <link href="{{ asset('css/base.css') }}?v={{ $v }}" rel="stylesheet">
    <link href="{{ asset('css/components.css') }}?v={{ $v }}" rel="stylesheet">
    <link href="{{ asset('css/variables.css') }}?v={{ $v }}" rel="stylesheet">

    @stack('styles')
</head>

<body>
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    @include('partials.navbar')

    <div class="container-fluid">
        <div class="row">
            @include('partials.desktop-menu')

            <main class="col-lg-9 col-xl-10 content-wrapper">
                @yield('content')
            </main>
        </div>
    </div>

    @include('partials.mobile-menu')

    <script src="https://code.jquery.com/jquery-3.7.1.min.js?v={{ $v }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js?v={{ $v }}">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/toastr@2.1.4/toastr.min.js?v={{ $v }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11?v={{ $v }}"></script>


    <script>
        window.flashMessages = {
            success: @json(session('success')),
            error: @json(session('error')),
            info: @json(session('info')),
            warning: @json(session('warning'))
        };

        @if (session('toast_persistent'))
            window.flashMessagePersistent = true;
        @endif
    </script>

    <script src="{{ asset('js/base.js') }}?v={{ $v }}"></script>

    <script>
        window.AppRoutes = {
            userRole: '{{ auth()->user()->roles->first()->name ?? '' }}',
            unreadCount: '{{ route('chat.unread_count') }}',
            conversasNaoLidas: '{{ route('chat.conversas_nao_lidas') }}',
            conversasComNaoLidas: '{{ route('chat.conversas_com_nao_lidas') }}',
            buscarUsuarios: '{{ route('chat.buscar_usuarios') }}',
            buscarPis: '{{ route('chat.buscar_pis') }}',
            limparMensagensOrfas: '{{ route('chat.limpar_mensagens_orfas') }}',
            limparMsgOrfas: '{{ route('chat.limpar_mensagens_orfas') }}',
            criarConversa: '{{ route('chat.criar_conversa') }}',
            usersIndex: '{{ route('chat.index') }}',
            chatIndex: '{{ route('chat.index') }}',
            show: '{{ route('chat.show', ':id') }}',
            resolverConversa: '{{ route('chat.resolver_conversa', ':id') }}',
            reabrirConversa: '{{ route('chat.reabrir_conversa', ':id') }}',
            excluirConversa: '{{ route('chat.excluir_conversa', ':id') }}',
            @hasrole('admin')
            limparCaches: '{{ route('sistema.limpar_caches') }}',
            reconstruirCaches: '{{ route('sistema.reconstruir_caches') }}',
            @endhasrole
        };
    </script>

    @stack('scripts')
</body>

</html>