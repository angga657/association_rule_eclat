@extends('layouts.app')

@section('title', 'Beranda - Association Rule ECLAT')

@section('content')
<div class="p-8">
    <h2 class="text-2xl font-bold mb-6">Beranda</h2>
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h3 class="text-xl font-semibold mb-4">Selamat datang</h3>
        <p class="text-gray-600 mb-6">Aplikasi Analisis Asosiasi Pembelian Produk Menggunakan Algoritma Eclat</p>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <a href="{{ route('data-transaksi') }}" class="bg-purple-50 rounded-lg p-6 text-center hover:shadow-md transition">
                <div class="bg-purple-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-exchange-alt text-purple-600 text-2xl"></i>
                </div>
                <h4 class="font-semibold text-lg mb-2">Data Transaksi</h4>
                <p class="text-gray-600 text-sm">Kelola data transaksi untuk analisis</p>
            </a>
            
            <a href="{{ route('data-proses') }}" class="bg-blue-50 rounded-lg p-6 text-center hover:shadow-md transition">
                <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-cogs text-blue-600 text-2xl"></i>
                </div>
                <h4 class="font-semibold text-lg mb-2">Data Proses</h4>
                <p class="text-gray-600 text-sm">Proses data dengan algoritma Eclat</p>
            </a>
            
            <a href="{{ route('data-uji') }}" class="bg-green-50 rounded-lg p-6 text-center hover:shadow-md transition">
                <div class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-vial text-green-600 text-2xl"></i>
                </div>
                <h4 class="font-semibold text-lg mb-2">Data Uji</h4>
                <p class="text-gray-600 text-sm">Lihat hasil analisis dan pengujian</p>
            </a>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-xl font-semibold mb-4">Tentang Aplikasi</h3>
        <p class="text-gray-600 mb-4">Aplikasi ini menggunakan algoritma Eclat untuk menemukan aturan asosiasi dari data transaksi pembelian produk. Algoritma Eclat (Equivalence Class Transformation) adalah salah satu metode yang efisien untuk menemukan itemset frekuensi dalam data transaksi.</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
            <div class="flex items-start">
                <i class="fas fa-check-circle text-green-500 mt-1 mr-3"></i>
                <div>
                    <h4 class="font-semibold">Analisis Cepat</h4>
                    <p class="text-gray-600 text-sm">Proses analisis data transaksi dengan kecepatan tinggi</p>
                </div>
            </div>
            <div class="flex items-start">
                <i class="fas fa-check-circle text-green-500 mt-1 mr-3"></i>
                <div>
                    <h4 class="font-semibold">Hasil Akurat</h4>
                    <p class="text-gray-600 text-sm">Menghasilkan aturan asosiasi yang akurat dan relevan</p>
                </div>
            </div>
            <div class="flex items-start">
                <i class="fas fa-check-circle text-green-500 mt-1 mr-3"></i>
                <div>
                    <h4 class="font-semibold">Parameter Fleksibel</h4>
                    <p class="text-gray-600 text-sm">Dapat mengatur minimum support dan confidence</p>
                </div>
            </div>
            <div class="flex items-start">
                <i class="fas fa-check-circle text-green-500 mt-1 mr-3"></i>
                <div>
                    <h4 class="font-semibold">Visualisasi Data</h4>
                    <p class="text-gray-600 text-sm">Menampilkan hasil analisis dalam bentuk tabel yang mudah dipahami</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection