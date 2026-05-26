<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pi_documentos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pi_id');
            $table->unsignedBigInteger('tipo_documento_id')->comment('Tipo de documento requerido');
            $table->unsignedBigInteger('documento_enviado_id')->nullable()->comment('NULL = pendente | Preenchido = atendido');
            $table->unsignedBigInteger('status_id')->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['pi_id', 'tipo_documento_id', 'deleted_at'], 'idx_pidoc_pi_tipo');
            $table->index(['documento_enviado_id', 'deleted_at'], 'idx_pidoc_doc_enviado');
            $table->index('status_id', 'idx_pidoc_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pi_documentos');
    }
};
