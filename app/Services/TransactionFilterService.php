<?php

namespace App\Services;

use App\Models\Transaction;

class TransactionFilterService
{
    /**
     * Query transaksi TERPUSAT
     * Dipakai oleh:
     * - API preview
     * - Proses ECLAT
     */
    public static function query(array $params)
    {
        return Transaction::query()

            // =========================
            // BATCH FILTER
            // =========================
            ->when(
                isset($params['batch_year']) &&
                $params['batch_year'] !== 'all',
                fn ($q) => $q->where('batch_year', $params['batch_year'])
            )

            // =========================
            // DATE FILTER
            // =========================
            ->when(
                empty($params['useAllDates']),
                fn ($q) => $q->whereBetween('tanggal', [
                    $params['start_date'],
                    $params['end_date']
                ])
            )

            // =========================
            // DIVISI & KATEGORI
            // =========================
            ->when(
                empty($params['useAllCategories']),
                function ($q) use ($params) {
                    if (!empty($params['divisi'])) {
                        $q->where('divisi', $params['divisi']);
                    }

                    if (!empty($params['kategori'])) {
                        $q->where('kategori', $params['kategori']);
                    }
                }
            );
    }
}
