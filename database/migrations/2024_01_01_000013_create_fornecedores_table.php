<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fornecedores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('razao_social');
            $table->string('nome_fantasia')->nullable();
            $table->string('nome_veiculo')->nullable();
            $table->string('cnpj', 18);
            $table->string('telefone', 20)->nullable();
            $table->string('fax', 20)->nullable();
            $table->string('praca')->nullable();
            $table->string('email_contato')->nullable();
            $table->string('site')->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id', 'idx_forn_user');
            $table->index('cnpj', 'idx_forn_cnpj');
            $table->index('deleted_at', 'idx_forn_deleted');
            $table->index(['razao_social', 'deleted_at'], 'idx_forn_razao');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fornecedores');
    }
};
