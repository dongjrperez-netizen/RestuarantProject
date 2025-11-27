<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Modify the enum column to include 'waste'. Use raw statement for portability.
        // NOTE: This assumes MySQL-like ENUM support. If using other DB, adjust accordingly.
        DB::statement("ALTER TABLE `damage_spoilage_logs` MODIFY `type` ENUM('damage','spoilage','waste') NOT NULL DEFAULT 'damage'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Revert to original enum (remove 'waste'). This will fail if rows with 'waste' exist.
        DB::statement("ALTER TABLE `damage_spoilage_logs` MODIFY `type` ENUM('damage','spoilage') NOT NULL DEFAULT 'damage'");
    }
};
