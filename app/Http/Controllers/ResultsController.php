<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\TransactionFilterService;
use App\Models\EclatProcessing;
use App\Models\EclatResult;

class ResultsController extends Controller
{
    /**
     * Display the data uji page.
     */
    public function index(Request $request)
    {
        $batch = $request->query('batch');

        $processing = EclatProcessing::when(
            $batch && $batch !== 'all',
            fn($q) => $q->where('batch_year', $batch)
        )->latest()->first();

        $singleItemsets = collect();
        $pairItemsets   = collect();

        if ($processing) {

            // ===============================
            // 🔥 SINGLE ITEMSET (BENAR)
            // ===============================
            $singleItemsets = $processing->results()
                ->whereNull('rule_from')
                ->whereNull('rule_to')
                ->where(function ($q) {
                    $q->whereNotNull('itemset')
                    ->where('itemset', '!=', '');
                })
                ->orderByDesc('support')
                ->get()
                ->map(function ($item) {
                    $item->support_percent = $item->support * 100;
                    return $item;
                });
                

            // ===============================
            // 🔥 PAIR ITEMSET / ASSOCIATION RULE
            // ===============================
            $pairItemsets = $processing->results()
                ->whereNotNull('rule_from')
                ->whereNotNull('rule_to')
                ->whereColumn('rule_from', '!=', 'rule_to')
                ->where('lift_ratio', '>', 1)
                ->orderByDesc('support')
                ->get()
                ->map(function ($item) {
                    $item->support_percent     = $item->support * 100;
                    $item->confidence_percent  = $item->confidence * 100;
                    return $item;
                });
        }

        return view('data-hasil', compact(
            'processing',
            'singleItemsets',
            'pairItemsets'
        ));
    }
}