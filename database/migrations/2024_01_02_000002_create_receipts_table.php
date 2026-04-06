<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number')->unique();
            $table->date('date');
            $table->string('customer_name');
            $table->string('customer_phone')->nullable();
            $table->string('car_model');
            $table->decimal('unit_price', 15, 2);
            $table->string('currency', 3)->default('USD');
            $table->integer('quantity')->default(1);
            $table->decimal('total_amount', 15, 2);
            $table->enum('payment_category', ['booking', 'full_payment', 'down_payment', 'installment', 'service_payment', 'other']);
            $table->string('bank_reference');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
