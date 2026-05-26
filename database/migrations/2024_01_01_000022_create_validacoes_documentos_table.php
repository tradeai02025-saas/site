<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('validacoes_documentos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('documento_enviado_id');
            $table->unsignedBigInteger('operacional_id')->nullable();
            $table->unsignedBigInteger('status_id');
            $table->timestamp('data_validacao');
            $table->text('observacoes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('documento_enviado_id', 'idx_valdoc_docenv');
            $table->index('operacional_id', 'idx_valdoc_op');
            $table->index(['documento_enviado_id', 'status_id'], 'idx_valdoc_docenv_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('validacoes_documentos');
    }
};
