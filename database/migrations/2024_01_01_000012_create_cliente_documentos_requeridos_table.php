<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cliente_documentos_requeridos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('tipo_documento_id');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['cliente_id', 'tipo_documento_id', 'deleted_at'], 'idx_clidoc_cliente_tipo');
            $table->index('tipo_documento_id', 'idx_clidoc_tipo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente_documentos_requeridos');
    }
};
