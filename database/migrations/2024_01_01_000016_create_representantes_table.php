<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('representantes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fornecedor_id');
            $table->string('nome_completo');
            $table->string('cpf', 14);
            $table->string('telefone_1', 20)->nullable();
            $table->string('telefone_2', 20)->nullable();
            $table->string('email_1')->nullable();
            $table->string('email_2')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['fornecedor_id', 'deleted_at'], 'idx_repr_fornecedor');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('representantes');
    }
};
