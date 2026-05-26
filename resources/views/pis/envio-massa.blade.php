{{-- resources/views/pis/envio-massa.blade.php --}}
@extends('layouts.app')

@section('title', 'Reatribuir PIs')

@section('content')
    <div class="container-fluid px-0">
        <div class="page-header">
            <div class="header-card">
                <div class="header-top">
                    <div class="title-wrapper">
                        <div class="icon-badge">
                            <i class="bi bi-arrow-left-right"></i>
                        </div>
                        <div>
                            <h1 class="main-title">Reatribuir PIs</h1>
                            <p class="main-subtitle">Reatribuição em lote de PIs entre operacionais</p>
                        </div>
                    </div>
                    <a href="{{ route('pis.index') }}" class="btn-back">
                        <i class="bi bi-arrow-left"></i>
                        <span>Voltar</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="filters-card">
            <div class="filters-header">
                <i class="bi bi-funnel-fill"></i>
                <span>Filtrar PIs</span>
            </div>
            <div class="filters-body">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label class="filter-label">Cliente</label>
                        <select id="filtroCliente" class="filter-control">
                            <option value="">Todos os Clientes</option>
                            @foreach ($clientes as $cliente)
                                <option value="{{ $cliente->id }}">{{ $cliente->razao_social }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Fornecedor</label>
                        <select id="filtroFornecedor" class="filter-control">
                            <option value="">Todos os Fornecedores</option>
                            @foreach ($fornecedores as $fornecedor)
                                <option value="{{ $fornecedor->id }}">{{ $fornecedor->razao_social }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Operacional Atual</label>
                        <select id="filtroOperacional" class="filter-control">
                            <option value="">Todos os Operacionais</option>
                            @foreach ($operacionais as $op)
                                <option value="{{ $op->id }}">{{ $op->nome_completo }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Status</label>
                        <select id="filtroStatus" class="filter-control">
                            <option value="">Todos os Status</option>
                            @foreach ($statusList as $status)
                                <option value="{{ $status->id }}">{{ $status->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="filters-actions">
                    <div class="search-box-em">
                        <i class="bi bi-search"></i>
                        <input type="text" id="filtroBusca" class="search-input-em" placeholder="Buscar por nº PI, cliente ou fornecedor...">
                    </div>
                    <button type="button" id="btnBuscar" class="btn-buscar">
                        <i class="bi bi-search"></i>
                        <span>Buscar</span>
                    </button>
                    <button type="button" id="btnLimpar" class="btn-limpar">
                        <i class="bi bi-x-circle"></i>
                        <span>Limpar</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="action-card">
            <div class="action-left">
                <div class="selection-counter">
                    <span id="selectionCount">0</span> PI(s) selecionado(s)
                </div>
            </div>
            <div class="action-center">
                <div class="action-field">
                    <label class="action-label">Operacional Destino</label>
                    <select id="opDestino" class="action-select">
                        <option value="">Selecione o operacional destino...</option>
                        @foreach ($operacionais as $op)
                            <option value="{{ $op->id }}">{{ $op->nome_completo }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="action-field action-field-motivo">
                    <label class="action-label">Motivo (opcional)</label>
                    <input type="text" id="motivoReatribuicao" class="action-input" placeholder="Ex: Redistribuição de carga" maxlength="500">
                </div>
            </div>
            <div class="action-right">
                <button type="button" id="btnReatribuir" class="btn-reatribuir" disabled>
                    <i class="bi bi-arrow-left-right"></i>
                    <span>Reatribuir Selecionados</span>
                </button>
            </div>
        </div>

        <div class="table-card">
            <div class="table-wrapper-em">
                <table class="data-table-em" id="pisTable">
                    <thead>
                        <tr>
                            <th class="th-check">
                                <label class="check-container">
                                    <input type="checkbox" id="selectAll">
                                    <span class="checkmark"></span>
                                </label>
                            </th>
                            <th>Nº PI</th>
                            <th>Cliente</th>
                            <th>Fornecedor</th>
                            <th>OP Atual</th>
                            <th>Status</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody id="pisTableBody">
                        <tr class="empty-row">
                            <td colspan="7">
                                <div class="empty-state-em">
                                    <i class="bi bi-funnel"></i>
                                    <p>Use os filtros acima para buscar PIs</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="table-footer" id="tableFooter" style="display: none;">
                <span class="results-info" id="resultsInfo"></span>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        let selectedPis = new Set();
        let allPisData = [];

        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('btnBuscar').addEventListener('click', buscarPis);
            document.getElementById('btnLimpar').addEventListener('click', limparFiltros);
            document.getElementById('btnReatribuir').addEventListener('click', confirmarReatribuicao);
            document.getElementById('selectAll').addEventListener('change', toggleSelectAll);
            document.getElementById('opDestino').addEventListener('change', atualizarBotao);

            document.getElementById('filtroBusca').addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    buscarPis();
                }
            });

            ['filtroCliente', 'filtroFornecedor', 'filtroOperacional', 'filtroStatus'].forEach(function (id) {
                document.getElementById(id).addEventListener('change', buscarPis);
            });
        });

        function buscarPis() {
            const params = new URLSearchParams();
            const cliente = document.getElementById('filtroCliente').value;
            const fornecedor = document.getElementById('filtroFornecedor').value;
            const operacional = document.getElementById('filtroOperacional').value;
            const status = document.getElementById('filtroStatus').value;
            const busca = document.getElementById('filtroBusca').value.trim();

            if (cliente) params.append('cliente_id', cliente);
            if (fornecedor) params.append('fornecedor_id', fornecedor);
            if (operacional) params.append('operacional_id', operacional);
            if (status) params.append('status_id', status);
            if (busca) params.append('search', busca);

            const tbody = document.getElementById('pisTableBody');
            tbody.innerHTML = '<tr class="loading-row"><td colspan="7"><div class="loading-state"><i class="bi bi-arrow-clockwise spin"></i><span>Buscando PIs...</span></div></td></tr>';

            fetch('{{ route("pis.envio_massa.buscar") }}?' + params.toString(), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    allPisData = data.pis;
                    renderizarTabela(data.pis);
                    document.getElementById('tableFooter').style.display = 'flex';
                    document.getElementById('resultsInfo').textContent = data.total + ' PI(s) encontrado(s)';
                } else {
                    tbody.innerHTML = '<tr class="empty-row"><td colspan="7"><div class="empty-state-em"><i class="bi bi-exclamation-triangle"></i><p>' + (data.message || 'Erro ao buscar') + '</p></div></td></tr>';
                }
            })
            .catch(function () {
                tbody.innerHTML = '<tr class="empty-row"><td colspan="7"><div class="empty-state-em"><i class="bi bi-exclamation-triangle"></i><p>Erro de conexão ao buscar PIs</p></div></td></tr>';
            });
        }

        function renderizarTabela(pis) {
            const tbody = document.getElementById('pisTableBody');

            if (!pis || pis.length === 0) {
                tbody.innerHTML = '<tr class="empty-row"><td colspan="7"><div class="empty-state-em"><i class="bi bi-inbox"></i><p>Nenhum PI encontrado com os filtros selecionados</p></div></td></tr>';
                document.getElementById('selectAll').checked = false;
                return;
            }

            let html = '';
            pis.forEach(function (pi) {
                const checked = selectedPis.has(pi.id) ? 'checked' : '';
                const selectedClass = selectedPis.has(pi.id) ? 'row-selected' : '';

                html += '<tr class="table-row-em ' + selectedClass + '" data-pi-id="' + pi.id + '">' +
                    '<td class="td-check"><label class="check-container"><input type="checkbox" class="pi-checkbox" value="' + pi.id + '" ' + checked + ' onchange="togglePi(this)"><span class="checkmark"></span></label></td>' +
                    '<td class="td-pi-number"><span class="pi-num">' + pi.numero_pi + '</span></td>' +
                    '<td class="td-entity">' + (pi.cliente || '<span class="text-muted-em">—</span>') + '</td>' +
                    '<td class="td-entity">' + (pi.fornecedor || '<span class="text-muted-em">—</span>') + '</td>' +
                    '<td class="td-op">' + pi.operacional_nome + '</td>' +
                    '<td class="td-status"><span class="status-badge-em" style="background:' + pi.status_cor + '15;color:' + pi.status_cor + ';border:1px solid ' + pi.status_cor + '30">' + pi.status_nome + '</span></td>' +
                    '<td class="td-date">' + pi.data + '</td>' +
                    '</tr>';
            });

            tbody.innerHTML = html;
            syncSelectAll();
        }

        function togglePi(checkbox) {
            const piId = parseInt(checkbox.value);
            const row = checkbox.closest('tr');

            if (checkbox.checked) {
                selectedPis.add(piId);
                row.classList.add('row-selected');
            } else {
                selectedPis.delete(piId);
                row.classList.remove('row-selected');
            }

            syncSelectAll();
            atualizarContador();
            atualizarBotao();
        }

        function toggleSelectAll() {
            const checked = document.getElementById('selectAll').checked;
            const checkboxes = document.querySelectorAll('.pi-checkbox');

            checkboxes.forEach(function (cb) {
                cb.checked = checked;
                const piId = parseInt(cb.value);
                const row = cb.closest('tr');

                if (checked) {
                    selectedPis.add(piId);
                    row.classList.add('row-selected');
                } else {
                    selectedPis.delete(piId);
                    row.classList.remove('row-selected');
                }
            });

            atualizarContador();
            atualizarBotao();
        }

        function syncSelectAll() {
            const checkboxes = document.querySelectorAll('.pi-checkbox');
            const allChecked = checkboxes.length > 0 && Array.from(checkboxes).every(function (cb) { return cb.checked; });
            document.getElementById('selectAll').checked = allChecked;
        }

        function atualizarContador() {
            document.getElementById('selectionCount').textContent = selectedPis.size;
        }

        function atualizarBotao() {
            const btn = document.getElementById('btnReatribuir');
            const opDestino = document.getElementById('opDestino').value;
            btn.disabled = selectedPis.size === 0 || !opDestino;
        }

        function limparFiltros() {
            document.getElementById('filtroCliente').value = '';
            document.getElementById('filtroFornecedor').value = '';
            document.getElementById('filtroOperacional').value = '';
            document.getElementById('filtroStatus').value = '';
            document.getElementById('filtroBusca').value = '';
            selectedPis.clear();
            atualizarContador();
            atualizarBotao();
            document.getElementById('selectAll').checked = false;

            const tbody = document.getElementById('pisTableBody');
            tbody.innerHTML = '<tr class="empty-row"><td colspan="7"><div class="empty-state-em"><i class="bi bi-funnel"></i><p>Use os filtros acima para buscar PIs</p></div></td></tr>';
            document.getElementById('tableFooter').style.display = 'none';
        }

        function confirmarReatribuicao() {
            if (selectedPis.size === 0) return;

            const opDestinoSelect = document.getElementById('opDestino');
            const opDestinoNome = opDestinoSelect.options[opDestinoSelect.selectedIndex].text;
            const opDestinoId = opDestinoSelect.value;

            if (!opDestinoId) {
                toastr.warning('Selecione o operacional destino.');
                return;
            }

            const count = selectedPis.size;
            const piLabel = count === 1 ? 'PI será reatribuído' : 'PIs serão reatribuídos';

            Swal.fire({
                title: '',
                html: '<div class="swal-em-container">' +
                    '<div class="swal-em-icon"><i class="bi bi-arrow-left-right"></i></div>' +
                    '<h2 class="swal-em-title">Confirmar Reatribuição</h2>' +
                    '<div class="swal-em-body">' +
                    '<div class="swal-em-info">' +
                    '<div class="swal-em-row"><span class="swal-em-label">Quantidade:</span><span class="swal-em-value">' + count + ' ' + piLabel + '</span></div>' +
                    '<div class="swal-em-row"><span class="swal-em-label">Destino:</span><span class="swal-em-value">' + opDestinoNome + '</span></div>' +
                    '</div>' +
                    '<div class="swal-em-warning"><i class="bi bi-exclamation-triangle-fill"></i><span>O operacional atual perderá o acesso a esses PIs.</span></div>' +
                    '</div></div>',
                showCancelButton: true,
                confirmButtonText: '<i class="bi bi-check-circle-fill"></i> Confirmar',
                cancelButtonText: '<i class="bi bi-x-circle-fill"></i> Cancelar',
                customClass: {
                    popup: 'swal-em-popup',
                    confirmButton: 'swal-em-btn-confirm',
                    cancelButton: 'swal-em-btn-cancel',
                    actions: 'swal-em-actions'
                },
                buttonsStyling: false,
                reverseButtons: true
            }).then(function (result) {
                if (result.isConfirmed) {
                    executarReatribuicao(opDestinoId);
                }
            });
        }

        function executarReatribuicao(opDestinoId) {
            const btn = document.getElementById('btnReatribuir');
            btn.disabled = true;
            btn.innerHTML = '<i class="bi bi-arrow-clockwise spin"></i><span>Processando...</span>';

            const motivo = document.getElementById('motivoReatribuicao').value.trim();

            fetch('{{ route("pis.envio_massa.processar") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    pi_ids: Array.from(selectedPis),
                    operacional_destino_id: parseInt(opDestinoId),
                    motivo: motivo || null
                })
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    toastr.success(data.message);

                    if (data.erros && data.erros.length > 0) {
                        data.erros.forEach(function (err) {
                            toastr.warning(err);
                        });
                    }

                    selectedPis.clear();
                    atualizarContador();
                    document.getElementById('motivoReatribuicao').value = '';
                    buscarPis();
                } else {
                    toastr.error(data.message || 'Erro ao processar reatribuição.');
                }
            })
            .catch(function () {
                toastr.error('Erro de conexão ao processar reatribuição.');
            })
            .finally(function () {
                btn.innerHTML = '<i class="bi bi-arrow-left-right"></i><span>Reatribuir Selecionados</span>';
                atualizarBotao();
            });
        }

        @if (session('success'))
            toastr.success('{{ session('success') }}');
        @elseif (session('error'))
            toastr.error('{{ session('error') }}');
        @endif
    </script>
@endpush

@push('styles')
    <style>
        @keyframes emFadeInDown {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes emFadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .spin {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .page-header {
            margin-bottom: 1.5rem;
            animation: emFadeInDown 0.6s ease;
        }

        .header-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 2rem;
        }

        .title-wrapper {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .icon-badge {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            color: white;
            box-shadow: 0 6px 20px rgba(0, 94, 130, 0.25);
        }

        .main-title {
            font-size: 1.875rem;
            font-weight: 700;
            color: var(--text-color);
            margin: 0;
        }

        .main-subtitle {
            font-size: 0.95rem;
            color: var(--text-muted);
            margin: 0.25rem 0 0 0;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 0.625rem;
            padding: 0.75rem 1.5rem;
            background: var(--bg-body);
            color: var(--text-color);
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.95rem;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-back:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
            transform: translateY(-2px);
        }

        .filters-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-sm);
            animation: emFadeInUp 0.6s ease 0.1s backwards;
        }

        .filters-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            font-weight: 700;
            font-size: 1rem;
            color: var(--text-color);
        }

        .filters-header i {
            color: var(--primary-color);
            font-size: 1.25rem;
        }

        .filters-body {
            padding: 1.5rem;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.375rem;
        }

        .filter-label {
            font-size: 0.8125rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .filter-control {
            padding: 0.625rem 0.875rem;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.9375rem;
            background: var(--bg-body);
            color: var(--text-color);
            transition: border-color 0.2s ease;
        }

        .filter-control:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 94, 130, 0.1);
        }

        .filters-actions {
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }

        .search-box-em {
            position: relative;
            flex: 1;
            max-width: 400px;
        }

        .search-box-em i {
            position: absolute;
            left: 0.875rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            pointer-events: none;
        }

        .search-input-em {
            width: 100%;
            padding: 0.625rem 1rem 0.625rem 2.5rem;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.9375rem;
            background: var(--bg-body);
            color: var(--text-color);
            transition: border-color 0.2s ease;
        }

        .search-input-em:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 94, 130, 0.1);
        }

        .btn-buscar {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9375rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-buscar:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 94, 130, 0.3);
        }

        .btn-limpar {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            background: transparent;
            color: var(--text-muted);
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9375rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-limpar:hover {
            border-color: #ef4444;
            color: #ef4444;
        }

        .action-card {
            background: var(--bg-card);
            border: 2px solid var(--primary-color);
            border-radius: 12px;
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: flex-end;
            gap: 1.5rem;
            box-shadow: 0 4px 16px rgba(0, 94, 130, 0.1);
            animation: emFadeInUp 0.6s ease 0.2s backwards;
        }

        .action-left {
            flex-shrink: 0;
        }

        .selection-counter {
            font-size: 1rem;
            font-weight: 700;
            color: var(--primary-color);
            background: rgba(0, 94, 130, 0.08);
            padding: 0.625rem 1.25rem;
            border-radius: 8px;
            white-space: nowrap;
        }

        .selection-counter span {
            font-size: 1.375rem;
            font-weight: 800;
        }

        .action-center {
            display: flex;
            gap: 1rem;
            flex: 1;
            align-items: flex-end;
        }

        .action-field {
            display: flex;
            flex-direction: column;
            gap: 0.375rem;
            flex: 1;
            min-width: 200px;
        }

        .action-field-motivo {
            max-width: 300px;
        }

        .action-label {
            font-size: 0.8125rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .action-select,
        .action-input {
            padding: 0.625rem 0.875rem;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.9375rem;
            background: var(--bg-body);
            color: var(--text-color);
            transition: border-color 0.2s ease;
        }

        .action-select:focus,
        .action-input:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 94, 130, 0.1);
        }

        .action-right {
            flex-shrink: 0;
        }

        .btn-reatribuir {
            display: inline-flex;
            align-items: center;
            gap: 0.625rem;
            padding: 0.75rem 1.75rem;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.9375rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3);
            white-space: nowrap;
        }

        .btn-reatribuir:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }

        .btn-reatribuir:disabled {
            background: linear-gradient(135deg, #9ca3af 0%, #6b7280 100%);
            cursor: not-allowed;
            box-shadow: none;
            opacity: 0.7;
        }

        .table-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            animation: emFadeInUp 0.6s ease 0.3s backwards;
        }

        .table-wrapper-em {
            overflow-x: auto;
        }

        .data-table-em {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .data-table-em thead {
            background: rgba(0, 94, 130, 0.03);
        }

        .data-table-em th {
            padding: 0.875rem 1rem;
            font-weight: 600;
            font-size: 0.8125rem;
            color: var(--text-muted);
            text-align: left;
            border-bottom: 2px solid var(--border-color);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
            background: rgba(0, 94, 130, 0.03);
        }

        .th-check {
            width: 50px;
            text-align: center;
        }

        .data-table-em td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
            font-size: 0.9375rem;
            color: var(--text-color);
        }

        .table-row-em {
            transition: background 0.15s ease;
            cursor: pointer;
        }

        .table-row-em:hover {
            background: rgba(0, 94, 130, 0.03);
        }

        .table-row-em.row-selected {
            background: rgba(16, 185, 129, 0.06);
        }

        .table-row-em.row-selected:hover {
            background: rgba(16, 185, 129, 0.1);
        }

        .td-check {
            width: 50px;
            text-align: center;
        }

        .td-pi-number {
            white-space: nowrap;
        }

        .pi-num {
            font-weight: 700;
            color: var(--text-color);
        }

        .td-entity {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .td-op {
            font-weight: 600;
            white-space: nowrap;
        }

        .text-muted-em {
            color: var(--text-muted);
            font-style: italic;
        }

        .status-badge-em {
            display: inline-flex;
            align-items: center;
            padding: 0.3125rem 0.75rem;
            border-radius: 6px;
            font-size: 0.8125rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .td-date {
            white-space: nowrap;
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        .check-container {
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            margin: 0;
        }

        .check-container input {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: var(--primary-color);
        }

        .check-container .checkmark {
            display: none;
        }

        .empty-state-em {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-muted);
        }

        .empty-state-em i {
            font-size: 3rem;
            opacity: 0.4;
            display: block;
            margin-bottom: 1rem;
        }

        .empty-state-em p {
            font-size: 1rem;
            margin: 0;
            font-weight: 600;
        }

        .loading-state {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            padding: 3rem;
            color: var(--primary-color);
            font-weight: 600;
        }

        .loading-state i {
            font-size: 1.5rem;
        }

        .table-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.875rem 1.25rem;
            border-top: 1px solid var(--border-color);
            background: rgba(0, 94, 130, 0.02);
        }

        .results-info {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-muted);
        }

        .swal-em-popup {
            border-radius: 20px !important;
            padding: 2rem !important;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2) !important;
        }

        .swal-em-container {
            text-align: center;
        }

        .swal-em-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 1rem;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            box-shadow: 0 8px 24px rgba(0, 94, 130, 0.3);
        }

        .swal-em-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text-color);
            margin: 0 0 1.25rem 0;
        }

        .swal-em-body {
            text-align: left;
        }

        .swal-em-info {
            background: rgba(0, 94, 130, 0.05);
            border: 2px solid var(--primary-color);
            border-radius: 12px;
            padding: 1rem 1.25rem;
            margin-bottom: 1rem;
        }

        .swal-em-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
        }

        .swal-em-row:not(:last-child) {
            border-bottom: 1px solid rgba(0, 94, 130, 0.15);
        }

        .swal-em-label {
            font-weight: 700;
            color: var(--text-muted);
        }

        .swal-em-value {
            font-weight: 700;
            color: var(--text-color);
        }

        .swal-em-warning {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.875rem 1rem;
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.3);
            border-radius: 8px;
            color: #d97706;
            font-weight: 600;
            font-size: 0.9375rem;
        }

        .swal-em-warning i {
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .swal-em-actions {
            margin-top: 1.5rem !important;
            gap: 1rem !important;
        }

        .swal-em-btn-confirm,
        .swal-em-btn-cancel {
            margin: 0 !important;
            padding: 0.875rem 2rem !important;
            border-radius: 10px !important;
            font-weight: 700 !important;
            font-size: 0.9375rem !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.5rem !important;
            transition: all 0.3s ease !important;
            border: none !important;
            min-width: 140px !important;
            justify-content: center !important;
            cursor: pointer !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
        }

        .swal-em-btn-confirm {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
            color: white !important;
        }

        .swal-em-btn-confirm:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 20px rgba(5, 150, 105, 0.4) !important;
        }

        .swal-em-btn-cancel {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%) !important;
            color: white !important;
        }

        .swal-em-btn-cancel:hover {
            background: linear-gradient(135deg, #4b5563 0%, #374151 100%) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 20px rgba(107, 114, 128, 0.3) !important;
        }

        [data-theme="dark"] .swal-em-popup {
            background: #1f2937 !important;
        }

        [data-theme="dark"] .swal-em-title {
            color: #f1f5f9;
        }

        [data-theme="dark"] .swal-em-info {
            background: rgba(14, 165, 233, 0.08);
        }

        [data-theme="dark"] .swal-em-label {
            color: #94a3b8;
        }

        [data-theme="dark"] .swal-em-value {
            color: #f1f5f9;
        }

        [data-theme="dark"] .swal-em-warning {
            background: rgba(245, 158, 11, 0.15);
        }

        [data-theme="dark"] .data-table-em thead {
            background: rgba(56, 189, 248, 0.08);
        }

        [data-theme="dark"] .data-table-em th {
            background: rgba(56, 189, 248, 0.08);
        }

        [data-theme="dark"] .table-row-em:hover {
            background: rgba(56, 189, 248, 0.04);
        }

        [data-theme="dark"] .table-row-em.row-selected {
            background: rgba(16, 185, 129, 0.1);
        }

        [data-theme="dark"] .table-footer {
            background: rgba(56, 189, 248, 0.04);
        }

        [data-theme="dark"] .filters-card,
        [data-theme="dark"] .table-card {
            background: var(--bg-card);
        }

        @media (max-width: 1024px) {
            .header-top {
                flex-direction: column;
                gap: 1.5rem;
                align-items: stretch;
            }

            .btn-back {
                align-self: flex-start;
            }

            .action-card {
                flex-direction: column;
                align-items: stretch;
                gap: 1rem;
            }

            .action-center {
                flex-direction: column;
            }

            .action-field-motivo {
                max-width: 100%;
            }
        }

        @media (max-width: 768px) {
            .page-header {
                margin-bottom: 1rem;
            }

            .header-top {
                padding: 1.5rem;
            }

            .main-title {
                font-size: 1.5rem;
            }

            .icon-badge {
                width: 50px;
                height: 50px;
                font-size: 1.5rem;
            }

            .filters-grid {
                grid-template-columns: 1fr;
            }

            .filters-actions {
                flex-direction: column;
            }

            .search-box-em {
                max-width: 100%;
            }

            .btn-buscar,
            .btn-limpar {
                width: 100%;
                justify-content: center;
            }

            .action-card {
                padding: 1rem;
            }

            .action-center {
                flex-direction: column;
                gap: 0.75rem;
            }

            .action-field {
                min-width: auto;
            }

            .btn-reatribuir {
                width: 100%;
                justify-content: center;
            }

            .data-table-em th:nth-child(4),
            .data-table-em td:nth-child(4),
            .data-table-em th:nth-child(7),
            .data-table-em td:nth-child(7) {
                display: none;
            }

            .swal-em-actions {
                flex-direction: column !important;
                width: 100% !important;
            }

            .swal-em-btn-confirm,
            .swal-em-btn-cancel {
                width: 100% !important;
                min-width: 100% !important;
            }
        }

        @media (max-width: 425px) {
            .container-fluid {
                padding-left: 0.75rem;
                padding-right: 0.75rem;
            }

            .header-top {
                padding: 1.25rem;
            }

            .main-title {
                font-size: 1.375rem;
            }

            .icon-badge {
                width: 44px;
                height: 44px;
                font-size: 1.375rem;
            }

            .filters-body {
                padding: 1rem;
            }

            .filter-control,
            .search-input-em,
            .action-select,
            .action-input {
                font-size: 0.875rem;
                padding: 0.5625rem 0.75rem;
            }

            .search-input-em {
                padding-left: 2.25rem;
            }

            .data-table-em th,
            .data-table-em td {
                padding: 0.625rem 0.75rem;
                font-size: 0.875rem;
            }

            .data-table-em th:nth-child(3),
            .data-table-em td:nth-child(3) {
                display: none;
            }

            .selection-counter {
                font-size: 0.875rem;
                padding: 0.5rem 1rem;
            }

            .selection-counter span {
                font-size: 1.125rem;
            }
        }

        @media (max-width: 375px) {
            .header-top {
                padding: 1rem;
            }

            .title-wrapper {
                gap: 0.875rem;
            }

            .main-title {
                font-size: 1.25rem;
            }

            .main-subtitle {
                font-size: 0.8125rem;
            }

            .icon-badge {
                width: 40px;
                height: 40px;
                font-size: 1.25rem;
            }

            .data-table-em th:nth-child(5),
            .data-table-em td:nth-child(5) {
                display: none;
            }
        }
    </style>
@endpush