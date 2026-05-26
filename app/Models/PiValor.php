<?php

/* app/Models/PiValor.php */

namespace App\Models;

use Illuminate\Database\Eloquent\{Factories\HasFactory, Model, SoftDeletes};
use OwenIt\Auditing\{Auditable, Contracts\Auditable as AuditableContract};

class PiValor extends Model implements AuditableContract
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'pi_valores';

    protected $fillable = [
        'pi_id',
        'total_bruto',
        'comissao',
        'liquido',
        'custo_unitario',
        'desconto',
        'custo_total',
        'local_cobranca',
        'prazo_pagamento',
    ];

    protected $casts = [
        'total_bruto' => 'decimal:2',
        'comissao' => 'decimal:2',
        'liquido' => 'decimal:2',
        'custo_unitario' => 'decimal:2',
        'desconto' => 'decimal:2',
        'custo_total' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function pi()
    {
        return $this->belongsTo(Pi::class);
    }
}