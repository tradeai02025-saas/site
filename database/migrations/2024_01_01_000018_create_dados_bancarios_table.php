<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dados_bancarios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fornecedor_id');
            $table->string('banco');
            $table->string('agencia', 10);
            $table->string('digito_agencia', 2)->nullable();
            $table->string('conta', 20);
            $table->string('digito_conta', 2);
            $table->enum('tipo_conta', ['corrente', 'poupanca']);
            $table->enum('pix_tipo', ['cpf', 'cnpj', 'email', 'telefone', 'chave_aleatoria'])->nullable();
            $table->string('pix_chave')->nullable();
            $table->string('titular');
            $table->string('cpf_cnpj_titular', 18);
            $table->timestamps();
            $table->softDeletes();

            $table->index('fornecedor_id', 'idx_dadobanc_fornecedor');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dados_bancarios');
    }
};
