<?php

/* app/Http/Controllers/UserController.php */

namespace App\Http\Controllers;

use App\Mail\{FornecedorMail, RepresentanteMail};
use App\Models\{Cliente, Fornecedor, GenOp, RepresentanteUsuario, User};
use App\Services\MailRotator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Hash, Log};
use Illuminate\Validation\{Rule, Rules\Password};
use Spatie\Permission\Models\{Permission, Role};

class UserController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user->hasRole('admin');

        $query = User::with(['roles', 'genOp', 'fornecedor']);

        if ($user->hasRole('gerencial')) {
            $query->whereHas('roles', function ($q) {
                $q->whereIn('name', ['operacional', 'fornecedor']);
            });
        }

        if ($isAdmin) {
            if ($request->filled('status')) {
                if ($request->status === 'inativos') {
                    $query->onlyTrashed();
                } elseif ($request->status === 'ativos') {
                    $query->withoutTrashed();
                } else {
                    $query->withTrashed();
                }
            } else {
                $query->withTrashed();
            }

            $totalUsuarios = User::withTrashed()->count();
            $usuariosAtivos = User::whereNull('deleted_at')->count();
            $usuariosInativos = User::onlyTrashed()->count();
        } else {
            $query->whereNull('deleted_at');

            if ($user->hasRole('gerencial')) {
                $totalUsuarios = User::whereNull('deleted_at')
                    ->whereHas('roles', function ($q) {
                        $q->whereIn('name', ['operacional', 'fornecedor']);
                    })
                    ->count();
            } else {
                $totalUsuarios = User::whereNull('deleted_at')->count();
            }

            $usuariosAtivos = $totalUsuarios;
            $usuariosInativos = 0;
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('genOp', function ($sq) use ($search) {
                        $sq->where('nome_completo', 'like', "%{$search}%")
                            ->orWhere('telefone', 'like', "%{$search}%");
                    })
                    ->orWhereHas('fornecedor', function ($sq) use ($search) {
                        $sq->where('razao_social', 'like', "%{$search}%")
                            ->orWhere('cnpj', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        if ($user->hasRole('admin')) {
            $roles = Role::all();
        } else {
            $roles = Role::whereIn('name', ['operacional', 'fornecedor'])->get();
        }

        return view('users.index', compact(
            'users',
            'roles',
            'totalUsuarios',
            'usuariosAtivos',
            'usuariosInativos'
        ));
    }

    public function create()
    {
        $user = Auth::user();

        if ($user->hasRole('admin')) {
            $roles = Role::all();
        } else {
            $roles = Role::whereIn('name', ['operacional', 'fornecedor', 'representante'])->get();
        }

        $permissions = Permission::all()->groupBy(function ($permission) {
            return explode('.', $permission->name)[0];
        });

        return view('users.form', compact('roles', 'permissions'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        if ($user->hasRole('gerencial') && ! in_array($request->role, ['operacional', 'fornecedor', 'representante'])) {
            abort(403, 'Você não tem permissão para criar este tipo de usuário.');
        }

        $rules = [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->whereNull('deleted_at')],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)->letters()->mixedCase()->numbers()->symbols(),
            ],
            'role' => 'required|exists:roles,name',
        ];

        $messages = [
            'name.required' => 'O nome de usuário é obrigatório.',
            'name.string' => 'O nome de usuário deve ser um texto válido.',
            'name.max' => 'O nome de usuário não pode ter mais de 255 caracteres.',
            'email.required' => 'O endereço de email é obrigatório.',
            'email.email' => 'Por favor, informe um endereço de email válido.',
            'email.unique' => 'Este endereço de email já está cadastrado no sistema.',
            'password.required' => 'A senha é obrigatória.',
            'password.string' => 'A senha deve ser um texto válido.',
            'password.min' => 'A senha deve ter no mínimo 8 caracteres.',
            'password.confirmed' => 'As senhas não coincidem. Por favor, confirme a senha corretamente.',
            'password.regex' => 'A senha deve conter pelo menos uma letra maiúscula, uma minúscula, um número e um caractere especial.',
            'role.required' => 'O perfil de acesso é obrigatório.',
            'role.exists' => 'O perfil de acesso selecionado é inválido.',
        ];

        if (in_array($request->role, ['gerencial', 'operacional'])) {
            $rules['nome_completo'] = 'required|string|max:255';
            $rules['telefone'] = 'nullable|string|max:20';
            $rules['departamento'] = 'nullable|string|max:100';

            $messages['nome_completo.required'] = 'O nome completo é obrigatório.';
            $messages['nome_completo.string'] = 'O nome completo deve ser um texto válido.';
            $messages['nome_completo.max'] = 'O nome completo não pode ter mais de 255 caracteres.';
        }

        if ($request->role === 'representante') {
            $rules['nome_empresa'] = 'nullable|string|max:255';
            $rules['representante_cnpj'] = 'nullable|string|max:18';
            $rules['representante_telefone'] = 'nullable|string|max:20';
            $rules['representante_observacoes'] = 'nullable|string|max:1000';
        }

        if ($request->role === 'fornecedor') {
            $rules['razao_social'] = 'required|string|max:255';
            $rules['cnpj'] = 'required|string|size:18';
            $rules['nome_fantasia'] = 'nullable|string|max:255';
            $rules['nome_veiculo'] = 'nullable|string|max:255';
            $rules['fornecedor_telefone'] = 'nullable|string|max:20';
            $rules['fax'] = 'nullable|string|max:20';
            $rules['praca'] = 'nullable|string|max:255';
            $rules['email_contato'] = 'nullable|email|max:255';
            $rules['site'] = 'nullable|url|max:255';
            $rules['observacoes'] = 'nullable|string';
            $rules['cep'] = 'nullable|string|max:10';
            $rules['logradouro'] = 'nullable|string|max:255';
            $rules['numero'] = 'nullable|string|max:20';
            $rules['complemento'] = 'nullable|string|max:100';
            $rules['bairro'] = 'nullable|string|max:100';
            $rules['cidade'] = 'nullable|string|max:100';
            $rules['estado'] = 'nullable|string|size:2';

            $rules['representantes'] = 'nullable|array';
            $rules['representantes.*.nome_completo'] = 'required|string|max:255';
            $rules['representantes.*.cpf'] = 'nullable|string|max:14';
            $rules['representantes.*.telefone_1'] = 'nullable|string|max:20';
            $rules['representantes.*.telefone_2'] = 'nullable|string|max:20';
            $rules['representantes.*.email_1'] = 'nullable|email|max:255';
            $rules['representantes.*.email_2'] = 'nullable|email|max:255';

            $rules['dados_bancarios'] = 'nullable|array';
            $rules['dados_bancarios.*.banco'] = 'required|string|max:255';
            $rules['dados_bancarios.*.agencia'] = 'required|string|max:10';
            $rules['dados_bancarios.*.digito_agencia'] = 'nullable|string|max:2';
            $rules['dados_bancarios.*.conta'] = 'required|string|max:20';
            $rules['dados_bancarios.*.digito_conta'] = 'nullable|string|max:2';
            $rules['dados_bancarios.*.tipo_conta'] = 'required|in:corrente,poupanca';
            $rules['dados_bancarios.*.pix_tipo'] = 'nullable|in:cpf,cnpj,email,telefone,chave_aleatoria';
            $rules['dados_bancarios.*.pix_chave'] = 'nullable|string|max:255';
            $rules['dados_bancarios.*.titular'] = 'required|string|max:255';
            $rules['dados_bancarios.*.cpf_cnpj_titular'] = 'required|string|max:18';

            $messages['razao_social.required'] = 'A razão social é obrigatória.';
            $messages['cnpj.required'] = 'O CNPJ é obrigatório.';
            $messages['cnpj.size'] = 'O CNPJ deve ter exatamente 18 caracteres (formato: 00.000.000/0000-00).';
        }

        $validated = $request->validate($rules, $messages);

        if ($request->role === 'fornecedor') {
            if (! $this->validarCnpjUnico($validated['cnpj'])) {
                return back()->withInput()->withErrors(['cnpj' => 'Este CNPJ já está cadastrado no sistema.']);
            }
        }

        if ($request->role === 'representante' && ! empty($validated['representante_cnpj'])) {
            $cnpjLimpoRep = preg_replace('/[^0-9]/', '', $validated['representante_cnpj']);
            if (strlen($cnpjLimpoRep) === 14) {
                if (! $this->validarCnpjUnico($validated['representante_cnpj'])) {
                    return back()->withInput()->withErrors(['representante_cnpj' => 'Este CNPJ já está cadastrado no sistema.']);
                }
            }
        }

        $senhaPlainText = $validated['password'];

        DB::beginTransaction();
        try {
            $userExcluido = User::onlyTrashed()->where('email', $validated['email'])->first();

            if ($userExcluido) {
                $userExcluido->restore();
                $userExcluido->update([
                    'name' => $validated['name'],
                    'password' => Hash::make($validated['password']),
                ]);
                $newUser = $userExcluido;

                $newUser->syncRoles([$validated['role']]);
                $newUser->syncPermissions([]);

                if ($newUser->genOp) {
                    $newUser->genOp->forceDelete();
                }
                if ($newUser->fornecedor) {
                    $newUser->fornecedor->forceDelete();
                }
                if ($newUser->representanteUsuario) {
                    $newUser->representanteUsuario->forceDelete();
                }
            } else {
                $newUser = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                ]);

                $newUser->assignRole($validated['role']);
            }

            if ($request->filled('permissions')) {
                $newUser->givePermissionTo($request->permissions);
            }

            if (in_array($validated['role'], ['gerencial', 'operacional'])) {
                GenOp::create([
                    'user_id' => $newUser->id,
                    'nome_completo' => $validated['nome_completo'],
                    'telefone' => $validated['telefone'] ?? null,
                    'departamento' => $validated['departamento'] ?? null,
                ]);
            }

            if ($validated['role'] === 'fornecedor') {
                $cnpjLimpo = preg_replace('/[^0-9]/', '', $validated['cnpj']);

                $fornecedor = Fornecedor::create([
                    'user_id' => $newUser->id,
                    'razao_social' => $validated['razao_social'],
                    'cnpj' => $cnpjLimpo,
                    'nome_fantasia' => $validated['nome_fantasia'] ?? null,
                    'nome_veiculo' => $validated['nome_veiculo'] ?? null,
                    'telefone' => $validated['fornecedor_telefone'] ?? null,
                    'fax' => $validated['fax'] ?? null,
                    'praca' => $validated['praca'] ?? null,
                    'email_contato' => $validated['email_contato'] ?? null,
                    'site' => $validated['site'] ?? null,
                    'observacoes' => $validated['observacoes'] ?? null,
                ]);

                if ($request->filled('cep') || $request->filled('logradouro') || $request->filled('cidade')) {
                    $fornecedor->enderecos()->create([
                        'cep' => preg_replace('/[^0-9]/', '', $validated['cep'] ?? ''),
                        'logradouro' => $validated['logradouro'] ?? null,
                        'numero' => $validated['numero'] ?? null,
                        'complemento' => $validated['complemento'] ?? null,
                        'bairro' => $validated['bairro'] ?? null,
                        'cidade' => $validated['cidade'] ?? null,
                        'estado' => $validated['estado'] ?? null,
                    ]);
                }

                if ($request->has('representantes') && ! empty($request->representantes)) {
                    foreach ($request->representantes as $repData) {
                        $fornecedor->representantes()->create([
                            'nome_completo' => $repData['nome_completo'],
                            'cpf' => isset($repData['cpf']) ? preg_replace('/[^0-9]/', '', $repData['cpf']) : null,
                            'telefone_1' => $repData['telefone_1'] ?? null,
                            'telefone_2' => $repData['telefone_2'] ?? null,
                            'email_1' => $repData['email_1'] ?? null,
                            'email_2' => $repData['email_2'] ?? null,
                        ]);
                    }
                }

                if ($request->has('dados_bancarios') && ! empty($request->dados_bancarios)) {
                    foreach ($request->dados_bancarios as $dadosData) {
                        $fornecedor->dadosBancarios()->create([
                            'banco' => $dadosData['banco'],
                            'agencia' => $dadosData['agencia'],
                            'digito_agencia' => $dadosData['digito_agencia'] ?? null,
                            'conta' => $dadosData['conta'],
                            'digito_conta' => $dadosData['digito_conta'] ?? null,
                            'tipo_conta' => $dadosData['tipo_conta'],
                            'pix_tipo' => $dadosData['pix_tipo'] ?? null,
                            'pix_chave' => $dadosData['pix_chave'] ?? null,
                            'titular' => $dadosData['titular'],
                            'cpf_cnpj_titular' => preg_replace('/[^0-9]/', '', $dadosData['cpf_cnpj_titular']),
                        ]);
                    }
                }

                MailRotator::send(
                    new FornecedorMail($newUser, $validated['email'], $senhaPlainText, $validated['razao_social']),
                    $validated['email']
                );

                activity()
                    ->causedBy($newUser)
                    ->withProperties(['tipo' => 'senha_temporaria'])
                    ->log('Senha temporária enviada - requer troca obrigatória');
            }

            if ($validated['role'] === 'representante') {
                $cnpjLimpo = ! empty($validated['representante_cnpj'])
                    ? preg_replace('/[^0-9]/', '', $validated['representante_cnpj'])
                    : null;

                RepresentanteUsuario::create([
                    'user_id' => $newUser->id,
                    'nome_empresa' => $validated['nome_empresa'] ?? null,
                    'cnpj' => $cnpjLimpo,
                    'telefone' => $validated['representante_telefone'] ?? null,
                    'observacoes' => $validated['representante_observacoes'] ?? null,
                    'ativo' => true,
                ]);

                if ($newUser->genOp) {
                    $newUser->genOp->delete();
                }
                if ($newUser->fornecedor) {
                    $newUser->fornecedor->delete();
                }

                MailRotator::send(
                    new RepresentanteMail($newUser, $validated['email'], $senhaPlainText, $validated['name']),
                    $validated['email']
                );

                activity()
                    ->causedBy($newUser)
                    ->withProperties(['tipo' => 'senha_temporaria'])
                    ->log('Senha temporária enviada - requer troca obrigatória');
            }

            DB::commit();

            $mensagemSucesso = 'Usuário criado com sucesso!';
            if ($validated['role'] === 'fornecedor' || $validated['role'] === 'representante') {
                $mensagemSucesso .= ' Um email com as credenciais de acesso foi enviado.';
            }

            return redirect()->route('users.index')->with('success', $mensagemSucesso);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('security')->error('Erro ao criar usuário', [
                'user_id' => Auth::id(),
                'role' => $request->role ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withInput()->with('error', 'Erro ao criar usuário: '.$e->getMessage());
        }
    }

    public function edit($id)
    {
        $currentUser = Auth::user();

        $user = User::withTrashed()->find($id);

        if (! $user) {
            return redirect()->route('users.index')
                ->with('error', 'Usuário não encontrado.');
        }

        if ($user->trashed()) {
            return redirect()->route('users.index')
                ->with('error', 'Não é possível editar um usuário inativo. Restaure-o primeiro.');
        }

        if ($currentUser->hasRole('gerencial')) {
            if (! $user->hasAnyRole(['operacional', 'fornecedor', 'representante'])) {
                return redirect()->route('users.index')
                    ->with('error', 'Você não tem permissão para editar este usuário.');
            }
        }

        $user->load([
            'roles',
            'permissions',
            'genOp',
            'fornecedor',
            'fornecedor.enderecos',
            'fornecedor.representantes',
            'fornecedor.dadosBancarios',
            'representanteUsuario',
        ]);

        if ($currentUser->hasRole('admin')) {
            $roles = Role::all();
        } else {
            $roles = Role::whereIn('name', ['operacional', 'fornecedor', 'representante'])->get();
        }

        $permissions = Permission::all()->groupBy(function ($permission) {
            return explode('.', $permission->name)[0];
        });

        return view('users.form', compact('user', 'roles', 'permissions'));
    }

    public function update(Request $request, $id)
    {
        $currentUser = Auth::user();

        $user = User::withTrashed()->find($id);

        if (! $user) {
            return redirect()->route('users.index')
                ->with('error', 'Usuário não encontrado.');
        }

        if ($user->trashed()) {
            return redirect()->route('users.index')
                ->with('error', 'Não é possível editar um usuário inativo.');
        }

        if ($currentUser->hasRole('gerencial')) {
            if (! $user->hasAnyRole(['operacional', 'fornecedor', 'representante'])) {
                return redirect()->route('users.index')
                    ->with('error', 'Você não tem permissão para editar este usuário.');
            }
            if (! in_array($request->role, ['operacional', 'fornecedor', 'representante'])) {
                return redirect()->route('users.index')
                    ->with('error', 'Você não tem permissão para alterar para este tipo de usuário.');
            }
        }

        $rules = [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => [
                'nullable',
                'string',
                'confirmed',
                Password::min(8)->letters()->mixedCase()->numbers()->symbols(),
            ],
            'role' => 'required|exists:roles,name',
        ];

        $messages = [
            'name.required' => 'O nome de usuário é obrigatório.',
            'email.required' => 'O endereço de email é obrigatório.',
            'email.email' => 'Por favor, informe um endereço de email válido.',
            'email.unique' => 'Este endereço de email já está cadastrado no sistema.',
            'password.min' => 'A senha deve ter no mínimo 8 caracteres.',
            'password.confirmed' => 'As senhas não coincidem.',
            'password.regex' => 'A senha deve conter pelo menos uma letra maiúscula, uma minúscula, um número e um caractere especial.',
            'role.required' => 'O perfil de acesso é obrigatório.',
        ];

        if (in_array($request->role, ['gerencial', 'operacional'])) {
            $rules['nome_completo'] = 'required|string|max:255';
            $rules['telefone'] = 'nullable|string|max:20';
            $rules['departamento'] = 'nullable|string|max:100';
        }

        if ($request->role === 'representante') {
            $rules['nome_empresa'] = 'nullable|string|max:255';
            $rules['representante_cnpj'] = 'nullable|string|max:18';
            $rules['representante_telefone'] = 'nullable|string|max:20';
            $rules['representante_observacoes'] = 'nullable|string|max:1000';
        }

        if ($request->role === 'fornecedor') {
            $rules['razao_social'] = 'required|string|max:255';
            $rules['cnpj'] = 'required|string|size:18';
            $rules['nome_fantasia'] = 'nullable|string|max:255';
            $rules['nome_veiculo'] = 'nullable|string|max:255';
            $rules['fornecedor_telefone'] = 'nullable|string|max:20';
            $rules['fax'] = 'nullable|string|max:20';
            $rules['praca'] = 'nullable|string|max:255';
            $rules['email_contato'] = 'nullable|email|max:255';
            $rules['site'] = 'nullable|url|max:255';
            $rules['observacoes'] = 'nullable|string';
            $rules['cep'] = 'nullable|string|max:10';
            $rules['logradouro'] = 'nullable|string|max:255';
            $rules['numero'] = 'nullable|string|max:20';
            $rules['complemento'] = 'nullable|string|max:100';
            $rules['bairro'] = 'nullable|string|max:100';
            $rules['cidade'] = 'nullable|string|max:100';
            $rules['estado'] = 'nullable|string|size:2';

            $rules['representantes'] = 'nullable|array';
            $rules['representantes.*.id'] = 'nullable|exists:representantes,id';
            $rules['representantes.*.nome_completo'] = 'required|string|max:255';
            $rules['representantes.*.cpf'] = 'nullable|string|max:14';
            $rules['representantes.*.telefone_1'] = 'nullable|string|max:20';
            $rules['representantes.*.telefone_2'] = 'nullable|string|max:20';
            $rules['representantes.*.email_1'] = 'nullable|email|max:255';
            $rules['representantes.*.email_2'] = 'nullable|email|max:255';

            $rules['dados_bancarios'] = 'nullable|array';
            $rules['dados_bancarios.*.id'] = 'nullable|exists:dados_bancarios,id';
            $rules['dados_bancarios.*.banco'] = 'required|string|max:255';
            $rules['dados_bancarios.*.agencia'] = 'required|string|max:10';
            $rules['dados_bancarios.*.digito_agencia'] = 'nullable|string|max:2';
            $rules['dados_bancarios.*.conta'] = 'required|string|max:20';
            $rules['dados_bancarios.*.digito_conta'] = 'nullable|string|max:2';
            $rules['dados_bancarios.*.tipo_conta'] = 'required|in:corrente,poupanca';
            $rules['dados_bancarios.*.pix_tipo'] = 'nullable|in:cpf,cnpj,email,telefone,chave_aleatoria';
            $rules['dados_bancarios.*.pix_chave'] = 'nullable|string|max:255';
            $rules['dados_bancarios.*.titular'] = 'required|string|max:255';
            $rules['dados_bancarios.*.cpf_cnpj_titular'] = 'required|string|max:18';
        }

        $validated = $request->validate($rules, $messages);

        if ($request->role === 'fornecedor') {
            $fornecedorIdIgnorar = $user->fornecedor ? $user->fornecedor->id : null;
            $cnpjAtual = $user->fornecedor ? $user->fornecedor->cnpj : null;
            $cnpjNovo = preg_replace('/[^0-9]/', '', $validated['cnpj']);

            if ($cnpjAtual !== $cnpjNovo) {
                if (! $this->validarCnpjUnico($validated['cnpj'], $fornecedorIdIgnorar)) {
                    return back()->withInput()->withErrors(['cnpj' => 'Este CNPJ já está cadastrado no sistema.']);
                }
            }
        }

        if ($request->role === 'representante' && ! empty($validated['representante_cnpj'])) {
            $cnpjLimpoRep = preg_replace('/[^0-9]/', '', $validated['representante_cnpj']);
            $representanteIdIgnorar = $user->representanteUsuario ? $user->representanteUsuario->id : null;
            $cnpjAtualRep = $user->representanteUsuario ? $user->representanteUsuario->cnpj : null;

            if ($cnpjAtualRep !== $cnpjLimpoRep && strlen($cnpjLimpoRep) === 14) {
                if (! $this->validarCnpjUnico($validated['representante_cnpj'], null, $representanteIdIgnorar)) {
                    return back()->withInput()->withErrors(['representante_cnpj' => 'Este CNPJ já está cadastrado no sistema.']);
                }
            }
        }

        DB::beginTransaction();
        try {
            $updateData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
            ];
            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($validated['password']);
            }
            $user->update($updateData);

            $user->syncRoles([$validated['role']]);
            $user->syncPermissions($request->permissions ?? []);

            if (in_array($validated['role'], ['gerencial', 'operacional'])) {
                $user->genOp()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'nome_completo' => $validated['nome_completo'],
                        'telefone' => $validated['telefone'] ?? null,
                        'departamento' => $validated['departamento'] ?? null,
                    ]
                );

                if ($user->fornecedor) {
                    $this->softDeleteFornecedorRelacionados($user->fornecedor);
                    $user->fornecedor->delete();
                }

                if ($user->representanteUsuario) {
                    $user->representanteUsuario->delete();
                }
            }

            if ($validated['role'] === 'fornecedor') {
                $cnpjLimpo = preg_replace('/[^0-9]/', '', $validated['cnpj']);

                $fornecedor = $user->fornecedor()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'razao_social' => $validated['razao_social'],
                        'cnpj' => $cnpjLimpo,
                        'nome_fantasia' => $validated['nome_fantasia'] ?? null,
                        'nome_veiculo' => $validated['nome_veiculo'] ?? null,
                        'telefone' => $validated['fornecedor_telefone'] ?? null,
                        'fax' => $validated['fax'] ?? null,
                        'praca' => $validated['praca'] ?? null,
                        'email_contato' => $validated['email_contato'] ?? null,
                        'site' => $validated['site'] ?? null,
                        'observacoes' => $validated['observacoes'] ?? null,
                    ]
                );

                $fornecedor->enderecos()->delete();
                if ($request->filled('cep') || $request->filled('logradouro') || $request->filled('cidade')) {
                    $fornecedor->enderecos()->create([
                        'cep' => preg_replace('/[^0-9]/', '', $validated['cep'] ?? ''),
                        'logradouro' => $validated['logradouro'] ?? null,
                        'numero' => $validated['numero'] ?? null,
                        'complemento' => $validated['complemento'] ?? null,
                        'bairro' => $validated['bairro'] ?? null,
                        'cidade' => $validated['cidade'] ?? null,
                        'estado' => $validated['estado'] ?? null,
                    ]);
                }

                $representantesIds = [];
                if ($request->has('representantes') && ! empty($request->representantes)) {
                    foreach ($request->representantes as $repData) {
                        if (isset($repData['id']) && $repData['id']) {
                            $representante = $fornecedor->representantes()->find($repData['id']);
                            if ($representante) {
                                $representante->update([
                                    'nome_completo' => $repData['nome_completo'],
                                    'cpf' => isset($repData['cpf']) ? preg_replace('/[^0-9]/', '', $repData['cpf']) : null,
                                    'telefone_1' => $repData['telefone_1'] ?? null,
                                    'telefone_2' => $repData['telefone_2'] ?? null,
                                    'email_1' => $repData['email_1'] ?? null,
                                    'email_2' => $repData['email_2'] ?? null,
                                ]);
                                $representantesIds[] = $representante->id;
                            }
                        } else {
                            $novoRepresentante = $fornecedor->representantes()->create([
                                'nome_completo' => $repData['nome_completo'],
                                'cpf' => isset($repData['cpf']) ? preg_replace('/[^0-9]/', '', $repData['cpf']) : null,
                                'telefone_1' => $repData['telefone_1'] ?? null,
                                'telefone_2' => $repData['telefone_2'] ?? null,
                                'email_1' => $repData['email_1'] ?? null,
                                'email_2' => $repData['email_2'] ?? null,
                            ]);
                            $representantesIds[] = $novoRepresentante->id;
                        }
                    }
                }
                $fornecedor->representantes()->whereNotIn('id', $representantesIds)->delete();

                $dadosBancariosIds = [];
                if ($request->has('dados_bancarios') && ! empty($request->dados_bancarios)) {
                    foreach ($request->dados_bancarios as $dadosData) {
                        if (isset($dadosData['id']) && $dadosData['id']) {
                            $dadosBancario = $fornecedor->dadosBancarios()->find($dadosData['id']);
                            if ($dadosBancario) {
                                $dadosBancario->update([
                                    'banco' => $dadosData['banco'],
                                    'agencia' => $dadosData['agencia'],
                                    'digito_agencia' => $dadosData['digito_agencia'] ?? null,
                                    'conta' => $dadosData['conta'],
                                    'digito_conta' => $dadosData['digito_conta'] ?? null,
                                    'tipo_conta' => $dadosData['tipo_conta'],
                                    'pix_tipo' => $dadosData['pix_tipo'] ?? null,
                                    'pix_chave' => $dadosData['pix_chave'] ?? null,
                                    'titular' => $dadosData['titular'],
                                    'cpf_cnpj_titular' => preg_replace('/[^0-9]/', '', $dadosData['cpf_cnpj_titular']),
                                ]);
                                $dadosBancariosIds[] = $dadosBancario->id;
                            }
                        } else {
                            $novoDadosBancario = $fornecedor->dadosBancarios()->create([
                                'banco' => $dadosData['banco'],
                                'agencia' => $dadosData['agencia'],
                                'digito_agencia' => $dadosData['digito_agencia'] ?? null,
                                'conta' => $dadosData['conta'],
                                'digito_conta' => $dadosData['digito_conta'] ?? null,
                                'tipo_conta' => $dadosData['tipo_conta'],
                                'pix_tipo' => $dadosData['pix_tipo'] ?? null,
                                'pix_chave' => $dadosData['pix_chave'] ?? null,
                                'titular' => $dadosData['titular'],
                                'cpf_cnpj_titular' => preg_replace('/[^0-9]/', '', $dadosData['cpf_cnpj_titular']),
                            ]);
                            $dadosBancariosIds[] = $novoDadosBancario->id;
                        }
                    }
                }
                $fornecedor->dadosBancarios()->whereNotIn('id', $dadosBancariosIds)->delete();

                if ($user->genOp) {
                    $user->genOp->delete();
                }

                if ($user->representanteUsuario) {
                    $user->representanteUsuario->delete();
                }
            }

            if ($validated['role'] === 'representante') {
                $cnpjLimpo = ! empty($validated['representante_cnpj'])
                    ? preg_replace('/[^0-9]/', '', $validated['representante_cnpj'])
                    : null;

                $user->representanteUsuario()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'nome_empresa' => $validated['nome_empresa'] ?? null,
                        'cnpj' => $cnpjLimpo,
                        'telefone' => $validated['representante_telefone'] ?? null,
                        'observacoes' => $validated['representante_observacoes'] ?? null,
                    ]
                );

                if ($user->genOp) {
                    $user->genOp->delete();
                }
                if ($user->fornecedor) {
                    $this->softDeleteFornecedorRelacionados($user->fornecedor);
                    $user->fornecedor->delete();
                }
            }

            if ($validated['role'] === 'admin') {
                if ($user->genOp) {
                    $user->genOp->delete();
                }
                if ($user->fornecedor) {
                    $this->softDeleteFornecedorRelacionados($user->fornecedor);
                    $user->fornecedor->delete();
                }
                if ($user->representanteUsuario) {
                    $user->representanteUsuario->delete();
                }
            }

            DB::commit();

            return redirect()->route('users.index')->with('success', 'Usuário atualizado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('security')->error('Erro ao atualizar usuário', [
                'target_user_id' => $id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withInput()->with('error', 'Erro ao atualizar usuário: '.$e->getMessage());
        }
    }

    public function destroy(User $user)
    {
        $currentUser = Auth::user();

        if ($currentUser->hasRole('gerencial')) {
            if (! $user->hasAnyRole(['operacional', 'fornecedor'])) {
                abort(403, 'Você não tem permissão para excluir este usuário.');
            }
        }

        if ($user->id === $currentUser->id) {
            return back()->with('error', 'Você não pode excluir sua própria conta.');
        }

        $dependencias = $this->verificarDependencias($user);

        if (! $dependencias['pode_inativar']) {
            return back()
                ->with('error', $dependencias['mensagem'])
                ->with('error_type', 'bloqueio_dependencia')
                ->with('error_details', $dependencias['detalhes'] ?? []);
        }

        DB::beginTransaction();
        try {
            if ($user->hasRole('fornecedor') && $user->fornecedor) {
                $this->softDeleteFornecedorCompleto($user->fornecedor);
            }

            if ($user->hasAnyRole(['gerencial', 'operacional']) && $user->genOp) {
                $this->softDeleteGenOpCompleto($user->genOp);
            }

            $user->delete();

            DB::commit();

            return redirect()->route('users.index')->with('success', 'Usuário inativado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('security')->error('Erro ao inativar usuário', [
                'target_user_id' => $user->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Erro ao inativar usuário: '.$e->getMessage());
        }
    }

    public function restore($id)
    {
        $currentUser = Auth::user();

        if (! $currentUser->hasRole('admin')) {
            abort(403, 'Apenas administradores podem restaurar usuários.');
        }

        DB::beginTransaction();
        try {
            $user = User::withTrashed()->findOrFail($id);

            $user->restore();

            if ($user->hasRole('fornecedor') && $user->fornecedor()->withTrashed()->exists()) {
                $fornecedor = $user->fornecedor()->withTrashed()->first();
                $this->restaurarFornecedorCompleto($fornecedor);
            }

            if ($user->hasAnyRole(['gerencial', 'operacional']) && $user->genOp()->withTrashed()->exists()) {
                $genOp = $user->genOp()->withTrashed()->first();
                $this->restaurarGenOpCompleto($genOp);
            }

            DB::commit();

            return redirect()->route('users.index')->with('success', 'Usuário restaurado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('security')->error('Erro ao restaurar usuário', [
                'target_user_id' => $id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Erro ao restaurar usuário: '.$e->getMessage());
        }
    }

    public function getFieldsByRole(Request $request)
    {
        $roleName = $request->input('role');
        $role = Role::where('name', $roleName)->first();

        if (! $role) {
            return response()->json([
                'success' => false,
                'message' => 'Role não encontrada',
            ], 404);
        }

        $permissions = $role->permissions->pluck('name')->toArray();

        return response()->json([
            'success' => true,
            'permissions' => $permissions,
        ]);
    }

    public function destroyRepresentante(User $user, $id)
    {
        $currentUser = Auth::user();

        if ($currentUser->hasRole('gerencial')) {
            if (! $user->hasAnyRole(['operacional', 'fornecedor'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para editar este usuário',
                ], 403);
            }
        }

        if (! $user->hasRole('fornecedor') || ! $user->fornecedor) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário não é um fornecedor',
            ], 403);
        }

        try {
            $representante = $user->fornecedor->representantes()->findOrFail($id);

            $nomeRepresentante = $representante->nome_completo;
            $representante->delete();

            activity()
                ->causedBy($currentUser)
                ->performedOn($representante)
                ->log('Representante removido do usuário '.$user->name.': '.$nomeRepresentante);

            return response()->json([
                'success' => true,
                'message' => 'Representante removido com sucesso!',
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::channel('security')->error('Representante não encontrado ao remover', ['id' => $id, 'user_id' => $user->id]);

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

    public function destroyDadosBancarios(User $user, $id)
    {
        $currentUser = Auth::user();

        if ($currentUser->hasRole('gerencial')) {
            if (! $user->hasAnyRole(['operacional', 'fornecedor'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para editar este usuário',
                ], 403);
            }
        }

        if (! $user->hasRole('fornecedor') || ! $user->fornecedor) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário não é um fornecedor',
            ], 403);
        }

        try {
            $dadosBancarios = $user->fornecedor->dadosBancarios()->findOrFail($id);

            $bancoNome = $dadosBancarios->banco;
            $dadosBancarios->delete();

            activity()
                ->causedBy($currentUser)
                ->performedOn($dadosBancarios)
                ->log('Dados bancários removidos do usuário '.$user->name.': '.$bancoNome);

            return response()->json([
                'success' => true,
                'message' => 'Dados bancários removidos com sucesso!',
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::channel('security')->error('Dados bancários não encontrados ao remover', ['id' => $id, 'user_id' => $user->id]);

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

    private function verificarDependencias(User $user)
    {
        $resultado = [
            'pode_inativar' => true,
            'mensagem' => '',
            'detalhes' => [],
        ];

        if ($user->hasRole('fornecedor') && $user->fornecedor) {
            $pisAtivos = $user->fornecedor->pis()
                ->where('concluido', false)
                ->whereNull('deleted_at')
                ->with(['gerencial', 'operacionais'])
                ->get();

            if ($pisAtivos->count() > 0) {
                $resultado['pode_inativar'] = false;
                $resultado['mensagem'] = "Não é possível inativar este fornecedor. Existem {$pisAtivos->count()} PI(s) em andamento vinculado(s) a ele. Por favor, conclua ou transfira os PIs antes de inativar o fornecedor.";
                $resultado['detalhes'] = [
                    'tipo' => 'fornecedor',
                    'quantidade_pis' => $pisAtivos->count(),
                    'pis' => $pisAtivos->map(function ($pi) {
                        $operacionais = $pi->operacionais->pluck('nome_completo')->implode(', ');

                        return [
                            'numero_pi' => $pi->numero_pi,
                            'gerencial' => $pi->gerencial->nome_completo ?? 'N/A',
                            'operacionais' => $operacionais ?: 'N/A',
                            'data_cadastro' => $pi->data_cadastro->format('d/m/Y'),
                        ];
                    })->toArray(),
                ];

                return $resultado;
            }
        }

        if ($user->hasRole('operacional') && $user->genOp) {
            $pisAtribuidos = $user->genOp->pisAtribuidos()
                ->where('pis.concluido', false)
                ->with(['fornecedor', 'gerencial'])
                ->get();

            if ($pisAtribuidos->count() > 0) {
                $resultado['pode_inativar'] = false;
                $resultado['mensagem'] = "Não é possível inativar este operacional. Existem {$pisAtribuidos->count()} PI(s) atribuído(s) a ele. Por favor, conclua ou transfira os PIs antes de inativar o operacional.";
                $resultado['detalhes'] = [
                    'tipo' => 'operacional',
                    'quantidade_pis' => $pisAtribuidos->count(),
                    'pis' => $pisAtribuidos->map(function ($pi) {
                        return [
                            'numero_pi' => $pi->numero_pi,
                            'fornecedor' => $pi->fornecedor->razao_social ?? 'N/A',
                            'gerencial' => $pi->gerencial->nome_completo ?? 'N/A',
                            'data_cadastro' => $pi->data_cadastro->format('d/m/Y'),
                        ];
                    })->toArray(),
                ];

                return $resultado;
            }

            $atribuicoesClientesAtivas = $user->genOp->atribuicoesClientes()
                ->where('ativo', true)
                ->whereNull('deleted_at')
                ->with('cliente')
                ->get();

            if ($atribuicoesClientesAtivas->count() > 0) {
                $resultado['pode_inativar'] = false;
                $resultado['mensagem'] = "Não é possível inativar este operacional. Existem {$atribuicoesClientesAtivas->count()} cliente(s) vinculado(s) a ele. Por favor, remova as atribuições de clientes antes de inativar o operacional.";
                $resultado['detalhes'] = [
                    'tipo' => 'operacional_clientes',
                    'quantidade_clientes' => $atribuicoesClientesAtivas->count(),
                    'clientes' => $atribuicoesClientesAtivas->map(function ($atribuicao) {
                        return [
                            'razao_social' => $atribuicao->cliente->razao_social ?? 'N/A',
                            'cnpj' => $atribuicao->cliente->cnpj ?? 'N/A',
                            'data_atribuicao' => $atribuicao->data_atribuicao->format('d/m/Y'),
                        ];
                    })->toArray(),
                ];

                return $resultado;
            }
        }

        if ($user->hasRole('representante') && $user->representanteUsuario) {
            $vinculosAtivos = $user->representanteUsuario->vinculosAtivos()->count();

            if ($vinculosAtivos > 0) {
                $resultado['pode_inativar'] = false;
                $resultado['mensagem'] = "Não é possível inativar este representante. Existem {$vinculosAtivos} vínculo(s) ativo(s) com fornecedores. Desvincule os fornecedores antes de inativar.";
                $resultado['detalhes'] = [
                    'tipo' => 'representante_vinculos',
                    'quantidade_vinculos' => $vinculosAtivos,
                ];

                return $resultado;
            }
        }

        return $resultado;
    }

    private function softDeleteFornecedorCompleto(Fornecedor $fornecedor)
    {
        $fornecedor->enderecos()->delete();
        $fornecedor->representantes()->delete();
        $fornecedor->dadosBancarios()->delete();
        $fornecedor->delete();
    }

    private function softDeleteFornecedorRelacionados(Fornecedor $fornecedor)
    {
        $fornecedor->enderecos()->delete();
        $fornecedor->representantes()->delete();
        $fornecedor->dadosBancarios()->delete();
    }

    private function softDeleteGenOpCompleto(GenOp $genOp)
    {
        $genOp->atribuicoes()->where('ativo', true)->update([
            'ativo' => false,
            'data_desatribuicao' => now(),
            'motivo_mudanca' => 'Usuário desativado',
        ]);
        $genOp->atribuicoes()->delete();
        $genOp->delete();
    }

    private function restaurarFornecedorCompleto(Fornecedor $fornecedor)
    {
        $fornecedor->restore();
        $fornecedor->enderecos()->withTrashed()->restore();
        $fornecedor->representantes()->withTrashed()->restore();
        $fornecedor->dadosBancarios()->withTrashed()->restore();
    }

    private function restaurarGenOpCompleto(GenOp $genOp)
    {
        $genOp->restore();
        $genOp->atribuicoes()->withTrashed()->restore();
    }

    private function validarCnpjUnico($cnpj, $fornecedorIdIgnorar = null, $representanteIdIgnorar = null)
    {
        $cnpjLimpo = preg_replace('/[^0-9]/', '', $cnpj);

        $queryFornecedor = Fornecedor::where('cnpj', $cnpjLimpo);
        if ($fornecedorIdIgnorar) {
            $queryFornecedor->where('id', '!=', $fornecedorIdIgnorar);
        }

        $queryCliente = Cliente::where('cnpj', $cnpjLimpo);

        $queryRepresentante = RepresentanteUsuario::where('cnpj', $cnpjLimpo);
        if ($representanteIdIgnorar) {
            $queryRepresentante->where('id', '!=', $representanteIdIgnorar);
        }

        return ! $queryFornecedor->exists() && ! $queryCliente->exists() && ! $queryRepresentante->exists();
    }

    public function forceDestroy($id)
    {
        $currentUser = Auth::user();

        if (! $currentUser->hasRole('admin')) {
            abort(403, 'Apenas administradores podem excluir permanentemente usuários.');
        }

        DB::beginTransaction();
        try {
            $user = User::withTrashed()->findOrFail($id);

            if (! $user->trashed()) {
                return back()->with('error', 'O usuário deve estar inativo antes de ser excluído permanentemente.');
            }

            $dependencias = $this->verificarDependenciasForceDelete($user);

            if (! $dependencias['pode_excluir']) {
                return back()
                    ->with('error', $dependencias['mensagem'])
                    ->with('error_type', 'bloqueio_force_delete')
                    ->with('error_details', $dependencias['detalhes'] ?? []);
            }

            $userName = $user->name;

            if ($user->hasRole('fornecedor') && $user->fornecedor) {
                $fornecedor = $user->fornecedor()->withTrashed()->first();
                if ($fornecedor) {
                    $fornecedor->enderecos()->withTrashed()->forceDelete();
                    $fornecedor->representantes()->withTrashed()->forceDelete();
                    $fornecedor->dadosBancarios()->withTrashed()->forceDelete();

                    $fornecedor->documentosEnviados()->withTrashed()->each(function ($doc) {
                        if ($doc->piDocumentos()->withTrashed()->count() === 0) {
                            foreach ($doc->arquivos()->withTrashed()->get() as $arquivo) {
                                $caminhos = [
                                    storage_path('app/private/' . $arquivo->file_path),
                                    storage_path('app/' . $arquivo->file_path),
                                ];
                                foreach ($caminhos as $caminho) {
                                    if (file_exists($caminho)) {
                                        unlink($caminho);
                                        break;
                                    }
                                }
                            }
                            $doc->arquivos()->withTrashed()->forceDelete();
                            $doc->validacoes()->withTrashed()->forceDelete();
                            $doc->forceDelete();
                        }
                    });

                    $fornecedor->conversasComoParticipante()->withTrashed()->forceDelete();
                    $fornecedor->mensagensEnviadas()->withTrashed()->forceDelete();

                    $fornecedor->forceDelete();
                }
            }

            if ($user->hasAnyRole(['gerencial', 'operacional']) && $user->genOp) {
                $genOp = $user->genOp()->withTrashed()->first();
                if ($genOp) {
                    $genOp->atribuicoes()->withTrashed()->forceDelete();
                    $genOp->validacoes()->withTrashed()->forceDelete();
                    $genOp->conversasComoParticipante()->withTrashed()->forceDelete();
                    $genOp->mensagensEnviadas()->withTrashed()->forceDelete();
                    $genOp->forceDelete();
                }
            }

            $user->conversasComoCriador()->withTrashed()->forceDelete();
            $user->participacoes()->withTrashed()->forceDelete();
            $user->mensagensEnviadas()->withTrashed()->forceDelete();

            $user->forceDelete();

            DB::commit();

            return redirect()->route('users.index')->with('success', "Usuário '{$userName}' excluído permanentemente com sucesso!");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('security')->error('Erro ao excluir permanentemente usuário', [
                'user_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Erro ao excluir permanentemente usuário: '.$e->getMessage());
        }
    }

    private function verificarDependenciasForceDelete(User $user)
    {
        $resultado = [
            'pode_excluir' => true,
            'mensagem' => '',
            'detalhes' => [],
        ];

        if ($user->hasRole('fornecedor') && $user->fornecedor) {
            $pisVinculados = $user->fornecedor->pis()
                ->withTrashed()
                ->with(['gerencial', 'operacionais', 'cliente'])
                ->get();

            if ($pisVinculados->count() > 0) {
                $resultado['pode_excluir'] = false;
                $resultado['mensagem'] = "Não é possível excluir permanentemente este fornecedor. Existem {$pisVinculados->count()} PI(s) vinculado(s) a ele no histórico do sistema. A exclusão permanente comprometeria a integridade dos dados.";
                $resultado['detalhes'] = [
                    'tipo' => 'fornecedor_pis',
                    'quantidade_pis' => $pisVinculados->count(),
                    'pis' => $pisVinculados->map(function ($pi) {
                        $operacionais = $pi->operacionais->pluck('nome_completo')->implode(', ');

                        return [
                            'numero_pi' => $pi->numero_pi,
                            'cliente' => $pi->cliente->razao_social ?? 'N/A',
                            'gerencial' => $pi->gerencial->nome_completo ?? 'N/A',
                            'operacionais' => $operacionais ?: 'N/A',
                            'data_cadastro' => $pi->data_cadastro->format('d/m/Y'),
                            'concluido' => $pi->concluido ? 'Sim' : 'Não',
                            'status' => $pi->trashed() ? 'Inativo' : 'Ativo',
                        ];
                    })->toArray(),
                ];

                return $resultado;
            }

            $documentosVinculados = $user->fornecedor->documentosEnviados()
                ->withTrashed()
                ->whereHas('piDocumentos')
                ->with('piDocumentos.pi')
                ->get();

            if ($documentosVinculados->count() > 0) {
                $resultado['pode_excluir'] = false;
                $resultado['mensagem'] = "Não é possível excluir permanentemente este fornecedor. Existem {$documentosVinculados->count()} documento(s) vinculado(s) a PIs no sistema. A exclusão permanente comprometeria a integridade dos dados.";
                $resultado['detalhes'] = [
                    'tipo' => 'fornecedor_documentos',
                    'quantidade_documentos' => $documentosVinculados->count(),
                    'documentos' => $documentosVinculados->map(function ($doc) {
                        return [
                            'tipo' => $doc->tipoDocumento->nome ?? 'N/A',
                            'data_envio' => $doc->data_envio->format('d/m/Y'),
                            'pis_vinculados' => $doc->piDocumentos->count(),
                        ];
                    })->toArray(),
                ];

                return $resultado;
            }
        }

        if ($user->hasAnyRole(['gerencial', 'operacional']) && $user->genOp) {
            $pisComoGerencial = $user->genOp->pisComoGerencial()
                ->withTrashed()
                ->with(['fornecedor', 'cliente'])
                ->get();

            if ($pisComoGerencial->count() > 0) {
                $resultado['pode_excluir'] = false;
                $resultado['mensagem'] = "Não é possível excluir permanentemente este gerencial. Existem {$pisComoGerencial->count()} PI(s) no histórico onde ele é/foi o gerencial responsável. A exclusão permanente comprometeria a integridade dos dados.";
                $resultado['detalhes'] = [
                    'tipo' => 'gerencial_pis',
                    'quantidade_pis' => $pisComoGerencial->count(),
                    'pis' => $pisComoGerencial->map(function ($pi) {
                        return [
                            'numero_pi' => $pi->numero_pi,
                            'fornecedor' => $pi->fornecedor->razao_social ?? 'N/A',
                            'cliente' => $pi->cliente->razao_social ?? 'N/A',
                            'data_cadastro' => $pi->data_cadastro->format('d/m/Y'),
                            'concluido' => $pi->concluido ? 'Sim' : 'Não',
                            'status' => $pi->trashed() ? 'Inativo' : 'Ativo',
                        ];
                    })->toArray(),
                ];

                return $resultado;
            }

            $pisAtribuidos = $user->genOp->pisAtribuidos()
                ->withTrashed()
                ->with(['fornecedor', 'cliente'])
                ->get();

            if ($pisAtribuidos->count() > 0) {
                $resultado['pode_excluir'] = false;
                $resultado['mensagem'] = "Não é possível excluir permanentemente este operacional. Existem {$pisAtribuidos->count()} PI(s) no histórico onde ele foi atribuído. A exclusão permanente comprometeria a integridade dos dados.";
                $resultado['detalhes'] = [
                    'tipo' => 'operacional_pis',
                    'quantidade_pis' => $pisAtribuidos->count(),
                    'pis' => $pisAtribuidos->map(function ($pi) {
                        return [
                            'numero_pi' => $pi->numero_pi,
                            'fornecedor' => $pi->fornecedor->razao_social ?? 'N/A',
                            'cliente' => $pi->cliente->razao_social ?? 'N/A',
                            'data_cadastro' => $pi->data_cadastro->format('d/m/Y'),
                            'concluido' => $pi->concluido ? 'Sim' : 'Não',
                            'status' => $pi->trashed() ? 'Inativo' : 'Ativo',
                        ];
                    })->toArray(),
                ];

                return $resultado;
            }

            $validacoesRealizadas = $user->genOp->validacoes()
                ->withTrashed()
                ->count();

            if ($validacoesRealizadas > 0) {
                $resultado['pode_excluir'] = false;
                $resultado['mensagem'] = "Não é possível excluir permanentemente este operacional. Existem {$validacoesRealizadas} validação(ões) de documentos realizadas por ele no histórico. A exclusão permanente comprometeria a rastreabilidade das validações.";
                $resultado['detalhes'] = [
                    'tipo' => 'operacional_validacoes',
                    'quantidade_validacoes' => $validacoesRealizadas,
                ];

                return $resultado;
            }
        }

        return $resultado;
    }
}