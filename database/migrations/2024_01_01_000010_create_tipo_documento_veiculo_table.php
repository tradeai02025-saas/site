<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipo_documento_veiculo', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tipo_documento_id');
            $table->unsignedBigInteger('tipo_veiculo_id');
            $table->timestamps();

            $table->unique(['tipo_documento_id', 'tipo_veiculo_id'], 'unique_tipo_doc_veiculo');

            $table->foreign('tipo_documento_id', 'fk_tdv_tipo_documento')
                ->references('id')->on('tipo_documentos')->cascadeOnDelete();
            $table->foreign('tipo_veiculo_id', 'fk_tdv_tipo_veiculo')
                ->references('id')->on('tipo_veiculos')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipo_documento_veiculo');
    }
};
