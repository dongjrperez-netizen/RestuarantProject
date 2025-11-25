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
        Schema::table('menu_plan_dishes', function (Blueprint $table) {
            $table->date('planned_date')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('menu_plan_dishes', function (Blueprint $table) {
            $table->date('planned_date')->nullable(false)->change();
        });
    }
};
