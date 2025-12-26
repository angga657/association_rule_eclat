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
        Schema::create('eclat_frequent_itemsets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('processing_id')
                  ->constrained('eclat_processing')
                  ->cascadeOnDelete();

            $table->string('itemset'); // contoh: "A" atau "A,B"
            $table->tinyInteger('itemset_size'); // 1 atau 2
            $table->decimal('support', 8, 6);

            $table->timestamps();

            $table->index(['processing_id', 'itemset_size']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eclat_frequent_itemsets');
    }
};
