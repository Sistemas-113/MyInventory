<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->onDelete('cascade');
            $table->foreignId('provider_id')->nullable()->constrained();
            $table->string('identifier_type')->nullable();
            $table->string('identifier')->nullable();
            $table->string('product_name');
            $table->text('product_description')->nullable();
           $table->decimal('purchase_price', 20, 2); // Nuevo campo 
            $table->decimal('unit_price', 20, 2);
            $table->integer('quantity')->default(1);
            $table->decimal('subtotal', 20, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_details');
    }
};
