{{-- resources/views/partials/navbar.blade.php --}}

<nav class="navbar">
    <div class="container-fluid navbar-container">
        <a class="navbar-brand" href="{{ route('dashboard') }}">
            <img src="{{ asset('img/logo.png') }}" alt="Logo" id="navbarLogo"
                data-logo-light="{{ asset('img/logo.png') }}" data-logo-dark="{{ asset('img/logo-dark.png') }}">
        </a>

        <div class="navbar-actions">
            @hasrole('admin')
                <div class="dropdown">
                    <button type="button" class="theme-toggle-btn" id="cacheBtn" data-bs-toggle="dropdown" aria-expanded="false" title="Gerenciar Caches">
                        <i class="bi bi-arrow-repeat" id="cacheIcon"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end cache-dropdown">
                        <li>
                            <button type="button" class="dropdown-item" id="btnLimparCaches">
                                <i class="bi bi-trash3"></i>
                                <span>Limpar Caches</span>
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item" id="btnReconstruirCaches">
                                <i class="bi bi-arrow-clockwise"></i>
                                <span>Reconstruir Caches</span>
                            </button>
                        </li>
                    </ul>
                </div>
            @endhasrole

            <button type="button" class="theme-toggle-btn" id="themeToggle" title="Alternar tema">
                <i class="bi bi-moon-stars-fill" id="themeIcon"></i>
            </button>

            <div class="dropdown">
                <a href="#" class="user-dropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="user-avatar">
                        {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 2, 'UTF-8')) }}
                    </div>
                    <div class="user-info">
                        <span class="user-name">{{ auth()->user()->name }}</span>
                        <span class="user-role">{{ auth()->user()->roles->first()->name ?? 'Usuário' }}</span>
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="{{ route('perfil.edit') }}">
                            <i class="bi bi-person-circle"></i>
                            <span>Meu Perfil</span>
                        </a>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}" id="logoutForm">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="bi bi-box-arrow-right"></i>
                                <span>Sair</span>
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>