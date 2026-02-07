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
        Schema::create('import_logs', function (Blueprint $table) {
            $table->id();
            $table->string('source'); // 'excel', 'odoo_manual', 'odoo_scheduled'
            $table->string('filename')->nullable(); // Original filename for Excel imports
            $table->timestamp('imported_at');
            $table->integer('items_count')->default(0);
            $table->json('summary_json')->nullable(); // Snapshot of import summary
            $table->string('status')->default('success'); // 'success', 'failed'
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index('imported_at');
            $table->index('source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_logs');
    }
};
