<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mensagens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversa_id');
            $table->string('remetente_type');
            $table->unsignedBigInteger('remetente_id');
            $table->text('mensagem');
            $table->boolean('lida')->default(false);
            $table->timestamp('data_leitura')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['conversa_id', 'lida', 'deleted_at'], 'idx_msg_conversa_lida');
            $table->index(['remetente_type', 'remetente_id'], 'idx_msg_remetente');
            $table->index(['conversa_id', 'created_at'], 'idx_msg_conversa_data');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mensagens');
    }
};
