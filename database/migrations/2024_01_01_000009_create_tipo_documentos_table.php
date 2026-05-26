<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipo_documentos', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->integer('validade_dias')->comment('Prazo de validade do documento em dias');
            $table->boolean('validar')->default(false)->comment('Se requer validação por operacional');
            $table->boolean('avulso')->default(false);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['ativo', 'deleted_at'], 'idx_tipodoc_ativo');
            $table->index(['avulso', 'ativo', 'deleted_at'], 'idx_tipodoc_avulso');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipo_documentos');
    }
};
