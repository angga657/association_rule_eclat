<?php

namespace App\Http\Controllers;

use App\Models\EclatProcessing;
use App\Models\EclatResult;

class ResultsController extends Controller
{
    /**
     * Display the data uji page.
     */
    public function index()
    {
        try {
            $processing = EclatProcessing::latest()->first();
            $results = collect([]);

            $singleItemsets = collect([]);
            $pairItemsets = collect([]);

            if ($processing) {
                $results = $processing->results()->get();

                // Pisahkan single & pair berdasarkan karakter '→'
                $singleItemsets = $results->filter(function($r){
                    return !str_contains($r->itemset, '→');
                });

                $pairItemsets = $results->filter(function($r){
                    return str_contains($r->itemset, '→');
                });
            }

            return view('data-uji', compact(
                'results', 'processing',
                'singleItemsets', 'pairItemsets'
            ));

        } catch (\Exception $e) {
            \Log::error('Error in dataUji: ' . $e->getMessage());

            return view('data-uji', [
                'results' => collect([]),
                'singleItemsets' => collect([]),
                'pairItemsets' => collect([]),
                'processing' => null,
                'error' => 'Terjadi kesalahan: ' . $e->getMessage()
            ]);
        }
    }
}