<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pis', function (Blueprint $table) {
            $table->id();
            $table->string('numero_pi');
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('fornecedor_id')->nullable();
            $table->unsignedBigInteger('gerencial_id')->nullable();
            $table->unsignedBigInteger('operacional_id')->nullable();
            $table->unsignedBigInteger('status_id')->nullable();
            $table->unsignedBigInteger('tipo_veiculo_id')->nullable();
            $table->tinyInteger('grau_prioridade')->default(1);
            $table->string('periodo')->nullable();
            $table->string('PIT_n')->nullable();
            $table->string('plano_n')->nullable();
            $table->string('planilha_n')->nullable();
            $table->string('file_path', 500)->nullable();
            $table->timestamp('data_cadastro');
            $table->timestamp('data_conclusao')->nullable();
            $table->text('observacoes')->nullable();
            $table->boolean('concluido')->default(false);
            $table->boolean('envio_confirmado')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('numero_pi', 'idx_pis_numero');
            $table->index(['fornecedor_id', 'deleted_at'], 'idx_pis_fornecedor');
            $table->index(['cliente_id', 'deleted_at'], 'idx_pis_cliente');
            $table->index(['status_id', 'concluido', 'deleted_at'], 'idx_pis_status');
            $table->index('operacional_id', 'idx_pis_operacional');
            $table->index('gerencial_id', 'idx_pis_gerencial');
            $table->index(['concluido', 'deleted_at'], 'idx_pis_concluido');
            $table->index('data_cadastro', 'idx_pis_data_cadastro');

            $table->foreign('tipo_veiculo_id', 'fk_pis_tipo_veiculo')
                ->references('id')->on('tipo_veiculos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pis');
    }
};
