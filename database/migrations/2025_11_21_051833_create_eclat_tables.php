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
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->smallInteger('batch_year')->nullable();
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

            $table->foreignId('processing_id')
                  ->constrained('eclat_processing')
                  ->cascadeOnDelete();

            $table->string('rule_from'); // A
            $table->string('rule_to');   // B

            $table->decimal('support', 8, 6);
            $table->decimal('confidence', 8, 6);
            $table->decimal('lift_ratio', 8, 6);

            $table->timestamps();

            // Mencegah duplicate A→B
            $table->unique(
                ['processing_id', 'rule_from', 'rule_to'],
                'unique_eclat_rule'
            );
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