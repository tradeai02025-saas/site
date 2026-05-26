<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversa_participantes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversa_id');
            $table->string('participante_type');
            $table->unsignedBigInteger('participante_id');
            $table->timestamp('data_entrada');
            $table->timestamp('data_saida')->nullable();
            $table->enum('nivel_participante', ['fornecedor', 'operacional', 'gerencial', 'admin']);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['participante_type', 'participante_id', 'data_saida'], 'idx_convpart_participante');
            $table->index(['conversa_id', 'data_saida'], 'idx_convpart_conversa');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversa_participantes');
    }
};
