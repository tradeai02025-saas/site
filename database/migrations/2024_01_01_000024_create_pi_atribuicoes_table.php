<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pi_atribuicoes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cliente_id')->nullable();
            $table->unsignedBigInteger('pi_id')->nullable();
            $table->unsignedBigInteger('operacional_id');
            $table->timestamp('data_atribuicao');
            $table->timestamp('data_desatribuicao')->nullable();
            $table->text('motivo_mudanca')->nullable();
            $table->boolean('ativo')->default(true);
            $table->boolean('principal')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['pi_id', 'operacional_id', 'ativo', 'deleted_at'], 'idx_piatrib_pi_op');
            $table->index(['cliente_id', 'operacional_id', 'ativo', 'deleted_at'], 'idx_piatrib_cliente_op');
            $table->index(['operacional_id', 'ativo', 'principal', 'deleted_at'], 'idx_piatrib_op_ativo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pi_atribuicoes');
    }
};
