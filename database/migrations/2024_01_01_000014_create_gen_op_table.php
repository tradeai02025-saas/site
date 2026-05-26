<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gen_op', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('nome_completo');
            $table->string('telefone', 20)->nullable();
            $table->string('departamento')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id', 'idx_genop_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gen_op');
    }
};
