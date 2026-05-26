<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('status', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->enum('tipo', ['pi', 'documento', 'geral']);
            $table->string('cor', 7)->nullable();
            $table->boolean('ativo')->default(true);
            $table->text('descricao')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tipo', 'ativo', 'deleted_at'], 'idx_status_tipo_ativo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('status');
    }
};
