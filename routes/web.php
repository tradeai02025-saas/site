<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\FornecedorController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\PiController;
use App\Http\Controllers\RepresentanteController;
use App\Http\Controllers\SistemaController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\TipoDocumentoController;
use App\Http\Controllers\UserController;
use App\Models\Cliente;
use App\Models\Fornecedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/api/telegram/webhook/{token}', [TelegramWebhookController::class, 'handle'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return view('landing');
})->name('home');

Route::get('/planos', function () {
    return view('landing');
})->name('planos');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::get('/esqueci-senha', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/esqueci-senha', [AuthController::class, 'sendResetLink'])->name('password.email')->middleware('throttle:3,60');
    Route::get('/redefinir-senha/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/redefinir-senha', [AuthController::class, 'resetPassword'])->name('password.update')->middleware('throttle:5,1');

});

Route::middleware('auth', 'check.temporary.password')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::post('/validar-cnpj', function (Request $request) {
        $cnpjLimpo = preg_replace('/[^0-9]/', '', $request->input('cnpj', ''));

        if (empty($cnpjLimpo)) {
            return response()->json([
                'disponivel' => false,
                'mensagem' => 'CNPJ não informado',
            ]);
        }

        $queryCliente = Cliente::where('cnpj', $cnpjLimpo);
        if ($request->has('cliente_id') && $request->input('cliente_id')) {
            $queryCliente->where('id', '!=', $request->input('cliente_id'));
        }

        $queryFornecedor = Fornecedor::where('cnpj', $cnpjLimpo);
        if ($request->has('fornecedor_id') && $request->input('fornecedor_id')) {
            $queryFornecedor->where('id', '!=', $request->input('fornecedor_id'));
        }

        $queryRepresentante = \App\Models\RepresentanteUsuario::where('cnpj', $cnpjLimpo);
        if ($request->has('representante_id') && $request->input('representante_id')) {
            $queryRepresentante->where('id', '!=', $request->input('representante_id'));
        }

        $existe = $queryCliente->exists() || $queryFornecedor->exists() || $queryRepresentante->exists();

        return response()->json([
            'disponivel' => ! $existe,
            'mensagem' => $existe ? 'Este CNPJ já está cadastrado no sistema.' : 'CNPJ disponível',
        ]);
    })->name('validar.cnpj')->middleware('throttle:20,1');

    Route::prefix('perfil')->name('perfil.')->group(function () {
        Route::get('/', [PerfilController::class, 'edit'])->name('edit');
        Route::put('/', [PerfilController::class, 'update'])->name('update');
        Route::put('/senha', [PerfilController::class, 'updatePassword'])->name('update_password');
        Route::delete('/representante/{representante}', [PerfilController::class, 'destroyRepresentante'])->name('destroy_representante');
        Route::delete('/dados-bancarios/{dadosBancarios}', [PerfilController::class, 'destroyDadosBancarios'])->name('destroy_dados_bancarios');
        Route::get('/buscar-representantes-usuarios', [PerfilController::class, 'buscarRepresentantesUsuarios'])->name('buscar_representantes_usuarios');
        Route::post('/solicitar-vinculo-representante', [PerfilController::class, 'solicitarVinculo'])->name('solicitar_vinculo_representante');
        Route::delete('/cancelar-vinculo-representante/{vinculo}', [PerfilController::class, 'cancelarVinculo'])->name('cancelar_vinculo_representante');
        Route::get('/fornecedor-vinculado/{fornecedor}', [PerfilController::class, 'detalhesFornecedorVinculado'])->name('fornecedor_vinculado_detalhes');
    });

    Route::prefix('vinculos-representante')->name('vinculos.')->middleware('role:admin|gerencial')->group(function () {
        Route::get('/pendentes', [PerfilController::class, 'listarVinculosPendentes'])->name('pendentes');
        Route::post('/{vinculo}/aprovar', [PerfilController::class, 'aprovarVinculo'])->name('aprovar');
        Route::post('/{vinculo}/rejeitar', [PerfilController::class, 'rejeitarVinculo'])->name('rejeitar');
    });

    Route::prefix('representantes')->name('representantes.')->middleware('role:admin|gerencial')->group(function () {
        Route::get('/', [RepresentanteController::class, 'index'])->name('index');
        Route::get('/create', [RepresentanteController::class, 'create'])->name('create');
        Route::post('/', [RepresentanteController::class, 'store'])->name('store');
        Route::post('/validar-email', [RepresentanteController::class, 'validarEmail'])->name('validar_email');
        Route::get('/{representante}/edit', [RepresentanteController::class, 'edit'])->name('edit');
        Route::put('/{representante}', [RepresentanteController::class, 'update'])->name('update');
        Route::get('/{representante}/detalhes', [RepresentanteController::class, 'detalhes'])->name('detalhes');
        Route::post('/vinculos/{vinculo}/desvincular', [RepresentanteController::class, 'desvincular'])->name('desvincular');
        Route::post('/{representante}/toggle-ativo', [RepresentanteController::class, 'toggleAtivo'])->name('toggle_ativo');
        Route::post('/{representante}/reenviar-credenciais', [RepresentanteController::class, 'reenviarCredenciais'])->name('reenviar_credenciais');
        Route::delete('/{representante}', [RepresentanteController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('pis')->name('pis.')->group(function () {
        Route::get('/', [PiController::class, 'index'])->name('index');
        Route::get('/create', [PiController::class, 'create'])->name('create')->middleware('role:admin|gerencial|operacional|fornecedor|representante');
        Route::post('/', [PiController::class, 'store'])->name('store')->middleware('role:admin|gerencial|operacional|fornecedor|representante');
        Route::get('/tipo-veiculos', [PiController::class, 'getTipoVeiculos'])->name('tipo_veiculos');
        Route::get('/cliente/{cliente}/verifica-exige-veiculo', [PiController::class, 'verificaClienteExigeVeiculo'])->name('verificar_exige_veiculo');
        Route::get('/operacionais-disponiveis', [PiController::class, 'getOperacionaisDisponiveis'])->name('operacionais_disponiveis')->middleware('role:admin|gerencial|operacional');
        Route::get('/gerenciais-disponiveis', [PiController::class, 'getGerenciaisDisponiveis'])->name('gerenciais_disponiveis')->middleware('role:admin|gerencial');
        Route::post('/sugerir-operacional', [PiController::class, 'sugerirOperacional'])->name('sugerir_operacional')->middleware('role:admin|gerencial');
        Route::post('/extrair-numero-pi', [PiController::class, 'extrairNumeroPi'])->name('extrair_numero_pi')->middleware('role:admin|gerencial|operacional|fornecedor|representante');
        Route::post('/verificar-numero-pi', [PiController::class, 'verificarNumeroPi'])->name('verificar_numero_pi')->middleware('role:admin|gerencial|operacional|fornecedor|representante');
        Route::get('/envio-massa', [PiController::class, 'envioMassa'])->name('envio_massa')->middleware('role:admin|gerencial');
        Route::get('/envio-massa/buscar', [PiController::class, 'buscarPisParaReatribuicao'])->name('envio_massa.buscar')->middleware('role:admin|gerencial');
        Route::post('/envio-massa/processar', [PiController::class, 'processarEnvioMassa'])->name('envio_massa.processar')->middleware('role:admin|gerencial');
        Route::get('/{pi}/detalhes', [PiController::class, 'detalhes'])->name('detalhes');
        Route::get('/{pi}/download-documento', [PiController::class, 'downloadDocumento'])->name('download_documento');
        Route::get('/{pi}/visualizar-documento', [PiController::class, 'visualizarDocumentoPi'])->name('visualizar_documento');
        Route::get('/{cliente}/operacionais', [PiController::class, 'getOperacionaisCliente'])->name('get_operacionais_cliente')->middleware('role:admin|gerencial|operacional');
        Route::get('/{pi}/edit', [PiController::class, 'edit'])->name('edit');
        Route::put('/{pi}', [PiController::class, 'update'])->name('update');
        Route::delete('/{pi}', [PiController::class, 'destroy'])->name('destroy')->middleware('role:admin|gerencial|fornecedor|representante');
        Route::post('/{pi}/vincular-operacional', [PiController::class, 'vincularOperacional'])->name('vincular_operacional')->middleware('role:admin|gerencial|operacional');
        Route::post('/{pi}/alterar-status', [PiController::class, 'alterarStatus'])->name('alterar_status')->middleware('role:admin|gerencial|operacional');
        Route::post('/{pi}/concluir', [PiController::class, 'concluir'])->name('concluir')->middleware('role:admin|gerencial|operacional');
        Route::post('/{pi}/pagar', [PiController::class, 'pagarPi'])->name('pagar')->middleware('role:admin|gerencial|operacional');
        Route::get('/{pi}/baixar-todos-documentos', [PiController::class, 'baixarTodosDocumentos'])->name('baixar_todos_documentos');
        Route::get('/{pi}/enviar-documentos', [PiController::class, 'showEnviarDocumentos'])->name('enviar_documentos');
        Route::post('/{pi}/enviar-documentos', [PiController::class, 'storeDocumentosPi'])->name('store_documentos_pi');
        Route::post('/{pi}/validar-data-pdf', [PiController::class, 'validarDataPdf'])->name('validar_data_pdf');
        Route::get('/{pi}/tipos-documentos-avulsos', [PiController::class, 'getTiposDocumentosAvulsos'])->name('tipos_documentos_avulsos');
        Route::post('/{pi}/adicionar-documento-avulso', [PiController::class, 'adicionarDocumentoAvulso'])->name('adicionar_documento_avulso');
        Route::delete('/{pi}/documento-avulso/{piDocumento}', [PiController::class, 'removerDocumentoAvulso'])->name('remover_documento_avulso');
        Route::post('/{pi}/documentos/{piDocumento}/desfazer-envio', [PiController::class, 'desfazerEnvioDocumento'])->name('desfazer_envio_documento');
        Route::get('/{pi}/validar-documentos', [PiController::class, 'showValidarDocumentos'])->name('validar_documentos')->middleware('role:admin|gerencial|operacional');
        Route::post('/{pi}/documentos/{documento}/aprovar', [PiController::class, 'aprovarDocumento'])->name('aprovar_documento')->middleware('role:admin|gerencial|operacional');
        Route::post('/{pi}/documentos/{documento}/reprovar', [PiController::class, 'reprovarDocumento'])->name('reprovar_documento')->middleware('role:admin|gerencial|operacional');
        Route::post('/{pi}/documentos/{documento}/solicitar-reenvio', [PiController::class, 'solicitarReenvioDocumento'])->name('solicitar_reenvio_documento')->middleware('role:admin|gerencial|operacional');
        Route::get('/{pi}/documentos/{documento}/historico', [PiController::class, 'historicoValidacoesDocumento'])->name('historico_validacoes_documento')->middleware('role:admin|gerencial|operacional');
        Route::post('/{pi}/documentos/{documento}/reverter', [PiController::class, 'reverterValidacao'])->name('reverter_validacao')->middleware('role:admin|gerencial|operacional');
    });

    Route::middleware(['role:admin|gerencial|operacional'])->group(function () {
        Route::prefix('clientes')->name('clientes.')->group(function () {
            Route::get('/', [ClienteController::class, 'index'])->name('index');
            Route::get('/create', [ClienteController::class, 'create'])->name('create');
            Route::post('/', [ClienteController::class, 'store'])->name('store');
            Route::get('/{cliente}/detalhes', [ClienteController::class, 'detalhes'])->name('detalhes');
            Route::get('/{cliente}/edit', [ClienteController::class, 'edit'])->name('edit');
            Route::put('/{cliente}', [ClienteController::class, 'update'])->name('update');
            Route::delete('/{cliente}', [ClienteController::class, 'destroy'])->name('destroy');
            Route::patch('/{id}/restore', [ClienteController::class, 'restore'])->name('restore')->middleware('role:admin');
            Route::get('/buscar-por-cnpj', [ClienteController::class, 'buscarPorCnpj'])->name('buscar_por_cnpj');
            Route::get('/listar-ativos', [ClienteController::class, 'listarAtivos'])->name('listar_ativos');
            Route::get('/{cliente}/operacionais', [ClienteController::class, 'getOperacionais'])->name('get_operacionais');
            Route::post('/{cliente}/vincular-operacional', [ClienteController::class, 'vincularOperacional'])->name('vincular_operacional');
            Route::post('/{cliente}/adicionar-endereco', [ClienteController::class, 'adicionarEndereco'])->name('adicionar_endereco');
            Route::delete('/{cliente}/endereco/{endereco}', [ClienteController::class, 'removerEndereco'])->name('remover_endereco');
            Route::delete('/{cliente}/documento/{documentoRequerido}', [ClienteController::class, 'desvincularDocumento'])->name('desvincular_documento');
            Route::delete('/{cliente}/operacional/{atribuicao}', [ClienteController::class, 'desvincularOperacional'])->name('desvincular_operacional');
        });

        Route::prefix('fornecedores')->name('fornecedores.')->group(function () {
            Route::get('/', [FornecedorController::class, 'index'])->name('index');
            Route::get('/buscar-usuarios', [FornecedorController::class, 'buscarUsuarios'])->name('buscar_usuarios');
            Route::get('/create', [FornecedorController::class, 'create'])->name('create');
            Route::post('/', [FornecedorController::class, 'store'])->name('store');
            Route::get('/{fornecedor}/detalhes', [FornecedorController::class, 'detalhes'])->name('detalhes');
            Route::get('/{fornecedor}/edit', [FornecedorController::class, 'edit'])->name('edit');
            Route::put('/{fornecedor}', [FornecedorController::class, 'update'])->name('update');
            Route::delete('/{fornecedor}', [FornecedorController::class, 'destroy'])->name('destroy');
            Route::patch('/{id}/restore', [FornecedorController::class, 'restore'])->name('restore')->middleware('role:admin');
            Route::delete('/{fornecedor}/representante/{representante}', [FornecedorController::class, 'destroyRepresentante'])->name('destroy_representante');
            Route::delete('/{fornecedor}/dados-bancarios/{dadosBancarios}', [FornecedorController::class, 'destroyDadosBancarios'])->name('destroy_dados_bancarios');
            Route::post('/{fornecedor}/reenviar-credenciais', [FornecedorController::class, 'reenviarCredenciais'])->name('reenviar_credenciais')->middleware('role:admin|gerencial');
        });

        Route::prefix('documentos')->name('documentos.')->group(function () {
            Route::get('/', [DocumentoController::class, 'validarDocs'])->name('index');
            Route::get('/{documento}/preview/{arquivo}', [DocumentoController::class, 'preview'])->name('preview');
            Route::get('/{documento}/download/{arquivo}', [DocumentoController::class, 'download'])->name('download');
            Route::post('/{documento}/validar', [DocumentoController::class, 'validar'])->name('validar');
            Route::post('/{documento}/reprovar', [DocumentoController::class, 'reprovar'])->name('reprovar');
            Route::post('/{documento}/solicitar-reenvio', [DocumentoController::class, 'solicitarReenvio'])->name('solicitar_reenvio');
            Route::get('/{documento}/historico-validacoes', [DocumentoController::class, 'historicoValidacoes'])->name('historico_validacoes');
            Route::get('/vencendo', [DocumentoController::class, 'documentosVencendo'])->name('vencendo');
            Route::get('/vencidos', [DocumentoController::class, 'documentosVencidos'])->name('vencidos');
            Route::delete('/{documento}', [DocumentoController::class, 'destroy'])->name('destroy');
        });

        Route::middleware(['role:admin|gerencial'])->group(function () {
            Route::prefix('tipo-documentos')->name('tipo_documentos.')->group(function () {
                Route::get('/', [TipoDocumentoController::class, 'index'])->name('index');
                Route::get('/create', [TipoDocumentoController::class, 'create'])->name('create');
                Route::post('/', [TipoDocumentoController::class, 'store'])->name('store');
                Route::get('/por-veiculo', [TipoDocumentoController::class, 'getByTipoVeiculo'])->name('por_veiculo');
                Route::get('/ativos', [TipoDocumentoController::class, 'getAtivos'])->name('get_ativos');
                Route::get('/{tipoDocumento}/edit', [TipoDocumentoController::class, 'edit'])->name('edit');
                Route::put('/{tipoDocumento}', [TipoDocumentoController::class, 'update'])->name('update');
                Route::delete('/{tipoDocumento}', [TipoDocumentoController::class, 'destroy'])->name('destroy');
                Route::patch('/{id}/restore', [TipoDocumentoController::class, 'restore'])->name('restore')->middleware('role:admin');
                Route::post('/{tipoDocumento}/toggle-ativo', [TipoDocumentoController::class, 'toggleAtivo'])->name('toggle_ativo');
                Route::get('/{tipoDocumento}/verificar-usage', [TipoDocumentoController::class, 'verificarUsage'])->name('verificar_usage');
            });

            Route::prefix('users')->name('users.')->group(function () {
                Route::get('/', [UserController::class, 'index'])->name('index');
                Route::post('/', [UserController::class, 'store'])->name('store');
                Route::get('create', [UserController::class, 'create'])->name('create');
                Route::put('/{id}', [UserController::class, 'update'])->name('update');
                Route::get('{id}/edit', [UserController::class, 'edit'])->name('edit');
                Route::patch('/{id}/restore', [UserController::class, 'restore'])->name('restore')->middleware('role:admin');
                Route::delete('/{id}/force-destroy', [UserController::class, 'forceDestroy'])->name('forceDestroy')->middleware('role:admin');
                Route::post('/get-fields-by-role', [UserController::class, 'getFieldsByRole'])->name('get_fields_by_role');
                Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
                Route::delete('/{user}/representante/{representante}', [UserController::class, 'destroyRepresentante'])->name('destroy_representante');
                Route::delete('/{user}/dados-bancarios/{dadosBancarios}', [UserController::class, 'destroyDadosBancarios'])->name('destroy_dados_bancarios');
            });
        });

        Route::middleware(['role:admin'])->group(function () {
            Route::prefix('status')->name('status.')->group(function () {
                Route::get('/', [StatusController::class, 'index'])->name('index');
                Route::get('/create', [StatusController::class, 'create'])->name('create');
                Route::post('/', [StatusController::class, 'store'])->name('store');
                Route::get('/por-tipo', [StatusController::class, 'getStatusPorTipo'])->name('por_tipo');
                Route::get('/{status}/edit', [StatusController::class, 'edit'])->name('edit');
                Route::put('/{status}', [StatusController::class, 'update'])->name('update');
                Route::delete('/{status}', [StatusController::class, 'destroy'])->name('destroy');
                Route::patch('/{id}/restore', [StatusController::class, 'restore'])->name('restore');
                Route::post('/{status}/toggle-ativo', [StatusController::class, 'toggleAtivo'])->name('toggle_ativo');
                Route::get('/{status}/verificar-uso', [StatusController::class, 'verificarUso'])->name('verificar_uso');
            });
        });
    });

    Route::middleware(['role:fornecedor|representante'])->prefix('fornecedor')->name('fornecedor.')->group(function () {
        Route::get('/documentos-list', [DocumentoController::class, 'list'])->name('docs_list.index');
        Route::get('/documentos-list/{validade?}', [DocumentoController::class, 'list'])->name('docs_list.filter');
        Route::get('/documentos-enviar', [DocumentoController::class, 'docsEnviar'])->name('docs_enviar.index');
        Route::post('/documentos-store', [DocumentoController::class, 'store'])->name('documentos.store')->middleware('throttle:15,1');
        Route::post('/documentos/validar-data-pdf', [DocumentoController::class, 'validarDataPdf'])->name('documentos.validar_data_pdf');
        Route::post('/documentos/extrair-data-pdf', [DocumentoController::class, 'extrairDataDoPdf'])->name('documentos.extrair_data_pdf');
        Route::get('/documentos', [DocumentoController::class, 'index'])->name('documentos.index');
        Route::get('/documentos/{documento}/preview/{arquivo}', [DocumentoController::class, 'preview'])->name('documentos.preview');
        Route::get('/documentos/{documento}/download/{arquivo}', [DocumentoController::class, 'download'])->name('documentos.download');
        Route::get('/documentos/baixar-todos-zip', [DocumentoController::class, 'baixarTodosZip'])->name('documentos.baixar_todos_zip');
        Route::delete('/documentos/{documento}', [DocumentoController::class, 'destroy'])->name('documentos.destroy');
        Route::post('/documentos/{documento}/reenviar', [DocumentoController::class, 'reenviar'])->name('documentos.reenviar');
    });

    Route::prefix('chat')->name('chat.')->middleware(['auth'])->group(function () {
        Route::get('/', [ChatController::class, 'index'])->name('index');
        Route::get('/unread-count', [ChatController::class, 'getUnreadCount'])->name('unread_count');
        Route::get('/conversas-nao-lidas', [ChatController::class, 'conversasNaoLidas'])->name('conversas_nao_lidas');
        Route::get('/conversas-com-nao-lidas', [ChatController::class, 'conversasComNaoLidas'])->name('conversas_com_nao_lidas');
        Route::get('/buscar-usuarios', [ChatController::class, 'buscarUsuarios'])->name('buscar_usuarios');
        Route::get('/buscar-pis', [ChatController::class, 'buscarPis'])->name('buscar_pis');
        Route::post('/limpar-mensagens-orfas', [ChatController::class, 'limparMensagensOrfas'])->name('limpar_mensagens_orfas');
        Route::post('/criar-conversa', [ChatController::class, 'criarConversa'])->name('criar_conversa');
        Route::get('/anexo/{anexo}/preview', [ChatController::class, 'previewAnexo'])->name('preview_anexo');
        Route::get('/anexo/{anexo}/download', [ChatController::class, 'downloadAnexo'])->name('download_anexo');
        Route::get('/{conversa}', [ChatController::class, 'show'])->name('show');
        Route::get('/{conversa}/buscar-mensagens-novas', [ChatController::class, 'buscarMensagensNovas'])->name('buscar_mensagens_novas');
        Route::post('/{conversa}/enviar-mensagem', [ChatController::class, 'enviarMensagem'])->name('enviar_mensagem')->middleware('throttle:30,1');
        Route::post('/{conversa}/marcar-como-lida', [ChatController::class, 'marcarComoLida'])->name('marcar_como_lida');
        Route::post('/{conversa}/escalar', [ChatController::class, 'escalarConversa'])->name('escalar_conversa');
        Route::post('/{conversa}/resolver', [ChatController::class, 'resolverConversa'])->name('resolver_conversa');
        Route::post('/{conversa}/reabrir', [ChatController::class, 'reabrirConversa'])->name('reabrir_conversa');
        Route::post('/{conversa}/adicionar-participante', [ChatController::class, 'adicionarParticipante'])->name('adicionar_participante');
        Route::post('/{conversa}/transferir', [ChatController::class, 'transferirConversa'])->name('transferir_conversa');
        Route::post('/{conversa}/assumir', [ChatController::class, 'assumirConversa'])->name('assumir_conversa');
        Route::post('/{conversa}/sair', [ChatController::class, 'sairConversa'])->name('sair_conversa');
        Route::delete('/{conversa}', [ChatController::class, 'excluirConversa'])->name('excluir_conversa');
    });

    Route::middleware(['role:admin'])->name('sistema.')->group(function () {
        Route::post('/limpar-caches', [SistemaController::class, 'limparCaches'])->name('limpar_caches');
        Route::post('/reconstruir-caches', [SistemaController::class, 'reconstruirCaches'])->name('reconstruir_caches');
    });
});