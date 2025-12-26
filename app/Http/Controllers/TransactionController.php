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
        $search    = $request->get('search');
        $perPage   = $request->get('per_page', 10);
        $batchYear = $request->get('batch_year');

        // ===============================
        // QUERY SINGLE ITEMSET (VERTIKAL)
        // ===============================
        $query = Transaction::select(
            'items',
            DB::raw('COUNT(DISTINCT id_trx) as jumlah_transaksi'),
            DB::raw('GROUP_CONCAT(DISTINCT divisi SEPARATOR ", ") as divisi'),
            DB::raw('GROUP_CONCAT(DISTINCT kategori SEPARATOR ", ") as kategori')
        );

        if ($batchYear) {
            $query->where('batch_year', $batchYear);
        }

        if (!empty($search)) {
            $query->where('items', 'LIKE', "%{$search}%");
        }

        $transactions = $query
            ->groupBy('items')
            ->orderByDesc('jumlah_transaksi')
            ->paginate($perPage)
            ->appends($request->query());

        // ===============================
        // TOTAL TRANSAKSI (UNTUK SUPPORT)
        // ===============================
        $totalTrx = Transaction::when($batchYear, fn($q) =>
            $q->where('batch_year', $batchYear)
        )->distinct('id_trx')->count('id_trx');

        // ===============================
        // LIST BATCH
        // ===============================
        $batches = Transaction::select('batch_year')
            ->distinct()
            ->orderByDesc('batch_year')
            ->pluck('batch_year');

        // ===============================
        // AJAX RESPONSE
        // ===============================
        if ($request->ajax()) {
            return response()->json([
                'table_body' => view(
                    'partials._transactions_table_body',
                    compact('transactions', 'totalTrx')
                )->render(),
                'pagination' => $transactions->links()->toHtml(),
            ]);
        }

        // ===============================
        // NORMAL VIEW
        // ===============================
        return view('data-transaksi', compact(
            'transactions',
            'batches',
            'totalTrx'
        ));
    }

    /**
    * Upload CSV file and import to database
    */
    public function uploadCsv(Request $request)
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(0);
        $validator = Validator::make($request->all(), [
            'csv_file' => 'required|file|mimes:csv,txt|max:512000', // ~500MB
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        $file = $request->file('csv_file');
        $path = $file->getRealPath();

        $requiredHeaders = [
            'Jenis_Trx','Tanggal','ID_Trx','Items','Satuan','Kuantiti',
            'Harga_Jual','Harga_Beli','Diskon','H_Diskon','Total_Harga',
            'Operator','Kosong','Barcode','ID_customer','ID_Distributor',
            'Produk_Out','Kosong.1','Kode_Sub_Kategori','Sub_Kategori',
            'Kode_Kategori','Kategori','Kode_Departemen','Departemen',
            'Customer','Kode_divisi','Divisi'
        ];

        $row = 0;
        $inserted = 0;
        $failed = 0;
        $chunkSize = 300;
        $chunk = [];

        try {
            if (($handle = fopen($path, 'r')) === false) {
                throw new \Exception('File tidak bisa dibuka');
            }

            while (($data = fgetcsv($handle, 0, ',')) !== false) {
                $row++;

                // =========================
                // HEADER
                // =========================
                if ($row === 1) {
                    $headers = $data;

                    foreach ($requiredHeaders as $header) {
                        if (!in_array($header, $headers)) {
                            throw new \Exception("Header '{$header}' tidak ditemukan");
                        }
                    }
                    continue;
                }

                try {
                    // =========================
                    // TANGGAL (MULTI FORMAT)
                    // =========================
                    $dateString = trim($data[array_search('Tanggal', $headers)]);

                    $formats = [
                        'd/m/Y H:i',
                        'd/m/Y',
                        'Y-m-d H:i:s.u',
                        'Y-m-d H:i:s',
                        'Y-m-d'
                    ];

                    $date = null;
                    foreach ($formats as $format) {
                        $date = \DateTime::createFromFormat($format, $dateString);
                        if ($date !== false) break;
                    }

                    if (!$date) {
                        throw new \Exception("Format tanggal tidak valid: {$dateString}");
                    }

                    $batchYear = $date->format('Y');

                    // =========================
                    // NORMALISASI ANGKA
                    // =========================
                    $num = fn($v) => (float) str_replace(',', '.', $v);

                    // =========================
                    // DATA ROW
                    // =========================
                    $chunk[] = [
                        'jenis_trx'        => $data[array_search('Jenis_Trx', $headers)],
                        'tanggal'          => $date->format('Y-m-d H:i:s'),
                        'batch_year'       => $batchYear,
                        'id_trx'           => $data[array_search('ID_Trx', $headers)],
                        'items'            => trim($data[array_search('Items', $headers)]),
                        'satuan'           => $data[array_search('Satuan', $headers)],
                        'kuantiti'         => (int) $num($data[array_search('Kuantiti', $headers)]),
                        'harga_jual'       => $num($data[array_search('Harga_Jual', $headers)]),
                        'harga_beli'       => $num($data[array_search('Harga_Beli', $headers)]),
                        'diskon'           => $num($data[array_search('Diskon', $headers)]),
                        'h_diskon'         => $num($data[array_search('H_Diskon', $headers)]),
                        'total_harga'      => $num($data[array_search('Total_Harga', $headers)]),
                        'operator'         => $data[array_search('Operator', $headers)],
                        'kosong'           => $data[array_search('Kosong', $headers)],
                        'barcode'          => (string) $data[array_search('Barcode', $headers)],
                        'id_customer'      => $data[array_search('ID_customer', $headers)],
                        'id_distributor'   => $data[array_search('ID_Distributor', $headers)],
                        'produk_out'       => $data[array_search('Produk_Out', $headers)],
                        'kosong_1'         => $data[array_search('Kosong.1', $headers)],
                        'kode_sub_kategori'=> $data[array_search('Kode_Sub_Kategori', $headers)],
                        'sub_kategori'     => $data[array_search('Sub_Kategori', $headers)],
                        'kode_kategori'    => $data[array_search('Kode_Kategori', $headers)],
                        'kategori'         => $data[array_search('Kategori', $headers)],
                        'kode_departemen'  => $data[array_search('Kode_Departemen', $headers)],
                        'departemen'       => $data[array_search('Departemen', $headers)],
                        'customer'         => $data[array_search('Customer', $headers)],
                        'kode_divisi'      => $data[array_search('Kode_divisi', $headers)],
                        'divisi'           => $data[array_search('Divisi', $headers)],
                        'created_at'       => now(),
                        'updated_at'       => now(),
                    ];

                    $inserted++;

                    if (count($chunk) >= $chunkSize) {
                        DB::table('transactions')->insert($chunk);
                        $chunk = [];
                    }

                } catch (\Exception $e) {
                    $failed++;
                    \Log::warning("CSV SKIP row {$row}: {$e->getMessage()}");
                }
            }

            if (!empty($chunk)) {
                DB::table('transactions')->insert($chunk);
            }

            fclose($handle);

            return redirect()->route('data-transaksi')->with(
                'success',
                "Import selesai → Berhasil: {$inserted}, Gagal: {$failed}"
            );

        } catch (\Exception $e) {
            if (isset($handle) && is_resource($handle)) {
                fclose($handle);
            }

            return redirect()->back()->with(
                'error',
                'Import gagal: ' . $e->getMessage()
            );
        }
    }
    
    public function deleteBatch(Request $request)
    {
        $request->validate([
            'batch_year' => 'nullable|digits:4'
        ]);

        DB::transaction(function () use ($request) {

            if ($request->batch_year) {

                // =========================
                // 1. Ambil processing ID terkait batch
                // =========================
                $processingIds = EclatProcessing::whereYear('created_at', $request->batch_year)
                    ->pluck('id');

                // =========================
                // 2. Hapus hasil ECLAT dulu (child)
                // =========================
                EclatResult::whereIn('processing_id', $processingIds)->delete();

                // =========================
                // 3. Hapus processing (parent)
                // =========================
                EclatProcessing::whereIn('id', $processingIds)->delete();

                // =========================
                // 4. Hapus transaksi
                // =========================
                Transaction::where('batch_year', $request->batch_year)->delete();

            } else {

                // =========================
                // HAPUS SEMUA DATA (URUTAN WAJIB)
                // =========================
                EclatResult::query()->delete();
                EclatProcessing::query()->delete();
                Transaction::query()->delete();
            }
        });

        return redirect()->back()->with(
            'success',
            $request->batch_year
                ? 'Data batch '.$request->batch_year.' berhasil dihapus.'
                : 'SEMUA data transaksi berhasil dihapus.'
        );
    }

    
    /**
    * Get filtered transaction data for preview (AJAX) with Pagination
    */
    public function getFilteredTransactions(Request $request)
    {
        try {
        // ===============================
        // VALIDASI MINIMAL
        // ===============================
        if (!$request->batch_year) {
            return response()->json([
                'success' => true,
                'data' => [],
                'pagination' => '',
                'from' => 0,
                'to' => 0,
                'total' => 0,
            ]);
        }

        // ===============================
        // TRANSAKSI DUPLIKAT (SUMBER ECLAT)
        // ===============================
        $duplicateIds = Transaction::select('id_trx')
            ->where('batch_year', $request->batch_year)
            ->groupBy('id_trx')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('id_trx');

        $query = Transaction::whereIn('id_trx', $duplicateIds)
            ->where('batch_year', $request->batch_year);

        // ===============================
        // FILTER TANGGAL
        // ===============================
        if ($request->start_date && $request->end_date) {
            $query->whereBetween('tanggal', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        }

        // ===============================
        // FILTER KATEGORI / DIVISI
        // ===============================
        if ($request->divisi) {
            $query->where('divisi', $request->divisi);
        }

        if ($request->kategori) {
            $query->where('kategori', $request->kategori);
        }

        // ===============================
        // QUERY FINAL
        // ===============================
        $transactions = $query
            ->select(
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

        // ===============================
        // TRANSFORM DATA
        // ===============================
        $data = $transactions->getCollection()->map(function ($trx) {
            return [
                'id_trx' => $trx->id_trx,
                'tanggal' => $trx->tanggal->format('d/m/Y'),
                'items' => array_map('trim', explode(',', $trx->items)),
                'kategori' => $trx->kategori,
                'divisi' => $trx->divisi,
                'jumlah_transaksi' => $trx->jumlah_transaksi,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => $transactions->links()->toHtml(),
            'from' => $transactions->firstItem(),
            'to' => $transactions->lastItem(),
            'total' => $transactions->total(),
        ]);

    } catch (\Exception $e) {
        \Log::error('Preview Error: ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'Terjadi kesalahan saat memuat data'
        ], 500);
    }
    }
}