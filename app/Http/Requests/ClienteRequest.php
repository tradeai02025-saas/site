<?php

/* app/Http/Requests/ClienteRequest.php */

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'gerencial', 'operacional']);
    }

    public function rules(): array
    {
        $rules = [
            'razao_social' => 'required|string|max:255',
            'nome_fantasia' => 'nullable|string|max:255',
            'nome_cliente' => 'nullable|string|max:255',
            'cnpj' => 'required|string|size:18',
            'inscricao_estadual' => 'nullable|string|max:50',
            'inscricao_municipal' => 'nullable|string|max:50',
            'praca' => 'nullable|string|max:255',
            'telefone' => 'nullable|string|max:20',
            'email_contato' => 'nullable|email|max:255',
            'site' => 'nullable|url|max:255',
            'observacoes' => 'nullable|string',
            'exige_documento_por_veiculo' => 'nullable|boolean',
            'cep' => 'nullable|string|max:10',
            'logradouro' => 'nullable|string|max:255',
            'numero' => 'nullable|string|max:20',
            'complemento' => 'nullable|string|max:100',
            'bairro' => 'nullable|string|max:100',
            'cidade' => 'nullable|string|max:100',
            'estado' => 'nullable|string|size:2',
        ];

        if ($this->isMethod('post')) {
            $rules['tipos_documentos'] = 'nullable|array';
            $rules['tipos_documentos.*'] = 'exists:tipo_documentos,id';
        }

        $rules['operacional_principal'] = 'required|exists:gen_op,id';

        return $rules;
    }

    public function messages(): array
    {
        return [
            'razao_social.required' => 'A razão social é obrigatória.',
            'cnpj.required' => 'O CNPJ é obrigatório.',
            'cnpj.size' => 'O CNPJ deve ter 18 caracteres (com formatação).',
            'email_contato.email' => 'O email de contato deve ser válido.',
            'site.url' => 'O site deve ser uma URL válida.',
            'operacional_principal.required' => 'O operacional principal é obrigatório.',
            'operacional_principal.exists' => 'O operacional selecionado não existe.',
        ];
    }
}
