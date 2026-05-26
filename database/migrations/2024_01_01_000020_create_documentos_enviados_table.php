<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos_enviados', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fornecedor_id');
            $table->unsignedBigInteger('tipo_documento_id');
            $table->unsignedBigInteger('status_id');
            $table->timestamp('data_envio');
            $table->date('data_validade_documento')->nullable();
            $table->boolean('valido')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['fornecedor_id', 'tipo_documento_id', 'deleted_at'], 'idx_docenv_fornecedor_tipo');
            $table->index(['status_id', 'valido', 'deleted_at'], 'idx_docenv_status_valido');
            $table->index(['data_validade_documento', 'valido'], 'idx_docenv_validade');
            $table->index(['fornecedor_id', 'deleted_at'], 'idx_docenv_fornecedor');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos_enviados');
    }
};
