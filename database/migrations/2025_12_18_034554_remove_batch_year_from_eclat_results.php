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
        Schema::table('eclat_results', function (Blueprint $table) {
            if (Schema::hasColumn('eclat_results', 'batch_year')) {
                $table->dropColumn('batch_year');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('eclat_results', function (Blueprint $table) {
            //
        });
    }
};
