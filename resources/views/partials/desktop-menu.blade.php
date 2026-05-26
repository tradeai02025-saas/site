{{-- resources/views/partials/desktop-menu.blade.php --}}

<nav class="col-lg-3 col-xl-2 sidebar d-none d-lg-block">
    <div class="sidebar-section">
        <div class="sidebar-header">
            <h6>Principal</h6>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                    <i class="bi bi-house-door-fill nav-link-icon"></i>
                    <span class="nav-link-text">Início</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('chat.*') ? 'active' : '' }}" href="{{ route('chat.index') }}"
                    style="position: relative;">
                    <i class="bi bi-chat-dots-fill nav-link-icon"></i>
                    <span class="nav-link-text">Mensagens</span>
                    @if (isset($unreadMessagesCount) && $unreadMessagesCount > 0)
                        <span id="desktop-badge" class="badge-notification">
                            {{ $unreadMessagesCount > 99 ? '99+' : $unreadMessagesCount }}
                        </span>
                    @endif
                </a>
            </li>
        </ul>
    </div>

    <div class="sidebar-section">
        <div class="sidebar-header">
            <h6>Pedidos de Inserção</h6>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('pis.*') && !request()->routeIs('pis.envio_massa*') ? 'active' : '' }}" href="{{ route('pis.index') }}">
                    <i class="bi bi-file-earmark-plus-fill nav-link-icon"></i>
                    <span class="nav-link-text">
                        @hasrole('operacional')
                            PIs Atribuídos
                        @elseif(auth()->user()->hasRole('representante'))
                            PIs dos Fornecedores
                        @else
                            Gerenciar PIs
                        @endhasrole
                    </span>
                </a>
            </li>
            @hasanyrole('admin|gerencial')
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('pis.envio_massa*') ? 'active' : '' }}" href="{{ route('pis.envio_massa') }}">
                        <i class="bi bi-arrow-left-right nav-link-icon"></i>
                        <span class="nav-link-text">Reatribuir PIs</span>
                    </a>
                </li>
            @endhasanyrole
        </ul>
    </div>

    @hasanyrole('admin|gerencial|operacional')
        <div class="sidebar-section">
            <div class="sidebar-header">
                <h6>Cadastros</h6>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('clientes.*') ? 'active' : '' }}"
                        href="{{ route('clientes.index') }}">
                        <i class="bi bi-building-fill nav-link-icon"></i>
                        <span class="nav-link-text">Clientes</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('fornecedores.*') && !request()->routeIs('fornecedor.*') ? 'active' : '' }}"
                        href="{{ route('fornecedores.index') }}">
                        <i class="bi bi-collection-play"></i>
                        <span class="nav-link-text">Fornecedores</span>
                    </a>
                </li>
                @hasanyrole('admin|gerencial')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('representantes.*') ? 'active' : '' }}"
                            href="{{ route('representantes.index') }}" style="position: relative;">
                            <i class="bi bi-person-badge-fill"></i>
                            <span class="nav-link-text">Representantes</span>
                            @if (isset($pendingVinculosCount) && $pendingVinculosCount > 0)
                                <span class="badge-notification badge-warning-notification">
                                    {{ $pendingVinculosCount > 99 ? '99+' : $pendingVinculosCount }}
                                </span>
                            @endif
                        </a>
                    </li>
                @endhasanyrole
            </ul>
        </div>

    @endhasanyrole

    @hasanyrole('fornecedor|representante')
        <div class="sidebar-section">
            <div class="sidebar-header">
                <h6>Documentos</h6>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('fornecedor.*') ? 'active' : '' }}"
                        href="{{ route('fornecedor.docs_list.index') }}">
                        <i class="bi bi-list-ul nav-link-icon"></i>
                        <span class="nav-link-text">Meus Documentos</span>
                    </a>
                </li>
            </ul>
        </div>
    @endhasanyrole

    @hasanyrole('admin|gerencial')
        <div class="sidebar-section">
            <div class="sidebar-header">
                <h6>Configurações</h6>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('tipo_documentos.*') ? 'active' : '' }}"
                        href="{{ route('tipo_documentos.index') }}">
                        <i class="bi bi-file-earmark-text-fill nav-link-icon"></i>
                        <span class="nav-link-text">Tipos de Documentos</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}"
                        href="{{ route('users.index') }}">
                        <i class="bi bi-people-fill nav-link-icon"></i>
                        <span class="nav-link-text">Usuários</span>
                    </a>
                </li>
                @hasrole('admin')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('status.*') ? 'active' : '' }}"
                            href="{{ route('status.index') }}">
                            <i class="bi bi-flag-fill nav-link-icon"></i>
                            <span class="nav-link-text">Status</span>
                        </a>
                    </li>
                @endhasrole
            </ul>
        </div>
    @endhasanyrole
</nav>