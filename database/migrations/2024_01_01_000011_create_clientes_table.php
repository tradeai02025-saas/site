<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('razao_social');
            $table->string('nome_fantasia')->nullable();
            $table->string('nome_cliente')->nullable();
            $table->string('cnpj', 18);
            $table->string('inscricao_estadual', 50)->nullable();
            $table->string('inscricao_municipal', 50)->nullable();
            $table->string('praca')->nullable();
            $table->string('telefone', 20)->nullable();
            $table->string('email_contato')->nullable();
            $table->string('site')->nullable();
            $table->text('observacoes')->nullable();
            $table->boolean('exige_documento_por_veiculo')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('cnpj', 'idx_clientes_cnpj');
            $table->index('deleted_at', 'idx_clientes_deleted');
            $table->index(['razao_social', 'deleted_at'], 'idx_clientes_razao');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
