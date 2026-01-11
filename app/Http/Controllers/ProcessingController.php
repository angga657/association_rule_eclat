<?php

namespace App\Http\Controllers;

use App\Services\TransactionFilterService;
use App\Models\Transaction;
use App\Models\EclatProcessing;
use App\Models\EclatResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ProcessingController extends Controller
{

    private function baseFilteredQuery(Request $request)
    {
        $query = Transaction::query();

        // BATCH
        if ($request->filled('batch_year') && $request->batch_year !== 'all') {
            $query->where('batch_year', $request->batch_year);
        }

        // TANGGAL
        if (
            !$request->boolean('useAllDates') &&
            $request->filled('start_date') &&
            $request->filled('end_date')
        ) {
            $query->whereBetween('tanggal', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        }

        // DIVISI & KATEGORI
        if (!$request->boolean('useAllCategories')) {
            if ($request->filled('divisi')) {
                $query->where('divisi', $request->divisi);
            }
            if ($request->filled('kategori')) {
                $query->where('kategori', $request->kategori);
            }
        }

        return $query;
    }
    /* ===============================
       HALAMAN DATA PROSES
       =============================== */
    public function index(Request $request)
    {
      $divisiList = Transaction::whereNotNull('divisi')
            ->where('divisi', '!=', '')
            ->distinct()
            ->pluck('divisi')
            ->sort()
            ->values();

        $kategoriByDivisi = Transaction::select('divisi', 'kategori')
            ->whereNotNull('divisi')
            ->whereNotNull('kategori')
            ->where('divisi', '!=', '')
            ->where('kategori', '!=', '')
            ->distinct()
            ->get()
            ->groupBy('divisi')
            ->map(fn ($item) => $item->pluck('kategori')->sort()->values());

        $batchYears = Transaction::select('batch_year')
            ->distinct()
            ->orderByDesc('batch_year')
            ->pluck('batch_year');

        return view('data-proses', compact(
            'divisiList',
            'kategoriByDivisi',
            'batchYears'
        ));
    }

    /* ===============================
       PREVIEW DATA (AJAX)
       TIDAK UBAH TAMPILAN
       =============================== */
    public function preview(Request $request)
    {
        $query = $this->baseFilteredQuery($request);

        // TOTAL TRANSAKSI UNIK (UNTUK INFO BAWAH)
        $totalTransaksiUnik = (clone $query)
            ->select('id_trx')
            ->distinct()
            ->count('id_trx');

        /* ===============================
        FILTER BATCH
        =============================== */
        if ($request->filled('batch_year')) {
            $query->where('batch_year', $request->batch_year);
        }

        /* ===============================
        FILTER TANGGAL
        =============================== */
        if (
            !$request->boolean('useAllDates') &&
            $request->filled('start_date') &&
            $request->filled('end_date')
        ) {
            $query->whereBetween('tanggal', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        }

        /* ===============================
        FILTER DIVISI & KATEGORI
        =============================== */
        if (!$request->boolean('useAllCategories')) {
            if ($request->filled('divisi')) {
                $query->where('divisi', $request->divisi);
            }
            if ($request->filled('kategori')) {
                $query->where('kategori', $request->kategori);
            }
        }

        /* ===============================
        SELECT + GROUP
        =============================== */
        $query->select(
            'items',
            DB::raw('COUNT(DISTINCT id_trx) as jumlah_transaksi'),
            DB::raw('GROUP_CONCAT(DISTINCT divisi SEPARATOR ", ") as divisi'),
            DB::raw('GROUP_CONCAT(DISTINCT kategori SEPARATOR ", ") as kategori')
        )
        ->groupBy('items')
        ->orderByDesc('jumlah_transaksi');

        /* ===============================
        PAGINATION (SATU KALI)
        =============================== */
        $transactions = $query
            ->paginate($request->get('per_page', 10))
            ->appends($request->query());

        $data = $transactions->getCollection()->map(function ($trx) {
            return [
                'items' => array_map('trim', explode(',', $trx->items)),
                'divisi' => $trx->divisi,
                'kategori' => $trx->kategori,
                'jumlah_transaksi' => $trx->jumlah_transaksi,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => preg_replace('/<p class="text-sm.*?<\/p>/s', '', $transactions->links()->toHtml()),
            'from' => $transactions->firstItem() ?? 0,
            'to' => $transactions->lastItem() ?? 0,
            'total' => $transactions->total(),
            'total_trx_unik' => $totalTransaksiUnik,
        ]);
    }

    /* ===============================
       PROSES ECLAT (VERTIKAL)
       =============================== */
    public function process(Request $request)
    {
        $request->validate([
            'batch_year'     => 'required',
            'min_support'    => 'required|numeric|min:0|max:1',
            'min_confidence' => 'required|numeric|min:0|max:1',
        ]);

        $transactions = $this->baseFilteredQuery($request)->get();

        if ($transactions->isEmpty()) {
            return back()->with('error', 'Tidak ada transaksi');
        }

        $eclat = $this->runEclatAlgorithm(
            $transactions,
            $request->min_support,
            $request->min_confidence
        );

        $singleItemsets = $eclat['single'];
        $rules          = $eclat['rules'];

        if ($rules->isEmpty()) {
            return back()->with('warning', 'Tidak ada aturan asosiasi yang ditemukan');
        }

        DB::transaction(function () use (
            $request,
            $transactions,
            $singleItemsets,
            $rules
        ) {

            // ✅ 1️⃣ PROCESSING
            $processing = EclatProcessing::create([
                'batch_year' => $request->batch_year === 'all'
                    ? null
                    : $request->batch_year,

                'start_date'         => $request->start_date,
                'end_date'           => $request->end_date,
                'min_support'        => $request->min_support,
                'min_confidence'     => $request->min_confidence,
                'divisi'   => $request->filled('divisi') ? $request->divisi : null,
                'kategori' => $request->filled('kategori') ? $request->kategori : null,
                'total_transactions' => $transactions->groupBy('id_trx')->count(),
            ]);

            // 🔥 2️⃣ SINGLE ITEMSET (1-ITEMSET)
            foreach ($singleItemsets as $item => $data) {
                EclatResult::create([
                    'processing_id' => $processing->id,
                    'itemset'       => $item,
                    'support'       => $data['support'],
                    'trx'           => $data['count'], // 🔥 ABSOLUT
                ]);
            }

            // 🔥 3️⃣ ASSOCIATION RULE (2-ITEMSET)
            foreach ($rules as $row) {
                EclatResult::create([
                    'processing_id' => $processing->id,
                    'rule_from'     => $row['rule_from'],
                    'rule_to'       => $row['rule_to'],
                    'support'       => $row['support'],
                    'confidence'    => $row['confidence'],
                    'lift_ratio'    => $row['lift_ratio'],
                    'trx_A'         => $row['trx_A'],
                    'trx_B'         => $row['trx_B'],
                    'trx_AB'        => $row['trx_AB'],
                ]);
            }
        });

        return redirect()
            ->route('data-hasil', ['batch_year' => $request->batch_year])
            ->with('success', 'ECLAT berhasil diproses');
    }

    /* ===============================
       ECLAT CORE (VERTIKAL)
       =============================== */
    private function runEclatAlgorithm($transactions, $minSupport, $minConfidence)
    {
         /* =====================================================
        1. GROUP TRANSAKSI (BERDASARKAN ID_TRX)
        ===================================================== */
        $grouped = $transactions->groupBy('id_trx');
        $totalTransactions = $grouped->count();

        /* =====================================================
        2. REPRESENTASI VERTIKAL
        item => [trx_id1, trx_id2, ...]
        ===================================================== */
        $vertical = [];

        foreach ($grouped as $trxId => $rows) {

            $uniqueItems = [];

            foreach ($rows as $row) {
                foreach (explode(',', $row->items) as $item) {
                    $item = trim($item);
                    if ($item !== '') {
                        $uniqueItems[$item] = true;
                    }
                }
            }

            foreach (array_keys($uniqueItems) as $item) {
                $vertical[$item][] = $trxId;
            }
        }

        // pastikan tidak ada trx_id ganda
        foreach ($vertical as $item => $trxIds) {
            $vertical[$item] = array_values(array_unique($trxIds));
        }

        /* =====================================================
        3. FREQUENT 1-ITEMSET
        simpan SUPPORT + COUNT ABSOLUT
        ===================================================== */
        $freq = [];

        foreach ($vertical as $item => $trxIds) {
            $count   = count($trxIds);
            $support = $count / $totalTransactions;

            if ($support >= $minSupport) {
                $freq[$item] = [
                    'ids'     => $trxIds,
                    'count'   => $count,  
                    'support' => $support
                ];
            }
        }

        /* =====================================================
        4. ASSOCIATION RULE 2-ITEMSET (A → B)
        ===================================================== */
        $rules = [];
        $items = array_keys($freq);

        for ($i = 0; $i < count($items); $i++) {
            for ($j = $i + 1; $j < count($items); $j++) {

                $A = $items[$i];
                $B = $items[$j];

                // A ∩ B
                $trxAB = array_intersect(
                    $freq[$A]['ids'],
                    $freq[$B]['ids']
                );

                $countAB   = count($trxAB);
                $supportAB = $countAB / $totalTransactions;

                // cek minimum support
                if ($supportAB < $minSupport) {
                    continue;
                }

                // confidence A → B
                $confidenceAB = $countAB / $freq[$A]['count'];
                if ($confidenceAB < $minConfidence) {
                    continue;
                }

                // lift A → B
                $liftAB = $supportAB / (
                    $freq[$A]['support'] * $freq[$B]['support']
                );

                // SIMPAN A → B
                $rules[] = [
                    'rule_from'  => $A,
                    'rule_to'    => $B,
                    'support'    => $supportAB,
                    'confidence' => $confidenceAB,
                    'lift_ratio' => $liftAB,

                    // 🔥 ANGKA ABSOLUT (UNTUK TABEL HASIL)
                    'trx_A'  => $freq[$A]['count'],
                    'trx_B'  => $freq[$B]['count'],
                    'trx_AB' => $countAB,
                ];
            }
        }

        /* =====================================================
        5. RETURN HASIL
        ===================================================== */
        return [
            'single' => $freq, // 1-itemset (dengan count absolut)
            'rules'  => collect($rules)
                            ->sortByDesc('lift_ratio')
                            ->values()
        ];
    }
}
