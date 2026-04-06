<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->enum('invoice_type', ['car_sale', 'service']);
            $table->date('date');
            $table->string('customer_name');
            $table->string('customer_address')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('car_model');
            $table->decimal('unit_price', 15, 2);
            $table->string('currency', 3)->default('USD');
            $table->integer('quantity')->default(1);
            $table->decimal('sub_total', 15, 2);
            $table->boolean('with_vat')->default(false);
            $table->decimal('vat_rate', 5, 2)->default(10.00);
            $table->decimal('vat_amount', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2);
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
        Schema::dropIfExists('invoices');
    }
};
