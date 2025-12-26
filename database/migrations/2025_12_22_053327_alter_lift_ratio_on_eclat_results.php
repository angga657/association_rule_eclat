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
            $table->double('lift_ratio', 15, 8)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('eclat_results', function (Blueprint $table) {
            $table->decimal('lift_ratio', 8, 2)->change();
        });
    }
};
