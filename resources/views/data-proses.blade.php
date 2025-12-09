@extends('layouts.app')

@section('title', 'Data Proses - Association Rule ECLAT')

@section('content')
<div class="p-8">
    <!-- Flash Messages -->
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            {{ session('error') }}
        </div>
    @endif
    
    @if(session('warning'))
        <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded mb-6">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            {{ session('warning') }}
        </div>
    @endif
    
    @if(session('info'))
        <div class="bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 rounded mb-6">
            <i class="fas fa-info-circle mr-2"></i>
            {{ session('info') }}
        </div>
    @endif
    
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-6">
            <i class="fas fa-check-circle mr-2"></i>
            {{ session('success') }}
        </div>
    @endif

    <h2 class="text-2xl font-bold mb-6">Data Proses</h2>
    
    <!-- Filter Section -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-xl font-semibold mb-4">Filter Data Transaksi</h3>
        <form action="{{ route('data-proses.submit') }}" method="POST" id="processingForm">
            @csrf
            
            <!-- Date Filter -->
            <div class="mb-6 p-4 bg-blue-50 rounded-lg">
                <h4 class="font-semibold mb-3 text-blue-800">
                    <i class="fas fa-calendar-alt mr-2"></i>Filter Tanggal
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Tanggal Mulai</label>
                        <input type="date" name="start_date" id="startDate" class="w-full border rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Tanggal Selesai</label>
                        <input type="date" name="end_date" id="endDate" class="w-full border rounded-lg px-3 py-2">
                    </div>
                </div>
                <div class="mt-3 flex items-center">
                    <input type="checkbox" id="useAllDates" name="useAllDates" class="mr-2" value="1">
                    <label for="useAllDates" class="text-sm text-gray-600">Gunakan semua tanggal</label>
                </div>
            </div>

            <!-- Category & Division -->
            <div class="mb-6 p-4 bg-green-50 rounded-lg">
                <h4 class="font-semibold mb-3 text-green-800">
                    <i class="fas fa-tags mr-2"></i>Filter Kategori & Divisi
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Divisi</label>
                        <select name="divisi" id="divisiFilter" class="w-full border rounded-lg px-3 py-2">
                            <option value="">-- Semua Divisi --</option>
                            @foreach($divisiList as $d)
                                <option value="{{ $d }}">{{ $d }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Kategori</label>
                        <select name="kategori" id="kategoriFilter" class="w-full border rounded-lg px-3 py-2">
                            <option value="">-- Semua Kategori --</option>
                        </select>
                    </div>
                </div>
                <div class="mt-3 flex items-center">
                    <input type="checkbox" id="useAllCategories" name="useAllCategories" class="mr-2" value="1">
                    <label for="useAllCategories" class="text-sm text-gray-600">Gunakan semua kategori dan divisi</label>
                </div>
            </div>

            <!-- ECLAT Parameters -->
            <div class="mb-6">
                <h4 class="font-semibold mb-3">
                    <i class="fas fa-sliders-h mr-2"></i>Parameter Algoritma ECLAT
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Min Support</label>
                        <div class="relative">
                            <input type="number" name="min_support" step="0.01" min="0" max="1" class="w-full border rounded-lg px-3 py-2" value="0.1">
                        </div>
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Min Confidence</label>
                        <div class="relative">
                            <input type="number" name="min_confidence" step="0.01" min="0" max="1" class="w-full border rounded-lg px-3 py-2" value="0.8">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-600">
                    <i class="fas fa-info-circle mr-1"></i>
                    Data akan diproses berdasarkan filter dan parameter di atas
                </div>
                <div class="space-x-2">
                    <button type="button" onclick="resetForm()" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
                        <i class="fas fa-undo mr-2"></i>Reset
                    </button>
                    <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700">
                        <i class="fas fa-play mr-2"></i>Proses Data
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Filter Info -->
    <div id="filterInfo" class="mb-4 p-3 bg-blue-50 rounded-lg hidden">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
            <div class="text-blue-800">
                <i class="fas fa-calendar mr-2"></i>
                <span id="dateFilterInfo">Semua tanggal</span>
            </div>
            <div class="text-green-800">
                <i class="fas fa-tags mr-2"></i>
                <span id="categoryFilterInfo">Semua kategori & divisi</span>
            </div>
        </div>
    </div>

    <!-- Loading -->
    <div id="loadingState" class="text-center py-8 text-gray-500 hidden">
        <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
        <p>Memuat data...</p>
    </div>

    <!-- Data Table -->
    <div class="overflow-x-auto" id="dataTableContainer">
        <table class="min-w-full table-auto">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID Transaksi</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Items</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kategori</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Divisi</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jumlah Transaksi</th>
                </tr>
            </thead>
            <tbody id="transactionTableBody" class="bg-white divide-y divide-gray-200">
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                        <i class="fas fa-database text-2xl mb-2"></i>
                        <p>Klik "Refresh" untuk memuat data dari database</p>
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="flex justify-between items-center mt-4">
            <div>Menampilkan <span id="currentItemCount">0</span> dari <span id="totalItemCount">0</span> data</div>
            <div id="pagination-container"></div>
        </div>
    </div>

    <!-- Empty State -->
    <div id="emptyState" class="text-center py-8 text-gray-500 hidden">
        <i class="fas fa-inbox text-4xl mb-3"></i>
        <p>Tidak ada data transaksi untuk filter yang dipilih</p>
    </div>
</div>

<script>
/* ============================================================
   BACKEND DATA (KATEGORI PER DIVISI)
============================================================ */
const kategoriByDivisi = @json($kategoriByDivisi);

/* ============================================================
   SET DEFAULT DATE + LOAD DATA ON START
============================================================ */
document.addEventListener('DOMContentLoaded', function() {

    // FORCE dropdown aktif saat load
    document.getElementById('useAllCategories').checked = false;
    document.getElementById('divisiFilter').disabled = false;
    document.getElementById('kategoriFilter').disabled = false;

    const today = new Date();
    const thirty = new Date(today);
    thirty.setDate(today.getDate() - 30);

    document.getElementById('startDate').value = thirty.toISOString().split('T')[0];
    document.getElementById('endDate').value = today.toISOString().split('T')[0];

    loadPreviewData();

    setupPaginationListener();
});

/* ============================================================
   UPDATE KATEGORI SAAT DIVISI DIGANTI
============================================================ */
document.getElementById('divisiFilter').addEventListener('change', function () {
    const kategoriSelect = document.getElementById('kategoriFilter');
    const selectedDivisi = this.value;

    kategoriSelect.innerHTML = '<option value="">-- Semua Kategori --</option>';

    if (selectedDivisi && kategoriByDivisi[selectedDivisi]) {
        kategoriByDivisi[selectedDivisi].forEach(k => {
            kategoriSelect.innerHTML += `<option value="${k}">${k}</option>`;
        });
    }

    loadPreviewData();
});

/* ============================================================
   HANDLE KATEGORI DIGANTI
============================================================ */
document.getElementById('kategoriFilter').addEventListener('change', function () {
    const selectedDivisi = document.getElementById('divisiFilter').value;
    const selectedKategori = this.value;

    if (selectedKategori && selectedDivisi && !kategoriByDivisi[selectedDivisi]?.includes(selectedKategori)) {

        document.getElementById('divisiFilter').value = '';

        const kategoriSelect = document.getElementById('kategoriFilter');
        kategoriSelect.innerHTML = '<option value="">-- Semua Kategori --</option>';

        const allCats = [...new Set([].concat(...Object.values(kategoriByDivisi)))];
        allCats.forEach(k => kategoriSelect.innerHTML += `<option value="${k}">${k}</option>`);
    }

    loadPreviewData();
});

/* ============================================================
   HANDLE CHECKBOX ALL CATEGORIES
============================================================ */
document.getElementById('useAllCategories').addEventListener('change', function () {
    const divisi = document.getElementById('divisiFilter');
    const kategori = document.getElementById('kategoriFilter');

    const isChecked = this.checked;

    divisi.disabled = isChecked;
    kategori.disabled = isChecked;

    // jika uncheck → reload kategori sesuai divisi terpilih
    if (!isChecked) {
        divisi.dispatchEvent(new Event('change'));
    }

    loadPreviewData();
});

/* ============================================================
   HANDLE CHECKBOX ALL DATES
============================================================ */
document.getElementById('useAllDates').addEventListener('change', loadPreviewData);

/* ============================================================
   HANDLE DATE RANGE CHANGE
============================================================ */
document.getElementById('startDate').addEventListener('change', loadPreviewData);
document.getElementById('endDate').addEventListener('change', loadPreviewData);

/* ============================================================
   RENDER DATA KE TABEL
============================================================ */
function renderPreviewData(result) {
    const loading = document.getElementById('loadingState');
    const empty = document.getElementById('emptyState');
    const tableBox = document.getElementById('dataTableContainer');
    const tbody = document.getElementById('transactionTableBody');
    const curr = document.getElementById('currentItemCount');
    const total = document.getElementById('totalItemCount');
    const paging = document.getElementById('pagination-container');

    if (result.success && result.data.length > 0) {
        loading.classList.add('hidden');
        empty.classList.add('hidden');
        tableBox.classList.remove('hidden');

        tbody.innerHTML = result.data
            .map(item => `
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium">${item.id_trx}</td>
                    <td class="px-4 py-3">${item.tanggal}</td>
                    <td class="px-4 py-3">
                        <div class="max-w-xs truncate" title="${item.items.join(', ')}">
                            ${item.items.join(', ')}
                        </div>
                    </td>
                    <td class="px-4 py-3">${item.kategori}</td>
                    <td class="px-4 py-3">${item.divisi}</td>
                    <td class="px-4 py-3">${item.jumlah_transaksi}</td>
                </tr>
        `).join('');

        curr.textContent = `${result.from} - ${result.to}`;
        total.textContent = result.total;

        paging.innerHTML = result.pagination;
        setupPaginationListener();
    } else {
        loading.classList.add('hidden');
        tableBox.classList.add('hidden');
        empty.classList.remove('hidden');
        total.textContent = 0;
    }
}

/* ============================================================
   LOAD PREVIEW DATA (AJAX)
============================================================ */
async function loadPreviewData(page = 1) {

    const start = document.getElementById('startDate').value;
    const end = document.getElementById('endDate').value;

    const useAllDates = document.getElementById('useAllDates').checked;
    const useAllCategories = document.getElementById('useAllCategories').checked;

    const divisi = document.getElementById('divisiFilter').value;
    const kategori = document.getElementById('kategoriFilter').value;

    updateFilterInfo(start, end, useAllDates, divisi, kategori, useAllCategories);

    document.getElementById('loadingState').classList.remove('hidden');
    document.getElementById('dataTableContainer').classList.add('hidden');
    document.getElementById('emptyState').classList.add('hidden');

    try {
        const params = new URLSearchParams({
            page,
            per_page: 10
        });

        if (!useAllDates) {
            params.append('start_date', start);
            params.append('end_date', end);
        }

        if (useAllCategories) {
            params.append('useAllCategories', 'true');
        } else {
            if (divisi) params.append('divisi', divisi);
            if (kategori) params.append('kategori', kategori);
        }

        const response = await fetch(`{{ route('api.transactions.filtered') }}?${params}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        const result = await response.json();
        renderPreviewData(result);

    } catch (err) {
        console.error(err);
        document.getElementById('emptyState').classList.remove('hidden');
    }
}

/* ============================================================
   PAGINATION TANPA REFRESH + ANTI JUMPING
============================================================ */
function setupPaginationListener() {
    const box = document.getElementById('pagination-container');

    box.onclick = function (event) {
        const link = event.target.closest('a');
        if (!link) return;

        event.preventDefault();
        link.blur();

        const page = new URL(link.href).searchParams.get('page');
        if (page) loadPreviewData(Number(page));
    };
}

/* ============================================================
   UPDATE LABEL FILTER
============================================================ */
function updateFilterInfo(start, end, allDates, divisi, kategori, allCats) {
    const dateText = document.getElementById('dateFilterInfo');
    const catText = document.getElementById('categoryFilterInfo');

    if (allDates) {
        dateText.textContent = "Semua tanggal";
    } else {
        dateText.textContent = `${formatDate(start)} - ${formatDate(end)}`;
    }

    if (allCats) {
        catText.textContent = "Semua kategori & divisi";
    } else if (divisi && kategori) {
        catText.textContent = `${divisi} - ${kategori}`;
    } else if (divisi) {
        catText.textContent = `Divisi: ${divisi}`;
    } else {
        catText.textContent = "Filter kategori dipilih";
    }
}

function formatDate(d) {
    return new Date(d).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
}

/* ============================================================
   RESET FORM
============================================================ */
function resetForm() {
    const form = document.getElementById('processingForm');
    form.reset();

    document.getElementById('useAllDates').checked = false;
    document.getElementById('useAllCategories').checked = false;

    const today = new Date();
    const thirty = new Date(today);
    thirty.setDate(today.getDate() - 30);

    document.getElementById('startDate').value = thirty.toISOString().split('T')[0];
    document.getElementById('endDate').value = today.toISOString().split('T')[0];

    document.getElementById('divisiFilter').disabled = false;
    document.getElementById('kategoriFilter').disabled = false;

    loadPreviewData();
}
</script>

@endsection
