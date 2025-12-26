<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EclatResult extends Model
{
    use HasFactory;
    
    protected $table = 'eclat_results'; // Pastikan ini benar
    
    protected $fillable = [
        'processing_id',
        'itemset',        // 🔥 WAJIB
        'rule_from',
        'rule_to',
        'support',
        'confidence',
        'lift_ratio',
    ];
    
    protected $casts = [
        'support' => 'decimal:6',
        'confidence' => 'decimal:6',
        'lift_ratio' => 'decimal:6',
    ];
    
    public function processing()
    {
        return $this->belongsTo(EclatProcessing::class, 'processing_id');
    }
}