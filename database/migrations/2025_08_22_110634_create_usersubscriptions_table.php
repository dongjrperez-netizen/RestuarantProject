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
        Schema::create('usersubscriptions', function (Blueprint $table) {
            $table->id('userSubscription_id');
            $table->date('subscription_startDate');
            $table->timestamp('subscription_endDate');
            $table->integer('remaining_days')->default(0);
            $table->boolean('is_trial')->default(true);
            $table->string('subscription_status')->default('active');

            $table->foreignId('plan_id')->constrained('subscriptionpackages', 'plan_id')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usersubscriptions');
    }
};
