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
        // Create eclat_processing table first
        Schema::create('eclat_processing', function (Blueprint $table) {
            $table->id();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('min_support', 4, 3);
            $table->decimal('min_confidence', 4, 3);
            $table->string('kategori')->nullable();
            $table->string('divisi')->nullable();
            $table->string('jenis_trx')->nullable();
            $table->string('customer_type')->nullable();
            $table->integer('total_transactions');
            $table->timestamps();
            
            $table->index(['start_date', 'end_date']);
            $table->index(['kategori', 'divisi']);
        });

        // Create eclat_results table second
        Schema::create('eclat_results', function (Blueprint $table) {
            $table->id();
            $table->string('itemset');
            $table->decimal('support', 8, 6);
            $table->decimal('confidence', 8, 6);
            $table->decimal('lift_ratio', 8, 6);
            $table->unsignedBigInteger('processing_id');
            $table->timestamps();
            
            // Add foreign key constraint
            $table->foreign('processing_id')
                  ->references('id')
                  ->on('eclat_processing')
                  ->onDelete('cascade');
            
            $table->index(['processing_id']);
            $table->index(['support']);
            $table->index(['confidence']);
            $table->index(['lift_ratio']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eclat_results');
        Schema::dropIfExists('eclat_processing');
    }
};