<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mensagens_anexos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mensagem_id');
            $table->string('nome_original');
            $table->string('nome_arquivo');
            $table->string('file_path', 500);
            $table->enum('tipo_arquivo', ['imagem', 'pdf', 'doc', 'outro']);
            $table->string('extensao', 10);
            $table->bigInteger('tamanho');
            $table->timestamps();
            $table->softDeletes();

            $table->index('mensagem_id', 'idx_msganexo_msg');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mensagens_anexos');
    }
};
