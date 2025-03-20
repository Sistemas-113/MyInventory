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
            $table->decimal('total_amount', 20, 2); // Aumentar la precisión
            $table->decimal('initial_payment', 20, 2)->default(0);
            $table->enum('payment_type', ['cash', 'credit', 'card']);
            $table->enum('status', ['pending', 'completed', 'cancelled']);
            $table->decimal('interest_rate', 5, 2)->nullable();
            $table->integer('installments')->nullable();
            $table->date('first_payment_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
