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

// Tambahkan route ini
Route::get('/data-transaksi/clear-vertical', [TransactionController::class, 'clearVerticalData'])->name('data-transaksi.clear-vertical');

// API untuk preview data
Route::get('/api/transactions/filtered', [TransactionController::class, 'getFilteredTransactions'])->name('api.transactions.filtered');

// Halaman Data Proses
Route::get('/data-proses', [ProcessingController::class, 'index'])->name('data-proses');
Route::post('/data-proses', [ProcessingController::class, 'process'])->name('data-proses.submit');

// Halaman Data Uji/Hasil
Route::get('/data-uji', [ResultsController::class, 'index'])->name('data-uji');