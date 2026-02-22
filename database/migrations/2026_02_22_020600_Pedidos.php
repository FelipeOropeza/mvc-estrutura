<?php

use Core\Database\Schema\Schema;
use Core\Database\Schema\Blueprint;

class Pedidos
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id(); // Auto Increment automatico (Inteiro Unsigned)
            $table->string('status', 20)->default('PENDENTE');
            $table->decimal('valor_total', 10, 2)->default(0);

            // Chave Estrangeira! 
            // O tipo precisa bater exatamente com o Pai (Que também é bigint/int unsigned se for criado com id())
            $table->integer('user_id')->unsigned();

            // Ligamos a nossa coluna user_id na coluna `id` da tabela `users` do seu projeto original
            $table->foreign('user_id')->references('id')->on('users')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
}
