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
        Schema::table('eclat_processing', function (Blueprint $table) {
            if (!Schema::hasColumn('eclat_processing', 'batch_year')) {
                $table->year('batch_year')->after('id')->index();
            }
        });

        Schema::table('eclat_results', function (Blueprint $table) {
            if (!Schema::hasColumn('eclat_results', 'batch_year')) {
                $table->year('batch_year')->after('processing_id')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('eclat_processing', function (Blueprint $table) {
            $table->dropColumn('batch_year');
        });

        Schema::table('eclat_results', function (Blueprint $table) {
            $table->dropColumn('batch_year');
        });
    }
};
