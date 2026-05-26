<?php

/* app/Http/Controllers/PerfilController.php */

namespace App\Http\Controllers;

use App\Models\{Cliente, Fornecedor, RepresentanteFornecedor, RepresentanteUsuario, User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Hash, Log};
use Illuminate\Validation\Rules\Password;
use Spatie\Activitylog\Models\Activity;

class PerfilController extends Controller
{
    public function edit()
    {
        $user = Auth::user();
        $user->load(['genOp', 'fornecedor.enderecos', 'fornecedor.representantes', 'fornecedor.dadosBancarios', 'roles']);

        $incompleteStep = $user->getIncompleteProfileStep();
        $profileCompletion = $user->getProfileCompletionPercentage();

        $abaAtiva = request()->get('aba', 'geral');

        if ($incompleteStep && ! request()->has('aba')) {
            $abaAtiva = $incompleteStep['tab'];
        }

        $vinculosRepresentante = collect();
        if ($user->hasRole('fornecedor') && $user->fornecedor) {
            $vinculosRepresentante = RepresentanteFornecedor::where('fornecedor_id', $user->fornecedor->id)
                ->whereIn('status_vinculo', ['pendente', 'aprovado'])
                ->where('ativo', true)
                ->whereNull('data_desvinculo')
                ->with('representanteUsuario.user')
                ->orderByRaw("FIELD(status_vinculo, 'pendente', 'aprovado')")
                ->orderBy('created_at', 'desc')
                ->get();
        }

        if ($user->hasRole('representante') && $user->representanteUsuario) {
            $vinculosRepresentante = RepresentanteFornecedor::where('representante_usuario_id', $user->representanteUsuario->id)
                ->whereIn('status_vinculo', ['pendente', 'aprovado'])
                ->where('ativo', true)
                ->whereNull('data_desvinculo')
                ->with('fornecedor')
                ->orderByRaw("FIELD(status_vinculo, 'pendente', 'aprovado')")
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return view('perfil.edit', compact('user', 'incompleteStep', 'profileCompletion', 'abaAtiva', 'vinculosRepresentante'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $abaGeral = $request->has('name') && $request->has('email') && ! $request->has('razao_social');
        $abaEmpresa = $request->has('razao_social') || $request->has('cnpj');
        $abaEndereco = $request->has('cep') || $request->has('logradouro') || $request->has('cidade');
        $abaRepresentantes = $request->has('representantes');
        $abaDadosBancarios = $request->has('dados_bancarios');

        $rules = [];
        $messages = [];

        if ($abaGeral) {
            $rules['name'] = 'required|string|max:255';
            $rules['email'] = 'required|email|unique:users,email,'.$user->id;

            $messages['name.required'] = 'O nome é obrigatório';
            $messages['email.required'] = 'O email é obrigatório';
            $messages['email.unique'] = 'Este email já está em uso';

            if ($user->hasRole(['gerencial', 'operacional'])) {
                $rules['nome_completo'] = 'required|string|max:255';
                $rules['telefone'] = 'nullable|string|max:20';
                $rules['departamento'] = 'nullable|string|max:100';

                $messages['nome_completo.required'] = 'O nome completo é obrigatório';
            }

            if ($user->hasRole('representante') && $user->representanteUsuario) {
                $rules['nome_empresa'] = 'nullable|string|max:255';
                $rules['cnpj_representante'] = 'nullable|string|max:18';
                $rules['telefone_representante'] = 'nullable|string|max:20';
            }
        }

        if ($abaEmpresa && $user->hasRole('fornecedor')) {
            $rules['razao_social'] = 'required|string|max:255';
            $rules['cnpj'] = 'required|string|size:18';
            $rules['nome_fantasia'] = 'nullable|string|max:255';
            $rules['nome_veiculo'] = 'nullable|string|max:255';

            $temDadosBancarios = $user->fornecedor->dadosBancarios()->exists();
            if (! $user->fornecedor->telefone || ! $temDadosBancarios) {
                $rules['telefone'] = 'required|string|max:20';
                $messages['telefone.required'] = 'O telefone é obrigatório para completar seu cadastro';
            } else {
                $rules['telefone'] = 'nullable|string|max:20';
            }

            $rules['fax'] = 'nullable|string|max:20';
            $rules['praca'] = 'nullable|string|max:255';
            $rules['email_contato'] = 'nullable|email|max:255';
            $rules['site'] = 'nullable|url|max:255';
            $rules['observacoes'] = 'nullable|string';

            $messages['razao_social.required'] = 'A razão social é obrigatória';
            $messages['cnpj.required'] = 'O CNPJ é obrigatório';
        }

        if ($abaEndereco && $user->hasRole('fornecedor')) {
            $temCep = $request->filled('cep');
            $temLogradouro = $request->filled('logradouro');
            $temNumero = $request->filled('numero');
            $temBairro = $request->filled('bairro');
            $temCidade = $request->filled('cidade');
            $temEstado = $request->filled('estado');

            $temAlgumCampoEndereco = $temCep || $temLogradouro || $temNumero || $temBairro || $temCidade || $temEstado;

            if ($temAlgumCampoEndereco) {
                $rules['cep'] = 'required|string|max:10';
                $rules['logradouro'] = 'required|string|max:255';
                $rules['numero'] = 'required|string|max:20';
                $rules['bairro'] = 'required|string|max:100';
                $rules['cidade'] = 'required|string|max:100';
                $rules['estado'] = 'required|string|size:2';

                $messages['cep.required'] = 'O CEP é obrigatório';
                $messages['logradouro.required'] = 'O logradouro é obrigatório';
                $messages['numero.required'] = 'O número é obrigatório';
                $messages['bairro.required'] = 'O bairro é obrigatório';
                $messages['cidade.required'] = 'A cidade é obrigatória';
                $messages['estado.required'] = 'O estado é obrigatório';
            }

            $rules['complemento'] = 'nullable|string|max:100';
        }

        if ($abaRepresentantes && $user->hasRole('fornecedor') && $request->has('representantes') && is_array($request->representantes)) {
            foreach ($request->representantes as $index => $rep) {
                $temNome = ! empty($rep['nome_completo']);
                $temCpf = ! empty($rep['cpf']);

                if ($temNome || $temCpf) {
                    $rules["representantes.{$index}.nome_completo"] = 'required|string|max:255';
                    $rules["representantes.{$index}.cpf"] = 'required|string|max:14';

                    $messages["representantes.{$index}.nome_completo.required"] = 'O nome completo do representante é obrigatório';
                    $messages["representantes.{$index}.cpf.required"] = 'O CPF do representante é obrigatório';
                }

                $rules["representantes.{$index}.telefone_1"] = 'nullable|string|max:20';
                $rules["representantes.{$index}.telefone_2"] = 'nullable|string|max:20';
                $rules["representantes.{$index}.email_1"] = 'nullable|email|max:255';
                $rules["representantes.{$index}.email_2"] = 'nullable|email|max:255';
            }
        }

        if ($abaDadosBancarios && $user->hasRole('fornecedor') && $request->has('dados_bancarios') && is_array($request->dados_bancarios)) {
            $temTelefone = ! empty($user->fornecedor->telefone);
            $temDadosBancarios = $user->fornecedor->dadosBancarios()->exists();

            $obrigatorio = ! $temTelefone || ! $temDadosBancarios;

            foreach ($request->dados_bancarios as $index => $dados) {
                if ($obrigatorio) {
                    $rules["dados_bancarios.{$index}.banco"] = 'required|string|max:255';
                    $rules["dados_bancarios.{$index}.agencia"] = 'required|string|max:10';
                    $rules["dados_bancarios.{$index}.conta"] = 'required|string|max:20';
                    $rules["dados_bancarios.{$index}.digito_conta"] = 'required|string|max:2';
                    $rules["dados_bancarios.{$index}.tipo_conta"] = 'required|in:corrente,poupanca';
                    $rules["dados_bancarios.{$index}.titular"] = 'required|string|max:255';
                    $rules["dados_bancarios.{$index}.cpf_cnpj_titular"] = 'required|string|max:18';

                    $messages["dados_bancarios.{$index}.banco.required"] = 'O nome do banco é obrigatório para completar seu cadastro';
                    $messages["dados_bancarios.{$index}.agencia.required"] = 'A agência é obrigatória';
                    $messages["dados_bancarios.{$index}.conta.required"] = 'O número da conta é obrigatório';
                    $messages["dados_bancarios.{$index}.digito_conta.required"] = 'O dígito da conta é obrigatório';
                    $messages["dados_bancarios.{$index}.tipo_conta.required"] = 'O tipo de conta é obrigatório';
                    $messages["dados_bancarios.{$index}.titular.required"] = 'O nome do titular é obrigatório';
                    $messages["dados_bancarios.{$index}.cpf_cnpj_titular.required"] = 'O CPF/CNPJ do titular é obrigatório';
                } else {
                    $temAlgumDado = ! empty($dados['banco']) || ! empty($dados['agencia']) ||
                                   ! empty($dados['conta']) || ! empty($dados['digito_conta']) ||
                                   ! empty($dados['tipo_conta']) || ! empty($dados['titular']) ||
                                   ! empty($dados['cpf_cnpj_titular']);

                    if ($temAlgumDado) {
                        $rules["dados_bancarios.{$index}.banco"] = 'required|string|max:255';
                        $rules["dados_bancarios.{$index}.agencia"] = 'required|string|max:10';
                        $rules["dados_bancarios.{$index}.conta"] = 'required|string|max:20';
                        $rules["dados_bancarios.{$index}.digito_conta"] = 'required|string|max:2';
                        $rules["dados_bancarios.{$index}.tipo_conta"] = 'required|in:corrente,poupanca';
                        $rules["dados_bancarios.{$index}.titular"] = 'required|string|max:255';
                        $rules["dados_bancarios.{$index}.cpf_cnpj_titular"] = 'required|string|max:18';
                    }
                }

                $rules["dados_bancarios.{$index}.digito_agencia"] = 'nullable|string|max:2';
                $rules["dados_bancarios.{$index}.pix_tipo"] = 'nullable|in:cpf,cnpj,email,telefone,chave_aleatoria';
                $rules["dados_bancarios.{$index}.pix_chave"] = 'nullable|string|max:255';
            }

            if ($obrigatorio && empty($request->dados_bancarios)) {
                return back()->withInput()
                    ->with('error', 'Os dados bancários são obrigatórios para completar seu cadastro.');
            }
        }

        $validated = $request->validate($rules, $messages);

        try {
            DB::beginTransaction();

            if ($abaGeral) {
                $user->name = $validated['name'];
                $user->email = $validated['email'];
                $user->save();

                if ($user->hasRole(['gerencial', 'operacional']) && $user->genOp) {
                    $user->genOp->update([
                        'nome_completo' => $validated['nome_completo'],
                        'telefone' => $validated['telefone'] ?? $user->genOp->telefone,
                        'departamento' => $validated['departamento'] ?? $user->genOp->departamento,
                    ]);
                }

                if ($user->hasRole('representante') && $user->representanteUsuario) {
                    $cnpjRepLimpo = isset($validated['cnpj_representante'])
                        ? preg_replace('/[^0-9]/', '', $validated['cnpj_representante'])
                        : $user->representanteUsuario->cnpj;

                    if ($cnpjRepLimpo && strlen($cnpjRepLimpo) === 14 && $cnpjRepLimpo !== $user->representanteUsuario->cnpj) {
                        $cnpjDuplicado = Fornecedor::where('cnpj', $cnpjRepLimpo)->exists()
                            || Cliente::where('cnpj', $cnpjRepLimpo)->exists()
                            || RepresentanteUsuario::where('cnpj', $cnpjRepLimpo)->where('id', '!=', $user->representanteUsuario->id)->exists();

                        if ($cnpjDuplicado) {
                            DB::rollBack();

                            return back()->withInput()
                                ->with('error', 'Este CNPJ já está cadastrado no sistema.');
                        }
                    }

                    $user->representanteUsuario->update([
                        'nome_empresa' => $validated['nome_empresa'] ?? $user->representanteUsuario->nome_empresa,
                        'cnpj' => $cnpjRepLimpo,
                        'telefone' => $validated['telefone_representante'] ?? $user->representanteUsuario->telefone,
                    ]);
                }
            }

            if ($abaEmpresa && $user->hasRole('fornecedor') && $user->fornecedor) {
                $cnpjLimpo = preg_replace('/[^0-9]/', '', $validated['cnpj']);

                if (! $this->validarCnpjUnico($cnpjLimpo, $user->fornecedor->id)) {
                    DB::rollBack();

                    return back()->withInput()
                        ->with('error', 'Este CNPJ já está cadastrado no sistema.');
                }

                $user->fornecedor->update([
                    'razao_social' => $validated['razao_social'],
                    'cnpj' => $cnpjLimpo,
                    'nome_fantasia' => $validated['nome_fantasia'] ?? null,
                    'nome_veiculo' => $validated['nome_veiculo'] ?? null,
                    'telefone' => $validated['telefone'] ?? $user->fornecedor->telefone,
                    'fax' => $validated['fax'] ?? null,
                    'praca' => $validated['praca'] ?? null,
                    'email_contato' => $validated['email_contato'] ?? null,
                    'site' => $validated['site'] ?? null,
                    'observacoes' => $validated['observacoes'] ?? null,
                ]);
            }

            if ($abaEndereco && $user->hasRole('fornecedor') && $user->fornecedor) {
                $temAlgumCampoEndereco = $request->filled('cep') || $request->filled('logradouro') ||
                    $request->filled('numero') || $request->filled('bairro') ||
                    $request->filled('cidade') || $request->filled('estado');

                if ($temAlgumCampoEndereco) {
                    $endereco = $user->fornecedor->enderecos()->first();

                    $dadosEndereco = [
                        'cep' => preg_replace('/[^0-9]/', '', $validated['cep']),
                        'logradouro' => $validated['logradouro'],
                        'numero' => $validated['numero'],
                        'complemento' => $validated['complemento'] ?? null,
                        'bairro' => $validated['bairro'],
                        'cidade' => $validated['cidade'],
                        'estado' => $validated['estado'],
                    ];

                    if ($endereco) {
                        $endereco->update($dadosEndereco);
                    } else {
                        $user->fornecedor->enderecos()->create($dadosEndereco);
                    }
                }
            }

            if ($abaRepresentantes && $user->hasRole('fornecedor') && $user->fornecedor) {
                $representantesIds = [];
                if ($request->has('representantes') && is_array($request->representantes)) {
                    foreach ($request->representantes as $repData) {
                        if (empty($repData['nome_completo']) && empty($repData['cpf'])) {
                            continue;
                        }

                        $dadosRepresentante = [
                            'nome_completo' => $repData['nome_completo'],
                            'cpf' => preg_replace('/[^0-9]/', '', $repData['cpf']),
                            'telefone_1' => $repData['telefone_1'] ?? null,
                            'telefone_2' => $repData['telefone_2'] ?? null,
                            'email_1' => $repData['email_1'] ?? null,
                            'email_2' => $repData['email_2'] ?? null,
                        ];

                        if (! empty($repData['id'])) {
                            $representante = $user->fornecedor->representantes()->find($repData['id']);
                            if ($representante) {
                                $representante->update($dadosRepresentante);
                                $representantesIds[] = $representante->id;
                            }
                        } else {
                            $novo = $user->fornecedor->representantes()->create($dadosRepresentante);
                            $representantesIds[] = $novo->id;
                        }
                    }
                }

                $user->fornecedor->representantes()
                    ->whereNotIn('id', $representantesIds)
                    ->delete();
            }

            if ($abaDadosBancarios && $user->hasRole('fornecedor') && $user->fornecedor) {
                $dadosBancariosIds = [];
                if ($request->has('dados_bancarios') && is_array($request->dados_bancarios)) {
                    foreach ($request->dados_bancarios as $dbData) {
                        if (empty($dbData['banco']) && empty($dbData['agencia']) && empty($dbData['conta'])) {
                            continue;
                        }

                        $dadosBanco = [
                            'banco' => $dbData['banco'],
                            'agencia' => $dbData['agencia'],
                            'digito_agencia' => $dbData['digito_agencia'] ?? null,
                            'conta' => $dbData['conta'],
                            'digito_conta' => $dbData['digito_conta'] ?? null,
                            'tipo_conta' => $dbData['tipo_conta'],
                            'titular' => $dbData['titular'],
                            'cpf_cnpj_titular' => preg_replace('/[^0-9]/', '', $dbData['cpf_cnpj_titular']),
                            'pix_tipo' => $dbData['pix_tipo'] ?? null,
                            'pix_chave' => $dbData['pix_chave'] ?? null,
                        ];

                        if (! empty($dbData['id'])) {
                            $db = $user->fornecedor->dadosBancarios()->find($dbData['id']);
                            if ($db) {
                                $db->update($dadosBanco);
                                $dadosBancariosIds[] = $db->id;
                            }
                        } else {
                            $novo = $user->fornecedor->dadosBancarios()->create($dadosBanco);
                            $dadosBancariosIds[] = $novo->id;
                        }
                    }
                }

                $user->fornecedor->dadosBancarios()
                    ->whereNotIn('id', $dadosBancariosIds)
                    ->delete();
            }

            DB::commit();

            activity()
                ->causedBy($user)
                ->log('Perfil atualizado');

            $nextStep = $user->fresh()->getIncompleteProfileStep();

            if ($nextStep) {
                return redirect()->route('perfil.edit', ['aba' => $nextStep['tab']])
                    ->with('success', 'Dados salvos com sucesso!')
                    ->with('info', 'Continue completando seu cadastro: '.$nextStep['title']);
            }

            $redirectAba = 'geral';
            if ($abaEmpresa) {
                $redirectAba = 'empresa';
            }
            if ($abaEndereco) {
                $redirectAba = 'endereco';
            }
            if ($abaRepresentantes) {
                $redirectAba = 'representantes';
            }
            if ($abaDadosBancarios) {
                $redirectAba = 'banking';
            }

            return redirect()->route('perfil.edit', ['aba' => $redirectAba])
                ->with('success', 'Perfil atualizado com sucesso!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('security')->error('Erro ao atualizar perfil', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withInput()
                ->with('error', 'Erro ao atualizar perfil: '.$e->getMessage());
        }
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required',
            'password' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
        ], [
            'current_password.required' => 'A senha atual é obrigatória',
            'password.required' => 'A nova senha é obrigatória',
            'password.confirmed' => 'As senhas não conferem',
        ]);

        $user = Auth::user();

        if (! Hash::check($validated['current_password'], $user->password)) {
            return back()
                ->withErrors(['current_password' => 'A senha atual está incorreta'])
                ->withInput();
        }

        if (Hash::check($validated['password'], $user->password)) {
            return back()
                ->withErrors(['password' => 'A nova senha deve ser diferente da senha atual'])
                ->withInput();
        }

        try {
            $user->password = Hash::make($validated['password']);
            $user->save();

            activity()
                ->causedBy($user)
                ->log('Senha alterada');

            Activity::where('causer_type', get_class($user))
                ->where('causer_id', $user->id)
                ->where('description', 'Senha temporária enviada - requer troca obrigatória')
                ->whereJsonContains('properties->tipo', 'senha_temporaria')
                ->delete();

            Auth::logoutOtherDevices($validated['password']);

            $nextStep = $user->getIncompleteProfileStep();

            if ($nextStep) {
                return redirect()->route('perfil.edit', ['aba' => $nextStep['tab']])
                    ->with('success', 'Senha alterada com sucesso!')
                    ->with('info', 'Agora complete seu cadastro: '.$nextStep['title']);
            }

            return redirect()->route('perfil.edit')
                ->with('success', 'Senha alterada com sucesso! Seu perfil está completo.');

        } catch (\Exception $e) {
            Log::channel('security')->error('Erro ao alterar senha', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->with('error', 'Erro ao alterar senha: '.$e->getMessage());
        }
    }

    public function buscarRepresentantesUsuarios(Request $request)
    {
        $user = Auth::user();

        if (! $user->hasRole('fornecedor') || ! $user->fornecedor) {
            return response()->json(['success' => false, 'message' => 'Ação não permitida.'], 403);
        }

        $termo = $request->input('termo', '');
        if (strlen($termo) < 3) {
            return response()->json(['success' => true, 'resultados' => []]);
        }

        $jaVinculados = RepresentanteFornecedor::where('fornecedor_id', $user->fornecedor->id)
            ->whereIn('status_vinculo', ['pendente', 'aprovado'])
            ->where('ativo', true)
            ->whereNull('data_desvinculo')
            ->pluck('representante_usuario_id')
            ->toArray();

        $resultados = RepresentanteUsuario::where('ativo', true)
            ->whereNull('deleted_at')
            ->whereNotIn('id', $jaVinculados)
            ->where(function ($q) use ($termo) {
                $q->where('nome_empresa', 'like', "%{$termo}%")
                    ->orWhereHas('user', function ($uq) use ($termo) {
                        $uq->where('name', 'like', "%{$termo}%")
                            ->orWhere('email', 'like', "%{$termo}%");
                    });
            })
            ->with('user:id,name,email')
            ->take(10)
            ->get()
            ->map(function ($rep) {
                return [
                    'id' => $rep->id,
                    'nome' => $rep->name,
                    'email' => $rep->user->email ?? '',
                    'empresa' => $rep->nome_empresa,
                    'cnpj' => $rep->cnpj,
                ];
            });

        return response()->json(['success' => true, 'resultados' => $resultados]);
    }

    public function solicitarVinculo(Request $request)
    {
        $user = Auth::user();

        if (! $user->hasRole('fornecedor') || ! $user->fornecedor) {
            return response()->json(['success' => false, 'message' => 'Ação não permitida.'], 403);
        }

        $request->validate([
            'representante_usuario_id' => 'required|integer|exists:representante_usuarios,id',
        ]);

        $representanteUsuario = RepresentanteUsuario::where('id', $request->representante_usuario_id)
            ->where('ativo', true)
            ->whereNull('deleted_at')
            ->first();

        if (! $representanteUsuario) {
            return response()->json(['success' => false, 'message' => 'Representante não encontrado ou inativo.'], 404);
        }

        $vinculoExistenteCheck = RepresentanteFornecedor::withTrashed()
            ->where('representante_usuario_id', $representanteUsuario->id)
            ->where('fornecedor_id', $user->fornecedor->id)
            ->whereNull('deleted_at')
            ->first();

        if ($vinculoExistenteCheck) {
            if ($vinculoExistenteCheck->status_vinculo === 'pendente' && $vinculoExistenteCheck->ativo) {
                return response()->json(['success' => false, 'message' => 'Já existe uma solicitação pendente para este representante.'], 422);
            }

            if ($vinculoExistenteCheck->status_vinculo === 'aprovado' && $vinculoExistenteCheck->ativo && ! $vinculoExistenteCheck->data_desvinculo) {
                return response()->json(['success' => false, 'message' => 'Este representante já está vinculado.'], 422);
            }

            if ($vinculoExistenteCheck->status_vinculo === 'rejeitado' && $vinculoExistenteCheck->data_aprovacao && $vinculoExistenteCheck->data_aprovacao->diffInHours(now()) < 24) {
                return response()->json(['success' => false, 'message' => 'Este vínculo foi rejeitado recentemente. Aguarde 24 horas para solicitar novamente.'], 422);
            }
        }

        try {
            $vinculoExistente = RepresentanteFornecedor::withTrashed()
                ->where('representante_usuario_id', $representanteUsuario->id)
                ->where('fornecedor_id', $user->fornecedor->id)
                ->first();

            if ($vinculoExistente) {
                if ($vinculoExistente->trashed()) {
                    $vinculoExistente->restore();
                }
                $vinculoExistente->update([
                    'status_vinculo' => 'pendente',
                    'data_vinculo' => now(),
                    'data_desvinculo' => null,
                    'data_aprovacao' => null,
                    'aprovado_por' => null,
                    'motivo_rejeicao' => null,
                    'ativo' => true,
                ]);
                $vinculo = $vinculoExistente;
            } else {
                $vinculo = RepresentanteFornecedor::create([
                    'representante_usuario_id' => $representanteUsuario->id,
                    'fornecedor_id' => $user->fornecedor->id,
                    'status_vinculo' => 'pendente',
                    'data_vinculo' => now(),
                    'ativo' => true,
                ]);
            }

            activity()
                ->causedBy($user)
                ->performedOn($vinculo)
                ->log('Solicitação de vínculo com representante: '.($representanteUsuario->name));

            return response()->json([
                'success' => true,
                'message' => 'Solicitação de vínculo enviada! Aguarde a aprovação.',
                'vinculo' => [
                    'id' => $vinculo->id,
                    'representante_nome' => $representanteUsuario->name,
                    'representante_email' => $representanteUsuario->user->email ?? '',
                    'representante_empresa' => $representanteUsuario->nome_empresa,
                    'status' => 'pendente',
                    'data' => $vinculo->created_at->format('d/m/Y H:i'),
                ],
            ]);
        } catch (\Exception $e) {
            Log::channel('security')->error('Erro ao solicitar vínculo de representante', [
                'user_id' => $user->id,
                'representante_usuario_id' => $request->representante_usuario_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'message' => 'Erro ao solicitar vínculo.'], 500);
        }
    }

    public function cancelarVinculo($id)
    {
        $user = Auth::user();

        if (! $user->hasRole('fornecedor') || ! $user->fornecedor) {
            return response()->json(['success' => false, 'message' => 'Ação não permitida.'], 403);
        }

        $vinculo = RepresentanteFornecedor::where('id', $id)
            ->where('fornecedor_id', $user->fornecedor->id)
            ->where('ativo', true)
            ->first();

        if (! $vinculo) {
            return response()->json(['success' => false, 'message' => 'Vínculo não encontrado.'], 404);
        }

        try {
            $vinculo->desvincular();

            activity()
                ->causedBy($user)
                ->performedOn($vinculo)
                ->log('Vínculo com representante cancelado/removido');

            return response()->json(['success' => true, 'message' => 'Vínculo removido com sucesso.']);
        } catch (\Exception $e) {
            Log::channel('security')->error('Erro ao cancelar vínculo', [
                'vinculo_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'message' => 'Erro ao cancelar vínculo.'], 500);
        }
    }

    public function listarVinculosPendentes()
    {
        $user = Auth::user();

        if (! $user->hasAnyRole(['admin', 'gerencial'])) {
            return response()->json(['success' => false, 'message' => 'Acesso negado.'], 403);
        }

        $pendentes = RepresentanteFornecedor::where('status_vinculo', 'pendente')
            ->where('ativo', true)
            ->with(['representanteUsuario.user', 'fornecedor'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($v) {
                return [
                    'id' => $v->id,
                    'representante_nome' => $v->representanteUsuario->name ?? 'N/A',
                    'representante_email' => $v->representanteUsuario->user->email ?? '',
                    'representante_empresa' => $v->representanteUsuario->nome_empresa,
                    'fornecedor_razao' => $v->fornecedor->razao_social ?? 'N/A',
                    'fornecedor_fantasia' => $v->fornecedor->nome_fantasia,
                    'data_solicitacao' => $v->created_at->format('d/m/Y H:i'),
                ];
            });

        return response()->json(['success' => true, 'pendentes' => $pendentes]);
    }

    public function aprovarVinculo($id)
    {
        $user = Auth::user();

        if (! $user->hasAnyRole(['admin', 'gerencial'])) {
            return response()->json(['success' => false, 'message' => 'Acesso negado.'], 403);
        }

        $vinculo = RepresentanteFornecedor::where('id', $id)
            ->where('status_vinculo', 'pendente')
            ->where('ativo', true)
            ->first();

        if (! $vinculo) {
            return response()->json(['success' => false, 'message' => 'Vínculo não encontrado ou já processado.'], 404);
        }

        try {
            $vinculo->aprovar($user->id);

            activity()
                ->causedBy($user)
                ->performedOn($vinculo)
                ->log('Vínculo de representante aprovado');

            return response()->json(['success' => true, 'message' => 'Vínculo aprovado com sucesso!']);
        } catch (\Exception $e) {
            Log::channel('security')->error('Erro ao aprovar vínculo', [
                'vinculo_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'message' => 'Erro ao aprovar vínculo.'], 500);
        }
    }

    public function rejeitarVinculo(Request $request, $id)
    {
        $user = Auth::user();

        if (! $user->hasAnyRole(['admin', 'gerencial'])) {
            return response()->json(['success' => false, 'message' => 'Acesso negado.'], 403);
        }

        $vinculo = RepresentanteFornecedor::where('id', $id)
            ->where('status_vinculo', 'pendente')
            ->where('ativo', true)
            ->first();

        if (! $vinculo) {
            return response()->json(['success' => false, 'message' => 'Vínculo não encontrado ou já processado.'], 404);
        }

        try {
            $vinculo->rejeitar($user->id, $request->input('motivo'));

            activity()
                ->causedBy($user)
                ->performedOn($vinculo)
                ->log('Vínculo de representante rejeitado');

            return response()->json(['success' => true, 'message' => 'Vínculo rejeitado.']);
        } catch (\Exception $e) {
            Log::channel('security')->error('Erro ao rejeitar vínculo', [
                'vinculo_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'message' => 'Erro ao rejeitar vínculo.'], 500);
        }
    }

    public function destroyRepresentante($id)
    {
        $user = Auth::user();

        if (! $user->hasRole('fornecedor') || ! $user->fornecedor) {
            Log::channel('security')->warning('Tentativa de ação por não-fornecedor', ['user_id' => $user->id]);

            return response()->json([
                'success' => false,
                'message' => 'Ação não permitida',
            ], 403);
        }

        try {
            $representante = $user->fornecedor->representantes()->findOrFail($id);

            $nomeRepresentante = $representante->nome_completo;
            $representante->delete();

            activity()
                ->causedBy($user)
                ->performedOn($representante)
                ->log('Representante removido: '.$nomeRepresentante);

            return response()->json([
                'success' => true,
                'message' => 'Representante removido com sucesso!',
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::channel('security')->error('Representante não encontrado ao remover', ['id' => $id]);

            return response()->json([
                'success' => false,
                'message' => 'Representante não encontrado',
            ], 404);

        } catch (\Exception $e) {
            Log::channel('security')->error('Erro ao remover representante', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover representante: '.$e->getMessage(),
            ], 500);
        }
    }

    public function destroyDadosBancarios($id)
    {
        $user = Auth::user();

        if (! $user->hasRole('fornecedor') || ! $user->fornecedor) {
            Log::channel('security')->warning('Tentativa de ação por não-fornecedor', ['user_id' => $user->id]);

            return response()->json([
                'success' => false,
                'message' => 'Ação não permitida',
            ], 403);
        }

        try {
            $dadosBancarios = $user->fornecedor->dadosBancarios()->findOrFail($id);

            $bancoNome = $dadosBancarios->banco;
            $dadosBancarios->delete();

            activity()
                ->causedBy($user)
                ->performedOn($dadosBancarios)
                ->log('Dados bancários removidos: '.$bancoNome);

            return response()->json([
                'success' => true,
                'message' => 'Dados bancários removidos com sucesso!',
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::channel('security')->error('Dados bancários não encontrados ao remover', ['id' => $id]);

            return response()->json([
                'success' => false,
                'message' => 'Dados bancários não encontrados',
            ], 404);

        } catch (\Exception $e) {
            Log::channel('security')->error('Erro ao remover dados bancários', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover dados bancários: '.$e->getMessage(),
            ], 500);
        }
    }

    private function validarCnpjUnico($cnpj, $fornecedorIdIgnorar = null)
    {
        $cnpjLimpo = preg_replace('/[^0-9]/', '', $cnpj);

        $queryFornecedor = Fornecedor::where('cnpj', $cnpjLimpo);
        if ($fornecedorIdIgnorar) {
            $queryFornecedor->where('id', '!=', $fornecedorIdIgnorar);
        }

        $queryCliente = Cliente::where('cnpj', $cnpjLimpo);
        $queryRepresentante = RepresentanteUsuario::where('cnpj', $cnpjLimpo);

        return ! $queryFornecedor->exists() && ! $queryCliente->exists() && ! $queryRepresentante->exists();
    }

    public function detalhesFornecedorVinculado($fornecedorId)
    {
        $user = Auth::user();

        if (! $user->hasRole('representante') || ! $user->representanteUsuario) {
            return response()->json(['success' => false, 'message' => 'Acesso negado.'], 403);
        }

        if (! $user->representanteUsuario->representaFornecedor($fornecedorId)) {
            return response()->json(['success' => false, 'message' => 'Você não representa este fornecedor.'], 403);
        }

        $fornecedor = Fornecedor::with([
            'user',
            'enderecos',
            'representantes',
        ])->find($fornecedorId);

        if (! $fornecedor) {
            return response()->json(['success' => false, 'message' => 'Fornecedor não encontrado.'], 404);
        }

        $totalPis = $fornecedor->pis()->count();
        $pisAtivos = $fornecedor->pis()->where('concluido', false)->count();

        return response()->json([
            'success' => true,
            'fornecedor' => [
                'razao_social' => $fornecedor->razao_social,
                'nome_fantasia' => $fornecedor->nome_fantasia,
                'cnpj' => $fornecedor->cnpj,
                'telefone' => $fornecedor->telefone,
                'email_contato' => $fornecedor->email_contato,
                'site' => $fornecedor->site,
                'praca' => $fornecedor->praca,
                'observacoes' => $fornecedor->observacoes,
                'enderecos' => $fornecedor->enderecos->map(function ($e) {
                    return [
                        'logradouro' => $e->logradouro,
                        'numero' => $e->numero,
                        'complemento' => $e->complemento,
                        'bairro' => $e->bairro,
                        'cidade' => $e->cidade,
                        'estado' => $e->estado,
                        'cep' => $e->cep,
                    ];
                }),
                'representantes_contato' => $fornecedor->representantes->map(function ($r) {
                    return [
                        'nome_completo' => $r->nome_completo,
                        'telefone_1' => $r->telefone_1,
                        'email_1' => $r->email_1,
                    ];
                }),
            ],
            'total_pis' => $totalPis,
            'pis_ativos' => $pisAtivos,
        ]);
    }

}