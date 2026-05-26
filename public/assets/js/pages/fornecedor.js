$(document).ready(function () {

    function getCurrentFilters() {
        const params = new URLSearchParams(window.location.search);
        return {
            search: params.get('search') || '',
            status: params.get('status') || ''
        };
    }

    function buildUrl(newFilters) {
        const filters = { ...getCurrentFilters(), ...newFilters };
        const params = new URLSearchParams();

        if (filters.search) params.append('search', filters.search);
        if (filters.status) params.append('status', filters.status);

        return `${window.AppRoutes.fornecedoresIndex}${params.toString() ? '?' + params.toString() : ''}`;
    }

    $('.search-input').on('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            $('#filterForm').submit();
        }
    });

    $('.search-clear').on('click', function () {
        $('.search-input').val('');
        $('#filterForm').submit();
    });

    $('.remove-filter').on('click', function () {
        const filter = $(this).data('filter');
        const newFilters = getCurrentFilters();
        newFilters[filter] = '';
        window.location.href = buildUrl(newFilters);
    });

    $('.stat-clickable').on('click', function () {
        const filter = $(this).data('filter');
        const newFilters = getCurrentFilters();

        $('.stat-clickable').removeClass('active');
        $(this).addClass('active');

        $(this).css('transform', 'scale(0.95)');

        setTimeout(() => {
            $(this).css('transform', '');

            if (filter === 'all') {
                newFilters.status = '';
            } else {
                newFilters.status = filter;
            }

            window.location.href = buildUrl(newFilters);
        }, 200);
    });

    const currentFilters = getCurrentFilters();

    if (currentFilters.status) {
        $(`.stat-clickable[data-filter="${currentFilters.status}"]`).addClass('active');
    } else if (!currentFilters.search) {
        $('.stat-clickable[data-filter="all"]').addClass('active');
    }

    $('.fornecedores-delete-form').on('submit', function (e) {
        e.preventDefault();
        const form = this;

        Swal.fire({
            title: 'Tem certeza?',
            text: 'Deseja excluir este fornecedor? Esta ação pode ser revertida posteriormente.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sim, excluir',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
});