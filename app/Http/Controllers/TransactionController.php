<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\EclatProcessing;
use App\Models\EclatResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    /**
     * Display the data transaksi page.
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $perPage = $request->get('per_page', 10);

        // Query untuk menampilkan SEMUA transaksi
        $query = Transaction::select(
                'id_trx', 
                'tanggal', 
                'items', 
                'kategori', 
                'divisi', 
                'id'
            )
            ->selectRaw('(
                SELECT COUNT(*) 
                FROM transactions t2 
                WHERE t2.id_trx = transactions.id_trx
            ) as jumlah_transaksi');

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('id_trx', 'LIKE', '%' . $search . '%')
                  ->orWhere('items', 'LIKE', '%' . $search . '%')
                  ->orWhere('kategori', 'LIKE', '%' . $search . '%')
                  ->orWhere('divisi', 'LIKE', '%' . $search . '%');
            });
        }

        $transactions = $query->orderBy('id', 'asc')
                              ->paginate($perPage)
                              ->appends($request->query());

        if ($request->ajax()) {
            // Kirim data ke partial view untuk AJAX
            $table_body = view('partials._transactions_table_body', compact('transactions'))->render();
            $pagination = $transactions->links()->toHtml();

            return response()->json([
                'table_body' => $table_body,
                'pagination' => $pagination,
            ]);
        }

        // Kirim data ke view utama
        return view('data-transaksi', compact('transactions'));
    }

    /**
    * Upload CSV file and import to database
    */
    public function uploadCsv(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'csv_file' => 'required|file|mimes:csv,txt|max:500240', // Max 10MB
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $file = $request->file('csv_file');
        $path = $file->getRealPath();
        
        $requiredHeaders = [
            'Jenis_Trx', 'Tanggal', 'ID_Trx', 'Items', 'Satuan', 'Kuantiti', 
            'Harga_Jual', 'Harga_Beli', 'Diskon', 'H_Diskon', 'Total_Harga', 
            'Operator', 'Kosong', 'Barcode', 'ID_customer', 'ID_Distributor', 
            'Produk_Out', 'Kosong.1', 'Kode_Sub_Kategori', 'Sub_Kategori', 
            'Kode_Kategori', 'Kategori', 'Kode_Departemen', 'Departemen', 
            'Customer', 'Kode_divisi', 'Divisi'
        ];

        $importedCount = 0;
        $chunkSize = 1000; // Jumlah baris yang akan disisipkan dalam satu batch

        try {
            // Buka file untuk dibaca baris per baris
            if (($handle = fopen($path, 'r')) !== FALSE) {
                $row = 0;
                $headers = [];
                $chunk = [];

                while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                    $row++;

                    // Proses header di baris pertama
                    if ($row === 1) {
                        $headers = $data;
                        
                        // Validasi header
                        foreach ($requiredHeaders as $header) {
                            if (!in_array($header, $headers)) {
                                fclose($handle);
                                return redirect()->back()->with('error', 'Format CSV tidak valid. Header "' . $header . '" tidak ditemukan.');
                            }
                        }
                        continue; // Lewati ke baris berikutnya
                    }

                    // Siapkan data untuk dimasukkan ke database
                    try {
                        $dateString = $data[array_search('Tanggal', $headers)];
                        $date = \DateTime::createFromFormat('d/m/Y H:i', $dateString);
                        
                        $chunk[] = [
                            'jenis_trx' => $data[array_search('Jenis_Trx', $headers)],
                            'tanggal' => $date,
                            'id_trx' => $data[array_search('ID_Trx', $headers)],
                            'items' => $data[array_search('Items', $headers)],
                            'satuan' => $data[array_search('Satuan', $headers)],
                            'kuantiti' => (int) $data[array_search('Kuantiti', $headers)],
                            'harga_jual' => (float) str_replace(',', '.', $data[array_search('Harga_Jual', $headers)]),
                            'harga_beli' => (float) str_replace(',', '.', $data[array_search('Harga_Beli', $headers)]),
                            'diskon' => (float) str_replace(',', '.', $data[array_search('Diskon', $headers)]),
                            'h_diskon' => (float) str_replace(',', '.', $data[array_search('H_Diskon', $headers)]),
                            'total_harga' => (float) str_replace(',', '.', $data[array_search('Total_Harga', $headers)]),
                            'operator' => $data[array_search('Operator', $headers)],
                            'kosong' => $data[array_search('Kosong', $headers)],
                            'barcode' => $data[array_search('Barcode', $headers)],
                            'id_customer' => $data[array_search('ID_customer', $headers)],
                            'id_distributor' => $data[array_search('ID_Distributor', $headers)],
                            'produk_out' => $data[array_search('Produk_Out', $headers)],
                            'kosong_1' => $data[array_search('Kosong.1', $headers)],
                            'kode_sub_kategori' => $data[array_search('Kode_Sub_Kategori', $headers)],
                            'sub_kategori' => $data[array_search('Sub_Kategori', $headers)],
                            'kode_kategori' => $data[array_search('Kode_Kategori', $headers)],
                            'kategori' => $data[array_search('Kategori', $headers)],
                            'kode_departemen' => $data[array_search('Kode_Departemen', $headers)],
                            'departemen' => $data[array_search('Departemen', $headers)],
                            'customer' => $data[array_search('Customer', $headers)],
                            'kode_divisi' => $data[array_search('Kode_divisi', $headers)],
                            'divisi' => $data[array_search('Divisi', $headers)],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                        
                        $importedCount++;

                        // Jika ukuran chunk sudah tercapai, sisipkan ke database
                        if (count($chunk) >= $chunkSize) {
                            DB::table('transactions')->insert($chunk);
                            $chunk = []; // Kosongkan chunk
                        }

                    } catch (\Exception $e) {
                        // Log error untuk baris yang bermasalah, tapi lanjutkan proses
                        \Log::error("Error importing CSV row {$row}: " . $e->getMessage());
                    }
                }
                
                // Sisipkan sisa data yang ada di chunk terakhir
                if (!empty($chunk)) {
                    DB::table('transactions')->insert($chunk);
                }
                
                fclose($handle);
            }
            
            // --- KODE TRANSFORMASI DATA VERTIKAL (DIPERTAHANKAN TAPI TIDAK DITAMPILKAN) ---
            try {
                // Get transactions with duplicate IDs (sumber data untuk transformasi)
                $duplicateTransactions = Transaction::select('id_trx', 'items')
                    ->whereIn('id_trx', function($query) {
                        $query->select('id_trx')
                              ->from('transactions')
                              ->groupBy('id_trx')
                              ->havingRaw('COUNT(*) > 1');
                    })
                    ->get();

                // Hitung total transaksi unik (untuk perhitungan support)
                $totalTransactions = $duplicateTransactions->unique('id_trx')->count();
                
                // Buat struktur vertikal: item => [id_trx, id_trx, ...]
                $verticalDataMap = [];
                foreach ($duplicateTransactions as $transaction) {
                    $items = explode(',', $transaction->items);
                    $idTrx = $transaction->id_trx;
                    
                    foreach ($items as $item) {
                        $item = trim($item);
                        if (!isset($verticalDataMap[$item])) {
                            $verticalDataMap[$item] = [];
                        }
                        if (!in_array($idTrx, $verticalDataMap[$item])) {
                            $verticalDataMap[$item][] = $idTrx;
                        }
                    }
                }

                // Format data untuk ditampilkan, hitung support, dan filter
                $resultVertical = [];
                foreach ($verticalDataMap as $item => $transactionIds) {
                    $jumlahData = count($transactionIds);
                    $support = $jumlahData / $totalTransactions;

                    // Filter berdasarkan minimum support (misal 0.01 seperti di Python)
                    if ($support >= 0.01) {
                        $resultVertical[] = [
                            'items' => $item,
                            'id_trx_list' => implode(', ', $transactionIds),
                            'jumlah_data' => $jumlahData,
                            'support' => $support
                        ];
                    }
                }

                // Urutkan berdasarkan support tertinggi
                usort($resultVertical, function($a, $b) {
                    return $b['support'] <=> $a['support'];
                });

                // PERUBAHAN: Tidak lagi menyimpan ke session, transformasi dilakukan secara internal
                // session()->put('vertical_data', $resultVertical);

            } catch (\Exception $e) {
                \Log::error('Error during vertical transformation: ' . $e->getMessage());
                // Jika transformasi gagal, tetap lanjutkan tapi beri tahu user
                session()->flash('warning', 'Data berhasil diimpor, tetapi gagal menampilkan hasil transformasi.');
            }
            // --- AKHIR KODE TRANSFORMASI ---

            return redirect()->route('data-transaksi')->with('success', 'Berhasil mengimpor ' . $importedCount . ' data transaksi.');

        } catch (\Exception $e) {
            // Pastikan file ditutup jika terjadi error
            if (isset($handle) && is_resource($handle)) {
                fclose($handle);
            }
            return redirect()->back()->with('error', 'Gagal memproses file: ' . $e->getMessage());
        }
    }
    
    /**
     * Delete all transaction data
     */
    public function deleteAllData()
    {
        try {
            Transaction::truncate();
            EclatResult::truncate();
            EclatProcessing::truncate();
            
            return redirect()->back()->with('success', 'Semua data transaksi dan hasil ECLAT berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }
    
    /**
    * Get filtered transaction data for preview (AJAX) with Pagination
    */
    public function getFilteredTransactions(Request $request)
    {
        try {
            // Get transactions with duplicate ID_Trx (sumber data untuk proses ECLAT)
            $duplicateIds = Transaction::select('id_trx')
                ->groupBy('id_trx')
                ->havingRaw('COUNT(*) > 1')
                ->pluck('id_trx');
            
            $query = Transaction::whereIn('id_trx', $duplicateIds);
            
            // Date filter
            if (!$request->has('useAllDates') && $request->start_date && $request->end_date) {
                $query->whereBetween('tanggal', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
            }
            
            // Category & Division filter
            if (!$request->has('useAllCategories')) {
                if ($request->divisi) {
                    $query->where('divisi', $request->divisi);
                }
                if ($request->kategori) {
                    $query->where('kategori', $request->kategori);
                }
            }
            
            // Select columns and add the subquery for count to avoid N+1 problem
            $transactions = $query->select(
                    'id_trx', 
                    'tanggal', 
                    'items', 
                    'kategori', 
                    'divisi'
                )
                ->selectRaw('(
                    SELECT COUNT(*) 
                    FROM transactions t2 
                    WHERE t2.id_trx = transactions.id_trx
                ) as jumlah_transaksi')
                ->orderBy('tanggal', 'desc')
                ->paginate($request->get('per_page', 10));

            // Transformasi data untuk setiap item di halaman saat ini
            $transformedData = $transactions->getCollection()->map(function ($transaction) {
                return [
                    'id_trx' => $transaction->id_trx,
                    'tanggal' => $transaction->tanggal->format('d/m/Y'),
                    'items' => array_map('trim', explode(',', $transaction->items)),
                    'kategori' => $transaction->kategori,
                    'divisi' => $transaction->divisi,
                    'jumlah_transaksi' => $transaction->jumlah_transaksi, // Gunakan nilai dari subquery
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $transformedData,
                'pagination' => $transactions->links()->toHtml(),
                'from' => $transactions->firstItem(),
                'to' => $transactions->lastItem(),
                'total' => $transactions->total(),
            ]);
                
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}