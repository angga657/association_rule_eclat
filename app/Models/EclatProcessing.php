<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EclatProcessing extends Model
{
    use HasFactory;
    
    protected $table = 'eclat_processing'; // SUDAH BENAR - singular
    
    protected $fillable = [
        'batch_year',
        'start_date',
        'end_date',
        'min_support',
        'min_confidence',
        'kategori',
        'divisi',
        'jenis_trx',
        'customer_type',
        'total_transactions',
    ];
    
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'min_support' => 'decimal:3',
        'min_confidence' => 'decimal:3',
        'total_transactions' => 'integer',
    ];
    
    public function results()
    {
        return $this->hasMany(EclatResult::class, 'processing_id');
    }
}