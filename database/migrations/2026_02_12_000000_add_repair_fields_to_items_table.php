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
        Schema::table('items', function (Blueprint $table) {
            $table->string('repair_order_name')->nullable()->after('category_flags');
            $table->string('repair_state')->nullable()->after('repair_order_name');
            $table->date('repair_schedule_date')->nullable()->after('repair_state');
            $table->string('repair_service_type')->nullable()->after('repair_schedule_date');
            $table->string('repair_vendor')->nullable()->after('repair_service_type');
            $table->integer('repair_odometer')->nullable()->after('repair_vendor');
            $table->date('repair_estimation_end')->nullable()->after('repair_odometer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn([
                'repair_order_name',
                'repair_state',
                'repair_schedule_date',
                'repair_service_type',
                'repair_vendor',
                'repair_odometer',
                'repair_estimation_end',
            ]);
        });
    }
};
