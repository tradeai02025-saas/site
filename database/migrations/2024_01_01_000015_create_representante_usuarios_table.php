<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('representante_usuarios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('nome_empresa')->nullable();
            $table->string('cnpj', 18)->nullable();
            $table->string('telefone', 20)->nullable();
            $table->text('observacoes')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id', 'idx_rep_user_id');
            $table->index('ativo', 'idx_rep_ativo');
            $table->index('cnpj', 'idx_repusr_cnpj');

            $table->foreign('user_id', 'fk_rep_usuarios_user')
                ->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('representante_usuarios');
    }
};
