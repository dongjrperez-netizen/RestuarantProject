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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id('supplier_id');
            $table->foreignId('restaurant_id')->constrained('restaurant_data')->onDelete('cascade');
            $table->string('supplier_name', 150);
            $table->string('contact_number', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('address', 255)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('business_registration')->nullable();
            $table->string('tax_id')->nullable();
            $table->enum('payment_terms', ['COD', 'NET_7', 'NET_15', 'NET_30', 'NET_60', 'NET_90'])->default('NET_30');
            $table->decimal('credit_limit', 12, 2);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);

            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
