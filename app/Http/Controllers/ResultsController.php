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
        // ✅ SAMAKAN DENGAN data-proses
        $rawBatch = $request->query('batch_year');

        $batchYear = (!$rawBatch || $rawBatch === 'all')
            ? 'Semua Batch'
            : $rawBatch;
        $divisi = $request->query('divisi');
        $kategori = $request->query('kategori');

        $processing = EclatProcessing::latest()->first();

        $singleItemsets = collect();
        $pairItemsets   = collect();

        if ($processing) {

            /* ===============================
               SINGLE ITEMSET
               =============================== */
            $singleItemsets = $processing->results()
            ->whereNull('rule_from')
            ->whereNull('rule_to')
            ->whereNotNull('itemset')
            ->where('itemset', '!=', '')
            ->orderByDesc('support')
            ->get()
            ->map(function ($item) {

                $item->support_percent = $item->support * 100;

                // ✅ LANGSUNG PAKAI HASIL ECLAT
                $item->trx = $item->trx;

                return $item;
            });

            $singleIndex = $singleItemsets->keyBy('itemset');

            /* ===============================
               ASSOCIATION RULE (A → B)
               =============================== */
            $pairItemsets = $processing->results()
            ->whereNotNull('rule_from')
            ->whereNotNull('rule_to')
            ->where('lift_ratio', '>', 1)
            ->orderByDesc('lift_ratio')
            ->get()
            ->map(function ($item) {

                $item->support_percent    = $item->support * 100;
                $item->confidence_percent = $item->confidence * 100;

                // 🔥 AMBIL HASIL ECLAT VERTIKAL
                $item->trx_A  = $item->trx_A;
                $item->trx_B  = $item->trx_B;
                $item->trx_AB = $item->trx_AB;

                return $item;
            });
        }

        // ✅ KIRIM batch_year ke view
        return view('data-hasil', compact(
            'processing',
            'singleItemsets',
            'pairItemsets',
            'batchYear'
        ));
    }
}