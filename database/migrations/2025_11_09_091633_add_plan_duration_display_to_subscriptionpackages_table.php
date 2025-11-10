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
        Schema::table('subscriptionpackages', function (Blueprint $table) {
            $table->string('plan_duration_display', 50)->nullable()->after('plan_duration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptionpackages', function (Blueprint $table) {
            $table->dropColumn('plan_duration_display');
        });
    }
};
