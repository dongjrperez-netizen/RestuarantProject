<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('waste_logs', function (Blueprint $table) {
            $table->bigIncrements('waste_log_id');
            $table->unsignedBigInteger('restaurant_id')->index();
            $table->string('order_id')->nullable()->index();
            $table->unsignedBigInteger('dish_id')->nullable()->index();
            $table->string('dish_name')->nullable();
            $table->decimal('quantity', 12, 2)->default(0);
            $table->string('unit')->nullable();
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('total_cost', 14, 2)->default(0);
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('reported_by')->nullable()->index();
            $table->timestamp('reported_at')->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('waste_logs');
    }
};
