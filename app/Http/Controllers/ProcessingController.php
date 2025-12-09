<?php

namespace App\Http\Controllers;

use App\Models\EclatProcessing;
use App\Models\EclatResult;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProcessingController extends Controller
{
    /**
     * Display the data proses page.
     */
    public function index()
    {
        // Default: show last 30 days
        $today = now();
        $thirtyDaysAgo = now()->subDays(30);
        
        $startDate = $thirtyDaysAgo->format('Y-m-d');
        $endDate = $today->format('Y-m-d');
        
        // Get unique divisi from database
        $divisiList = Transaction::distinct('divisi')
            ->whereNotNull('divisi')
            ->where('divisi', '!=', '')
            ->pluck('divisi')
            ->sort()
            ->values();
        
        // Get kategori grouped by divisi
        $kategoriByDivisi = Transaction::select('divisi', 'kategori')
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
        
        // Tidak perlu preview data lagi, karena akan diambil via AJAX
        return view('data-proses', compact('startDate', 'endDate', 'divisiList', 'kategoriByDivisi'));
    }

    /**
    * Process the data with ECLAT algorithm.
    */
    public function process(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'min_support' => 'required|numeric|min:0|max:1',
                'min_confidence' => 'required|numeric|min:0|max:1',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput()
                    ->with('error', 'Validasi gagal. Silakan periksa kembali input Anda.');
            }

            // Get filtered transactions based on filters
            $query = Transaction::query();
            
            // Date filter
            if (!$request->has('useAllDates')) {
                $query->whereBetween('tanggal', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
            }
            
            // Category & Division filter
            if (!$request->has('useAllCategories')) {
                if ($request->filled('divisi')) {
                    $query->where('divisi', $request->divisi);
                }
                if ($request->filled('kategori')) {
                    $query->where('kategori', $request->kategori);
                }
            }
            
            // Get transactions with duplicate ID_Trx
            $duplicateTransactions = $this->getTransactionsWithDuplicateId($query);
            
            if ($duplicateTransactions->isEmpty()) {
                return redirect()->back()
                    ->with('error', 'Tidak ada data transaksi yang memenuhi kriteria filter. Silakan pilih filter yang berbeda.')
                    ->withInput();
            }
            
            // Show processing message
            session()->flash('info', 'Sedang memproses data dengan algoritma ECLAT...');
            
            // Run ECLAT algorithm
            $eclatResults = $this->runEclatAlgorithm(
                $duplicateTransactions, 
                $request->min_support, 
                $request->min_confidence
            );
            
            if ($eclatResults->isEmpty()) {
                return redirect()->back()
                    ->with('warning', 'Tidak ada aturan asosiasi yang ditemukan dengan parameter yang diberikan. Silakan coba dengan nilai support/confidence yang lebih rendah.')
                    ->withInput();
            }
            
            // Save processing parameters (handle null values)
            $processing = EclatProcessing::create([
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'min_support' => $request->min_support,
                'min_confidence' => $request->min_confidence,
                'kategori' => $request->filled('kategori') ? $request->kategori : null,
                'divisi' => $request->filled('divisi') ? $request->divisi : null,
                'jenis_trx' => $request->filled('jenis_trx') ? $request->jenis_trx : null,
                'customer_type' => $request->filled('customer_type') ? $request->customer_type : null,
                'total_transactions' => $duplicateTransactions->count(),
            ]);
            
            // Save ECLAT results
            $savedResults = 0;
            foreach ($eclatResults as $result) {
                EclatResult::create([
                    'processing_id' => $processing->id,
                    'itemset' => $result['itemset'],
                    'support' => $result['support'],
                    'confidence' => $result['confidence'],
                    'lift_ratio' => $result['lift_ratio'],
                ]);
                $savedResults++;
            }
            
            session()->flash('success', 'Data berhasil diproses dengan algoritma ECLAT! Ditemukan ' . $savedResults . ' aturan asosiasi dari ' . $duplicateTransactions->count() . ' transaksi.');
            
            return redirect()->route('data-proses')->withInput();
            
        } catch (\Exception $e) {
            \Log::error('Error in process: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan saat memproses data: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Get transactions with duplicate ID_Trx
     */
    private function getTransactionsWithDuplicateId($query)
    {
        // Get all transactions with the same ID_Trx
        $subquery = Transaction::select('id_trx')
            ->groupBy('id_trx')
            ->havingRaw('COUNT(*) > 1');
            
        return $query->whereIn('id_trx', $subquery)
            ->orderBy('id_trx')
            ->get();
    }
    
    /**
     * Run ECLAT algorithm (PHP implementation)
     */
    private function runEclatAlgorithm($transactions, $minSupport, $minConfidence)
    {
        // Convert to array for easier processing
        $transactionsArray = $transactions->toArray();
        $totalTransactions = count($transactionsArray);
        
        // Create vertical format: item => [transaction_ids]
        $verticalData = [];
        
        foreach ($transactionsArray as $transaction) {
            $items = explode(',', $transaction['items']);
            $idTrx = $transaction['id_trx'];
            
            foreach ($items as $item) {
                $item = trim($item);
                if (!isset($verticalData[$item])) {
                    $verticalData[$item] = [];
                }
                
                if (!in_array($idTrx, $verticalData[$item])) {
                    $verticalData[$item][] = $idTrx;
                }
            }
        }
        
        // Calculate support for each item (frequent 1-itemsets)
        $frequentItemsets = [];
        
        foreach ($verticalData as $item => $transactionIds) {
            $support = count($transactionIds) / $totalTransactions;
            
            if ($support >= $minSupport) {
                $frequentItemsets[$item] = [
                    'transaction_ids' => $transactionIds,
                    'support' => $support
                ];
            }
        }
        
        // Generate results array
        $results = [];
        
        // 1. Add single itemsets (frequent 1-itemsets)
        foreach ($frequentItemsets as $item => $data) {
            $results[] = [
                'itemset' => $item,
                'support' => $data['support'],
                'confidence' => 1.0, // Single item selalu memiliki confidence 1.0
                'lift_ratio' => 1.0, // Single item selalu memiliki lift 1.0
                'type' => 'single' // Tambahkan tipe untuk membedakan
            ];
        }
        
        // 2. Generate 2-itemsets and calculate confidence and lift
        $items = array_keys($frequentItemsets);
        $itemCount = count($items);
        
        for ($i = 0; $i < $itemCount; $i++) {
            for ($j = $i + 1; $j < $itemCount; $j++) {
                $itemA = $items[$i];
                $itemB = $items[$j];
                
                // Find common transaction IDs
                $commonIds = array_intersect(
                    $frequentItemsets[$itemA]['transaction_ids'],
                    $frequentItemsets[$itemB]['transaction_ids']
                );
                
                if (empty($commonIds)) {
                    continue;
                }
                
                $supportAB = count($commonIds) / $totalTransactions;
                
                if ($supportAB < $minSupport) {
                    continue;
                }
                
                // Calculate confidence: P(B|A) = support(A∩B) / support(A)
                $confidenceAtoB = $supportAB / $frequentItemsets[$itemA]['support'];
                $confidenceBtoA = $supportAB / $frequentItemsets[$itemB]['support'];
                
                // Calculate lift: lift(A,B) = support(A∩B) / (support(A) * support(B))
                $lift = $supportAB / ($frequentItemsets[$itemA]['support'] * $frequentItemsets[$itemB]['support']);
                
                // Add rules that meet minimum confidence
                if ($confidenceAtoB >= $minConfidence) {
                    $results[] = [
                        'itemset' => "{$itemA} → {$itemB}",
                        'support' => $supportAB,
                        'confidence' => $confidenceAtoB,
                        'lift_ratio' => $lift,
                        'type' => 'pair'
                    ];
                }
                
                if ($confidenceBtoA >= $minConfidence) {
                    $results[] = [
                        'itemset' => "{$itemB} → {$itemA}",
                        'support' => $supportAB,
                        'confidence' => $confidenceBtoA,
                        'lift_ratio' => $lift,
                        'type' => 'pair'
                    ];
                }
            }
        }
        
        // Sort by lift ratio descending, but put single items first
        usort($results, function($a, $b) {
            // Single items selalu di atas
            if ($a['type'] === 'single' && $b['type'] !== 'single') {
                return -1;
            }
            if ($b['type'] === 'single' && $a['type'] !== 'single') {
                return 1;
            }
            
            // Untuk tipe yang sama, sort berdasarkan lift ratio
            return $b['lift_ratio'] <=> $a['lift_ratio'];
        });
        
        return collect($results);
    }
}