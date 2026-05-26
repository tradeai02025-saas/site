{{-- resources/views/partials/mobile-menu.blade.php --}}

<nav class="bottom-nav d-lg-none">
    <div class="bottom-nav-container">
        <a href="{{ route('dashboard') }}" class="bottom-nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="bi bi-house-door-fill bottom-nav-icon"></i>
            <span class="bottom-nav-label">Início</span>
        </a>

        <a href="#"
            class="bottom-nav-item {{ request()->routeIs('users.*', 'status.*', 'pis.*', 'clientes.*', 'fornecedores.*', 'tipo_documentos.*', 'documentos.*', 'fornecedor.*') && !request()->routeIs('perfil.*') ? 'active' : '' }}"
            id="menuToggle">
            <i class="bi bi-grid-fill bottom-nav-icon"></i>
            <span class="bottom-nav-label">Menu</span>
        </a>

        <a href="{{ route('chat.index') }}" class="bottom-nav-item {{ request()->routeIs('chat.*') ? 'active' : '' }}"
            style="position: relative;">
            <i class="bi bi-chat-dots-fill bottom-nav-icon"></i>
            <span class="bottom-nav-label">
                Mensagens
                @if (isset($unreadMessagesCount) && $unreadMessagesCount > 0)
                    <span id="mobile-badge" class="badge-notification">
                        {{ $unreadMessagesCount > 99 ? '99+' : $unreadMessagesCount }}
                    </span>
                @endif
            </span>
        </a>

        <a href="{{ route('perfil.edit') }}"
            class="bottom-nav-item {{ request()->routeIs('perfil.*') ? 'active' : '' }}">
            <i class="bi bi-person-fill bottom-nav-icon"></i>
            <span class="bottom-nav-label">Perfil</span>
        </a>
    </div>
</nav>

<div class="mobile-submenu d-md-none" id="mobileSubmenu">
    <div class="mobile-submenu-content">

        <div class="mobile-submenu-section">
            <div class="mobile-submenu-header">Pedidos de Inserção</div>
            <a href="{{ route('pis.index') }}"
                class="mobile-submenu-link {{ request()->routeIs('pis.*') && !request()->routeIs('pis.envio_massa*') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-plus-fill"></i>
                <span>
                    @hasrole('operacional')
                        PIs Atribuídos
                    @elseif(auth()->user()->hasRole('representante'))
                        PIs dos Fornecedores
                    @else
                        Gerenciar PIs
                    @endhasrole
                </span>
            </a>
            @hasanyrole('admin|gerencial')
                <a href="{{ route('pis.envio_massa') }}"
                    class="mobile-submenu-link {{ request()->routeIs('pis.envio_massa*') ? 'active' : '' }}">
                    <i class="bi bi-arrow-left-right"></i>
                    <span>Reatribuir PIs</span>
                </a>
            @endhasanyrole
        </div>

        @hasanyrole('admin|gerencial|operacional')
            <div class="mobile-submenu-section">
                <div class="mobile-submenu-header">Cadastros</div>
                <a href="{{ route('clientes.index') }}"
                    class="mobile-submenu-link {{ request()->routeIs('clientes.*') ? 'active' : '' }}">
                    <i class="bi bi-building-fill"></i>
                    <span>Clientes</span>
                </a>
                <a href="{{ route('fornecedores.index') }}"
                    class="mobile-submenu-link {{ request()->routeIs('fornecedores.*') && !request()->routeIs('fornecedor.*') ? 'active' : '' }}">
                    <i class="bi bi-collection-play"></i>
                    <span>Fornecedores</span>
                </a>
                @hasanyrole('admin|gerencial')
                    <a href="{{ route('representantes.index') }}"
                        class="mobile-submenu-link {{ request()->routeIs('representantes.*') ? 'active' : '' }}"
                        style="position: relative;">
                        <i class="bi bi-person-badge-fill"></i>
                        <span>Representantes</span>
                        @if (isset($pendingVinculosCount) && $pendingVinculosCount > 0)
                            <span class="badge bg-warning text-dark rounded-pill ms-auto" style="font-size:.7rem;">
                                {{ $pendingVinculosCount > 99 ? '99+' : $pendingVinculosCount }}
                            </span>
                        @endif
                    </a>
                @endhasanyrole
            </div>
        @endhasanyrole

        @hasanyrole('fornecedor|representante')
            <div class="mobile-submenu-section">
                <div class="mobile-submenu-header">Documentos</div>
                <a href="{{ route('fornecedor.docs_list.index') }}"
                    class="mobile-submenu-link {{ request()->routeIs('fornecedor.*') ? 'active' : '' }}">
                    <i class="bi bi-list-ul"></i>
                    <span>Meus Documentos</span>
                </a>
            </div>
        @endhasanyrole

        @hasanyrole('admin|gerencial')
            <div class="mobile-submenu-section">
                <div class="mobile-submenu-header">Configurações</div>
                <a href="{{ route('tipo_documentos.index') }}"
                    class="mobile-submenu-link {{ request()->routeIs('tipo_documentos.*') ? 'active' : '' }}">
                    <i class="bi bi-file-earmark-text-fill"></i>
                    <span>Tipos de Documentos</span>
                </a>
                <a href="{{ route('users.index') }}"
                    class="mobile-submenu-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                    <i class="bi bi-people-fill"></i>
                    <span>Usuários</span>
                </a>
                @hasrole('admin')
                    <a href="{{ route('status.index') }}"
                        class="mobile-submenu-link {{ request()->routeIs('status.*') ? 'active' : '' }}">
                        <i class="bi bi-flag-fill"></i>
                        <span>Status</span>
                    </a>
                @endhasrole
            </div>
        @endhasanyrole

    </div>
</div>

<style>
    #menuToggle:hover {
        color: white;
    }
</style>