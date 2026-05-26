<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pi_valores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pi_id');
            $table->decimal('total_bruto', 15, 2);
            $table->decimal('comissao', 15, 2)->nullable();
            $table->decimal('liquido', 15, 2);
            $table->decimal('custo_unitario', 15, 2)->nullable();
            $table->decimal('desconto', 15, 2)->nullable();
            $table->decimal('custo_total', 15, 2);
            $table->string('local_cobranca')->nullable();
            $table->string('prazo_pagamento')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('pi_id', 'idx_pival_pi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pi_valores');
    }
};
