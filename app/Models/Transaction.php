<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'jenis_trx',
        'tanggal',
        'id_trx',
        'items',
        'satuan',
        'kuantiti',
        'harga_jual',
        'harga_beli',
        'diskon',
        'h_diskon',
        'total_harga',
        'operator',
        'kosong',
        'barcode',
        'id_customer',
        'id_distributor',
        'produk_out',
        'kosong_1',
        'kode_sub_kategori',
        'sub_kategori',
        'kategori',
        'kode_kategori',
        'kode_departemen',
        'departemen',
        'customer',
        'kode_divisi',
        'divisi',
    ];
    
    protected $casts = [
        'tanggal' => 'datetime',
        'harga_jual' => 'decimal:2',
        'harga_beli' => 'decimal:2',
        'diskon' => 'decimal:2',
        'h_diskon' => 'decimal:2',
        'total_harga' => 'decimal:2',
    ];
    
    /**
     * Scope untuk filter berdasarkan tanggal
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        if ($startDate && $endDate) {
            return $query->whereBetween('tanggal', [$startDate, $endDate]);
        }
        return $query;
    }
    
    /**
     * Scope untuk filter berdasarkan kategori
     */
    public function scopeCategory($query, $kategori)
    {
        if ($kategori) {
            return $query->where('kategori', $kategori);
        }
        return $query;
    }
    
    /**
     * Scope untuk filter berdasarkan divisi
     */
    public function scopeDivision($query, $divisi)
    {
        if ($divisi) {
            return $query->where('divisi', $divisi);
        }
        return $query;
    }
    
    /**
     * Get unique divisi from database
     */
    public static function getUniqueDivisi()
    {
        return self::distinct('divisi')
            ->whereNotNull('divisi')
            ->where('divisi', '!=', '')
            ->pluck('divisi')
            ->sort()
            ->values();
    }
    
    /**
     * Get kategori grouped by divisi
     */
    public static function getKategoriByDivisi()
    {
        return self::select('divisi', 'kategori')
            ->whereNotNull('divisi')
            ->whereNotNull('kategori')
            ->where('divisi', '!=', '')
            ->where('kategori', '!=', '')
            ->distinct()
            ->get()
            ->groupBy('divisi')
            ->map(function ($item) {
                return $item->pluck('kategori')->sort()->values();
            });
    }
    
    /**
     * Relationship untuk menghitung transaksi dengan ID yang sama
     */
    public function sameIdTransactions()
    {
        return $this->hasMany(Transaction::class, 'id_trx', 'id_trx');
    }
}