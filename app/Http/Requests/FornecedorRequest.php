<?php

/* app/Http/Requests/FornecedorRequest.php */

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FornecedorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'gerencial', 'operacional']);
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('put') || $this->isMethod('patch');
        $emailUnique = Rule::unique('users', 'email')->whereNull('deleted_at');

        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $fornecedor = $this->route('fornecedor');
            if ($fornecedor && $fornecedor->user_id) {
                $emailUnique->ignore($fornecedor->user_id);
            }
        }

        return [
            'razao_social' => 'required|string|max:255',
            'cnpj' => 'required|string|size:18',
            'email_contato' => ['required', 'email', 'max:255', $emailUnique],
            'cep' => 'required|string|max:10',
            'logradouro' => 'required|string|max:255',
            'numero' => 'required|string|max:20',
            'bairro' => 'required|string|max:100',
            'cidade' => 'required|string|max:100',
            'estado' => 'required|string|size:2',

            'nome_fantasia' => 'nullable|string|max:255',
            'nome_veiculo' => 'nullable|string|max:255',
            'telefone' => 'nullable|string|max:20',
            'fax' => 'nullable|string|max:20',
            'praca' => 'nullable|string|max:255',
            'site' => 'nullable|url|max:255',
            'observacoes' => 'nullable|string',
            'complemento' => 'nullable|string|max:100',

            'representantes' => 'nullable|array',
            'representantes.*.id' => 'nullable|exists:representantes,id',
            'representantes.*.nome_completo' => ($isUpdate ? 'required' : 'nullable') . '|string|max:255',
            'representantes.*.cpf' => 'nullable|string|max:14',
            'representantes.*.telefone_1' => 'nullable|string|max:20',
            'representantes.*.telefone_2' => 'nullable|string|max:20',
            'representantes.*.email_1' => 'nullable|email|max:255',
            'representantes.*.email_2' => 'nullable|email|max:255',

            'dados_bancarios' => 'nullable|array',
            'dados_bancarios.*.id' => 'nullable|exists:dados_bancarios,id',
            'dados_bancarios.*.banco' => ($isUpdate ? 'required' : 'nullable') . '|string|max:255',
            'dados_bancarios.*.agencia' => ($isUpdate ? 'required' : 'nullable') . '|string|max:10',
            'dados_bancarios.*.digito_agencia' => 'nullable|string|max:2',
            'dados_bancarios.*.conta' => ($isUpdate ? 'required' : 'nullable') . '|string|max:20',
            'dados_bancarios.*.digito_conta' => 'nullable|string|max:2',
            'dados_bancarios.*.tipo_conta' => ($isUpdate ? 'required' : 'nullable') . '|in:corrente,poupanca',
            'dados_bancarios.*.pix_tipo' => 'nullable|in:cpf,cnpj,email,telefone,chave_aleatoria',
            'dados_bancarios.*.pix_chave' => 'nullable|string|max:255',
            'dados_bancarios.*.titular' => ($isUpdate ? 'required' : 'nullable') . '|string|max:255',
            'dados_bancarios.*.cpf_cnpj_titular' => ($isUpdate ? 'required' : 'nullable') . '|string|max:18',
        ];
    }

    public function messages(): array
    {
        return [
            'razao_social.required' => 'A razão social é obrigatória.',
            'cnpj.required' => 'O CNPJ é obrigatório.',
            'cnpj.size' => 'O CNPJ deve ter 18 caracteres (com formatação).',
            'email_contato.required' => 'O email de contato é obrigatório.',
            'email_contato.email' => 'O email de contato deve ser válido.',
            'email_contato.unique' => 'Este email já está cadastrado no sistema.',
            'cep.required' => 'O CEP é obrigatório.',
            'logradouro.required' => 'O logradouro é obrigatório.',
            'numero.required' => 'O número é obrigatório.',
            'bairro.required' => 'O bairro é obrigatório.',
            'cidade.required' => 'A cidade é obrigatória.',
            'estado.required' => 'O estado é obrigatório.',
        ];
    }
}
