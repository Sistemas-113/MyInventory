<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained();
            $table->decimal('total_amount', 10, 2);
            $table->enum('payment_type', ['cash', 'credit', 'card']);
            $table->enum('status', ['pending', 'completed', 'cancelled']);
            $table->decimal('interest_rate', 5, 2)->nullable(); // Tasa de interés
            $table->integer('installments')->nullable(); // Número de cuotas
            $table->date('first_payment_date')->nullable(); // Fecha primer pago
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
