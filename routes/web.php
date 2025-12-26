<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\ProcessingController;
use App\Http\Controllers\ResultsController;

// Halaman Beranda (Home)
Route::get('/', [HomeController::class, 'index'])->name('beranda');

// Halaman Data Transaksi
Route::get('/data-transaksi', [TransactionController::class, 'index'])->name('data-transaksi');
Route::post('/data-transaksi/upload', [TransactionController::class, 'uploadCsv'])->name('data-transaksi.upload');
Route::delete('/data-transaksi', [TransactionController::class, 'deleteAllData'])->name('data-transaksi.delete');

// Delete Batch
Route::delete('/data-transaksi/batch', [TransactionController::class, 'deleteBatch'])
    ->name('data-transaksi.delete-batch');


// Tambahkan route ini
Route::get('/data-transaksi/clear-vertical', [TransactionController::class, 'clearVerticalData'])->name('data-transaksi.clear-vertical');

// API untuk preview data
// API untuk preview data
Route::get('/api/transactions/filtered', [TransactionController::class, 'getFilteredTransactions'])->name('api.transactions.filtered');
// Route::get('/api/transactions/preview', [ProcessingController::class, 'preview'])->name('api.transactions.preview');



// Halaman Data Proses
Route::get('/data-proses', [ProcessingController::class, 'index'])->name('data-proses');
Route::post('/data-proses', [ProcessingController::class, 'process'])->name('data-proses.submit');
Route::get('/api/transactions/filtered', [ProcessingController::class, 'preview'])->name('api.transactions.filtered');

// Halaman Data Uji/Hasil
Route::get('/data-hasil', [ResultsController::class, 'index'])->name('data-hasil');