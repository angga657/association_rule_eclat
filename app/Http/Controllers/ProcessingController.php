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
        $query = TransactionFilterService::query($request->all())
            ->select(
                'items',
                DB::raw('COUNT(DISTINCT id_trx) as jumlah_transaksi'),
                DB::raw('GROUP_CONCAT(DISTINCT divisi SEPARATOR ", ") as divisi'),
                DB::raw('GROUP_CONCAT(DISTINCT kategori SEPARATOR ", ") as kategori')
            );

        $transactions = $query
            ->groupBy('items')
            ->orderByDesc('jumlah_transaksi')
            ->paginate($request->get('per_page', 10))
            ->appends($request->query());

        /* ===============================
        FILTER BATCH (AMAN)
        =============================== */
        if ($request->filled('batch_year')) {
            $query->where('batch_year', $request->batch_year);
        }
        // ❗ jika batch_year TIDAK ADA → semua tahun

        /* ===============================
        FILTER TANGGAL
        =============================== */
        if (!$request->boolean('useAllDates') &&
            $request->filled('start_date') &&
            $request->filled('end_date')) {

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

        $transactions = $query
            ->groupBy('items')
            ->orderByDesc('jumlah_transaksi')
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
            'pagination' => $transactions->links()->toHtml(),
            'from' => $transactions->firstItem() ?? 0,
            'to' => $transactions->lastItem() ?? 0,
            'total' => $transactions->total(),
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

        $transactions = TransactionFilterService::query($request->all())->get();

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
            return back()->with('warning', 'Tidak ada association rule');
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
                'total_transactions' => $transactions->groupBy('id_trx')->count(),
            ]);

            // 🔥 2️⃣ SINGLE ITEMSET (1-ITEMSET)
            foreach ($singleItemsets as $item => $data) {
                EclatResult::create([
                    'processing_id' => $processing->id,
                    'itemset'       => $item,
                    'rule_from'     => null,
                    'rule_to'       => null,
                    'support'       => $data['support'],
                    'confidence'    => null,
                    'lift_ratio'    => null,
                ]);
            }

            // 🔥 3️⃣ ASSOCIATION RULE (2-ITEMSET)
            foreach ($rules as $row) {
                EclatResult::create([
                    'processing_id' => $processing->id,
                    'itemset'       => null,
                    'rule_from'     => $row['rule_from'],
                    'rule_to'       => $row['rule_to'],
                    'support'       => $row['support'],
                    'confidence'    => $row['confidence'],
                    'lift_ratio'    => $row['lift_ratio'],
                ]);
            }
        });

        return redirect()
            ->route('data-hasil')
            ->with('success', 'ECLAT berhasil diproses');
    }

    /* ===============================
       ECLAT CORE (VERTIKAL)
       =============================== */
    private function runEclatAlgorithm($transactions, $minSupport, $minConfidence)
    {
        $grouped = $transactions->groupBy('id_trx');
        $totalTransactions = $grouped->count();

        // 1️⃣ Vertical representation
        $vertical = [];

        foreach ($grouped as $trxId => $rows) {
            foreach ($rows as $row) {
                foreach (explode(',', $row->items) as $item) {
                    $item = trim($item);
                    $vertical[$item][] = $trxId;
                }
            }
        }

        foreach ($vertical as &$ids) {
            $ids = array_unique($ids);
        }

        // 2️⃣ Frequent 1-itemset
        $freq = [];
        foreach ($vertical as $item => $ids) {
            $support = count($ids) / $totalTransactions;
            if ($support >= $minSupport) {
                $freq[$item] = [
                    'ids' => $ids,
                    'support' => $support
                ];
            }
        }

        $results = [];
        $items = array_keys($freq);

        // 3️⃣ Generate 2-itemset (ECLAT core)
        for ($i = 0; $i < count($items); $i++) {
            for ($j = $i + 1; $j < count($items); $j++) {

                $A = $items[$i];
                $B = $items[$j];

                $intersect = array_intersect(
                    $freq[$A]['ids'],
                    $freq[$B]['ids']
                );

                $supAB = count($intersect) / $totalTransactions;
                if ($supAB < $minSupport) continue;

                $confAB = $supAB / $freq[$A]['support'];
                if ($confAB >= $minConfidence) {
                    $lift = $supAB / ($freq[$A]['support'] * $freq[$B]['support']);

                    // A → B
                    $results[] = [
                        'rule_from'  => $A,
                        'rule_to'    => $B,
                        'support'    => $supAB,
                        'confidence' => $confAB,
                        'lift_ratio' => $lift,
                    ];
                }

                $confBA = $supAB / $freq[$B]['support'];
                if ($confBA >= $minConfidence) {
                    $lift = $supAB / ($freq[$A]['support'] * $freq[$B]['support']);

                    // B → A
                    $results[] = [
                        'rule_from'  => $B,
                        'rule_to'    => $A,
                        'support'    => $supAB,
                        'confidence' => $confBA,
                        'lift_ratio' => $lift,
                    ];
                }
            }
        }

        return [
            'single' => $freq,
            'rules'  => collect($results)->sortByDesc('lift_ratio')->values()
        ];
    }
}
