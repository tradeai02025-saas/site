<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversa_transferencias', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversa_id');
            $table->unsignedBigInteger('de_user_id');
            $table->unsignedBigInteger('para_user_id');
            $table->text('motivo')->nullable();
            $table->timestamp('data_transferencia')->useCurrent();
            $table->timestamps();
            $table->softDeletes();

            $table->index('conversa_id', 'idx_convtransf_conversa');
            $table->index('de_user_id', 'idx_convtransf_de');
            $table->index('para_user_id', 'idx_convtransf_para');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversa_transferencias');
    }
};
