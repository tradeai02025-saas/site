<?php

/* app/Models/Fornecedor.php */

namespace App\Models;

use Illuminate\Database\Eloquent\{Factories\HasFactory, Model, SoftDeletes};
use OwenIt\Auditing\{Auditable, Contracts\Auditable as AuditableContract};

class Fornecedor extends Model implements AuditableContract
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'fornecedores';

    protected $fillable = [
        'user_id',
        'razao_social',
        'nome_fantasia',
        'nome_veiculo',
        'cnpj',
        'telefone',
        'fax',
        'praca',
        'email_contato',
        'site',
        'observacoes',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function representantes()
    {
        return $this->hasMany(Representante::class);
    }

    public function representantesUsuarios()
    {
        return $this->belongsToMany(RepresentanteUsuario::class, 'representante_fornecedores', 'fornecedor_id', 'representante_usuario_id')
            ->where('representante_fornecedores.status_vinculo', 'aprovado')
            ->where('representante_fornecedores.ativo', true)
            ->whereNull('representante_fornecedores.data_desvinculo')
            ->withPivot('status_vinculo', 'data_vinculo', 'data_aprovacao', 'ativo')
            ->withTimestamps();
    }

    public function todosRepresentantesUsuarios()
    {
        return $this->belongsToMany(RepresentanteUsuario::class, 'representante_fornecedores', 'fornecedor_id', 'representante_usuario_id')
            ->withPivot('id', 'status_vinculo', 'data_vinculo', 'data_aprovacao', 'data_desvinculo', 'ativo', 'motivo_rejeicao', 'aprovado_por')
            ->withTimestamps();
    }

    public function vinculosRepresentantes()
    {
        return $this->hasMany(RepresentanteFornecedor::class, 'fornecedor_id');
    }

    public function vinculosRepresentantesAtivos()
    {
        return $this->hasMany(RepresentanteFornecedor::class, 'fornecedor_id')
            ->where('status_vinculo', 'aprovado')
            ->where('ativo', true)
            ->whereNull('data_desvinculo');
    }

    public function vinculosRepresentantesPendentes()
    {
        return $this->hasMany(RepresentanteFornecedor::class, 'fornecedor_id')
            ->where('status_vinculo', 'pendente')
            ->where('ativo', true);
    }

    public function enderecos()
    {
        return $this->morphMany(Endereco::class, 'enderecavel');
    }

    public function dadosBancarios()
    {
        return $this->hasMany(DadosBancarios::class);
    }

    public function pis()
    {
        return $this->hasMany(Pi::class);
    }

    public function documentosEnviados()
    {
        return $this->hasMany(DocumentoEnviado::class);
    }

    public function conversasComoParticipante()
    {
        return $this->morphMany(ConversaParticipante::class, 'participante');
    }

    public function mensagensEnviadas()
    {
        return $this->morphMany(Mensagem::class, 'remetente');
    }

    public function precisaCompletarCadastro(): bool
    {
        return empty($this->telefone) || !$this->dadosBancarios()->exists();
    }

    public function getCamposFaltantes(): array
    {
        $faltantes = [];
        if (empty($this->telefone)) {
            $faltantes[] = 'Telefone de contato';
        }
        if (!$this->dadosBancarios()->exists()) {
            $faltantes[] = 'Dados bancários';
        }
        return $faltantes;
    }

    public function getProgressoCadastro(): int
    {
        $steps = [
            'telefone' => !empty($this->telefone),
            'banking'  => $this->dadosBancarios()->exists(),
        ];
        $completed = count(array_filter($steps));
        return (int) round(($completed / count($steps)) * 100);
    }
}