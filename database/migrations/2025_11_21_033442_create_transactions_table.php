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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('jenis_trx');
            $table->dateTime('tanggal');
            $table->string('id_trx');
            $table->text('items');
            $table->string('satuan');
            $table->integer('kuantiti');
            $table->decimal('harga_jual', 15, 2);
            $table->decimal('harga_beli', 15, 2);
            $table->decimal('diskon', 15, 2)->default(0);
            $table->decimal('h_diskon', 15, 2)->default(0);
            $table->decimal('total_harga', 15, 2);
            $table->string('operator');
            $table->string('kosong')->nullable();
            $table->string('barcode');
            $table->string('id_customer');
            $table->string('id_distributor');
            $table->string('produk_out');
            $table->string('kosong_1')->nullable();
            $table->string('kode_sub_kategori');
            $table->string('sub_kategori');
            $table->string('kode_kategori');
            $table->string('kategori');
            $table->string('kode_departemen');
            $table->string('departemen');
            $table->string('customer');
            $table->string('kode_divisi');
            $table->string('divisi');
            $table->timestamps();
            
            // Index untuk mempercepat query
            $table->index(['id_trx']);
            $table->index(['tanggal']);
            $table->index(['kategori']);
            $table->index(['divisi']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
