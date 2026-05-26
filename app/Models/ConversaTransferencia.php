<?php

/* app/Models/ConversaTransferencia.php */

namespace App\Models;

use Illuminate\Database\Eloquent\{Factories\HasFactory, Model, SoftDeletes};
use OwenIt\Auditing\{Auditable, Contracts\Auditable as AuditableContract};

class ConversaTransferencia extends Model implements AuditableContract
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'conversa_transferencias';

    protected $fillable = [
        'conversa_id',
        'de_user_id',
        'para_user_id',
        'motivo',
        'data_transferencia',
    ];

    protected $casts = [
        'data_transferencia' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function conversa()
    {
        return $this->belongsTo(Conversa::class);
    }

    public function deUsuario()
    {
        return $this->belongsTo(User::class, 'de_user_id');
    }

    public function paraUsuario()
    {
        return $this->belongsTo(User::class, 'para_user_id');
    }
}