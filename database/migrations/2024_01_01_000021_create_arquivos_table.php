<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arquivos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('documento_enviado_id');
            $table->string('nome_original');
            $table->string('nome_arquivo');
            $table->string('file_path', 500);
            $table->string('extensao', 10);
            $table->bigInteger('tamanho');
            $table->string('mime_type', 100);
            $table->timestamps();
            $table->softDeletes();

            $table->index('documento_enviado_id', 'idx_arq_docenv');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arquivos');
    }
};
