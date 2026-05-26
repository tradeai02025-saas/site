<?php

/* app/Helpers/helpers.php */

if (! function_exists('previous_route')) {

    function previous_route($default = 'dashboard', $maxDepth = 10)
    {
        $current = url()->current();
        $currentHost = parse_url($current, PHP_URL_HOST);
        $history = session()->get('navigation_history', []);

        if (empty($history)) {
            return route($default);
        }

        $history = array_reverse($history);
        $checked = [];

        foreach ($history as $index => $url) {
            if ($index >= $maxDepth) {
                break;
            }

            if (in_array($url, $checked)) {
                continue;
            }

            $checked[] = $url;

            if ($url === $current) {
                continue;
            }

            $urlHost = parse_url($url, PHP_URL_HOST);

            if ($urlHost === $currentHost) {
                return $url;
            }
        }

        return route($default);
    }
}

if (! function_exists('clear_navigation_history')) {
    function clear_navigation_history()
    {
        session()->forget('navigation_history');
    }
}

if (! function_exists('get_navigation_history')) {
    function get_navigation_history()
    {
        return session()->get('navigation_history', []);
    }
}

if (! function_exists('get_representante_emails')) {
    function get_representante_emails($fornecedorId)
    {
        if (! $fornecedorId) {
            return [];
        }

        return \App\Models\RepresentanteFornecedor::where('fornecedor_id', $fornecedorId)
            ->where('status_vinculo', 'aprovado')
            ->where('ativo', true)
            ->whereNull('data_desvinculo')
            ->with('representanteUsuario.user')
            ->get()
            ->filter(function ($vinculo) {
                return $vinculo->representanteUsuario
                    && $vinculo->representanteUsuario->user
                    && $vinculo->representanteUsuario->user->email;
            })
            ->map(function ($vinculo) {
                return $vinculo->representanteUsuario->user->email;
            })
            ->unique()
            ->values()
            ->toArray();
    }
}

if (! function_exists('enviar_para_representantes')) {
    function enviar_para_representantes($mailable, $fornecedorId, $excluirEmail = null)
    {
        if (! $excluirEmail) {
            $fornecedor = \App\Models\Fornecedor::with('user')->find($fornecedorId);
            $excluirEmail = $fornecedor && $fornecedor->user ? $fornecedor->user->email : null;
        }

        $emails = get_representante_emails($fornecedorId);

        foreach ($emails as $email) {
            if ($excluirEmail && strtolower($email) === strtolower($excluirEmail)) {
                continue;
            }

            try {
                \App\Services\MailRotator::send(clone $mailable, $email);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::channel('docs')->warning('Erro ao enviar email para representante', [
                    'email' => $email,
                    'fornecedor_id' => $fornecedorId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}