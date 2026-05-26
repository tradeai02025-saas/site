<?php

/* app/Models/RepresentanteUsuario.php */

namespace App\Models;

use Illuminate\Database\Eloquent\{Factories\HasFactory, Model, SoftDeletes};
use OwenIt\Auditing\{Auditable, Contracts\Auditable as AuditableContract};

class RepresentanteUsuario extends Model implements AuditableContract
{
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'representante_usuarios';

    protected $fillable = [
        'user_id',
        'nome_empresa',
        'cnpj',
        'telefone',
        'observacoes',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vinculos()
    {
        return $this->hasMany(RepresentanteFornecedor::class, 'representante_usuario_id');
    }

    public function vinculosAtivos()
    {
        return $this->hasMany(RepresentanteFornecedor::class, 'representante_usuario_id')
            ->where('status_vinculo', 'aprovado')
            ->where('ativo', true)
            ->whereNull('data_desvinculo');
    }

    public function vinculosPendentes()
    {
        return $this->hasMany(RepresentanteFornecedor::class, 'representante_usuario_id')
            ->where('status_vinculo', 'pendente')
            ->where('ativo', true);
    }

    public function fornecedores()
    {
        return $this->belongsToMany(Fornecedor::class, 'representante_fornecedores', 'representante_usuario_id', 'fornecedor_id')
            ->where('representante_fornecedores.status_vinculo', 'aprovado')
            ->where('representante_fornecedores.ativo', true)
            ->whereNull('representante_fornecedores.data_desvinculo')
            ->withPivot('status_vinculo', 'data_vinculo', 'data_aprovacao', 'ativo')
            ->withTimestamps();
    }

    public function todosFornecedores()
    {
        return $this->belongsToMany(Fornecedor::class, 'representante_fornecedores', 'representante_usuario_id', 'fornecedor_id')
            ->withPivot('status_vinculo', 'data_vinculo', 'data_aprovacao', 'data_desvinculo', 'ativo', 'motivo_rejeicao')
            ->withTimestamps();
    }

    public function representaFornecedor(int $fornecedorId): bool
    {
        return $this->vinculosAtivos()
            ->where('fornecedor_id', $fornecedorId)
            ->exists();
    }

    public function getFornecedorIds(): array
    {
        return $this->vinculosAtivos()
            ->pluck('fornecedor_id')
            ->toArray();
    }

    public function getNameAttribute(): string
    {
        if ($this->nome_empresa) {
            return $this->nome_empresa;
        }

        return $this->user ? $this->user->name : 'Representante';
    }

    public function precisaCompletarCadastro(): bool
    {
        return empty($this->nome_empresa) || empty($this->telefone);
    }

    public function getCamposFaltantes(): array
    {
        $faltantes = [];
        if (empty($this->nome_empresa)) {
            $faltantes[] = 'Nome da empresa';
        }
        if (empty($this->telefone)) {
            $faltantes[] = 'Telefone';
        }
        return $faltantes;
    }

    public function getProgressoCadastro(): int
    {
        $steps = [
            'empresa' => ! empty($this->nome_empresa),
            'telefone' => ! empty($this->telefone),
        ];
        $completed = count(array_filter($steps));
        return (int) round(($completed / count($steps)) * 100);
    }
}