<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_detail_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade'); // Link to order details
            $table->string('discount_type'); // Type of discount ('flat' or 'percentage')
            $table->decimal('discount_value', 8, 2); // Discount value (e.g., 10 for 10% or $10)
            $table->string('discount_name')->nullable(); // Name of the discount (optional, e.g., 'Coupon Code', 'Seasonal')
            $table->text('description')->nullable(); // Details about the discount
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_detail_discounts');
    }
};
