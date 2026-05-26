<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('representante_fornecedores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('representante_usuario_id');
            $table->unsignedBigInteger('fornecedor_id');
            $table->enum('status_vinculo', ['pendente', 'aprovado', 'rejeitado'])->default('pendente');
            $table->unsignedBigInteger('aprovado_por')->nullable();
            $table->timestamp('data_vinculo');
            $table->timestamp('data_aprovacao')->nullable();
            $table->timestamp('data_desvinculo')->nullable();
            $table->string('motivo_rejeicao', 500)->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['representante_usuario_id', 'fornecedor_id'], 'unique_rep_forn');
            $table->index('representante_usuario_id', 'idx_repforn_representante');
            $table->index('fornecedor_id', 'idx_repforn_fornecedor');
            $table->index('status_vinculo', 'idx_repforn_status');
            $table->index('ativo', 'idx_repforn_ativo');

            $table->foreign('representante_usuario_id', 'fk_repforn_representante')
                ->references('id')->on('representante_usuarios')->cascadeOnDelete();
            $table->foreign('fornecedor_id', 'fk_repforn_fornecedor')
                ->references('id')->on('fornecedores')->cascadeOnDelete();
            $table->foreign('aprovado_por', 'fk_repforn_aprovado_por')
                ->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('representante_fornecedores');
    }
};
