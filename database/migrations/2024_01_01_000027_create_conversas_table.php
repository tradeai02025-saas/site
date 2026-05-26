<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversas', function (Blueprint $table) {
            $table->id();
            $table->string('assunto');
            $table->unsignedBigInteger('pi_id')->nullable();
            $table->enum('nivel_atual', ['fornecedor', 'operacional', 'gerencial', 'admin', 'representante']);
            $table->enum('status_conversa', ['aberta', 'resolvida', 'escalada'])->default('aberta');
            $table->string('criador_type');
            $table->unsignedBigInteger('criador_id');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['pi_id', 'deleted_at'], 'idx_conv_pi');
            $table->index(['status_conversa', 'deleted_at'], 'idx_conv_status');
            $table->index(['criador_type', 'criador_id'], 'idx_conv_criador');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversas');
    }
};
