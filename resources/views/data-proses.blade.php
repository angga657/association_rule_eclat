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
            
            <!-- Batch Filter -->
            <div class="mb-4">
                <label class="font-semibold">Batch Tahun</label>
                <select id="batch_year" name="batch_year"
                    class="w-full border rounded px-3 py-2" required>
                    <option value="">-- Pilih Batch --</option>
                    <option value="all">Semua Tahun</option>
                    @foreach($batchYears as $year)
                        <option value="{{ $year }}">{{ $year }}</option>
                    @endforeach
                </select>
            </div>
            <!-- Date Filter -->
            <div class="mb-4">
                <label class="font-semibold">Rentang Tanggal</label>

                <div class="flex gap-2">
                    <input type="date" id="start_date" name="start_date"
                        class="border rounded px-3 py-2 w-full">

                    <input type="date" id="end_date" name="end_date"
                        class="border rounded px-3 py-2 w-full">
                </div>

                <label class="inline-flex items-center mt-2">
                    <input type="checkbox" id="useAllDates" name="useAllDates" value="1">
                    <span class="ml-2">Gunakan seluruh tanggal dalam batch</span>
                </label>
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
                            <input type="number" name="min_support" step="0.00001" min="0" max="1" class="w-full border rounded-lg px-3 py-2" value="0.1">
                        </div>
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Min Confidence</label>
                        <div class="relative">
                            <input type="number" name="min_confidence" step="0.00001" min="0" max="1" class="w-full border rounded-lg px-3 py-2" value="0.1">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="font-semibold ">
                Total Transaksi: 
                <span id="totalTrxUnik">0</span> Transaksi
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
                <span id="categoryFilterInfo">Semua Divisi & Kategori</span>
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
                    <th class="px-4 py-2">No</th>
                    <th class="px-4 py-2">Items</th>
                    <th class="px-4 py-2">Divisi</th>
                    <th class="px-4 py-2">Kategori</th>
                    <th class="px-4 py-2 text-center">Jumlah Transaksi</th>
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

        <div id="paginationContainer" class="mt-4"></div>

        <div class="text-sm text-gray-700 space-y-1">
            <div>
                Showing <span id="currentItemCount">0</span>
                to <span id="currentItemTo">0</span>
                of <span id="totalItemCount">0</span> entries
            </div>

            
        </div>
    </div>

    <!-- Empty State -->
    <div id="emptyState" class="text-center py-8 text-gray-500 hidden">
        <i class="fas fa-inbox text-4xl mb-3"></i>
        <p>Tidak ada data transaksi untuk filter yang dipilih</p>
    </div>
</div>

<script>
const kategoriByDivisi = @json($kategoriByDivisi);

/* ===============================
   Reset 
=============================== */
function resetForm() {

    // Reset form native
    document.getElementById('processingForm').reset();

    // ===== STATE DEFAULT =====
    useAllDates.checked = true;
    useAllCategories.checked = false;

    // Aktifkan filter
    divisiFilter.disabled = false;
    kategoriFilter.disabled = false;

    kategoriFilter.innerHTML = '<option value="">-- Semua Kategori --</option>';

    // 🔥 BATCH KEMBALI KE PILIH BATCH
    batch_year.value = '';

    // Reset tanggal
    start_date.value = '';
    end_date.value = '';

    // ===== RESET TABEL =====
    transactionTableBody.innerHTML = `
        <tr>
            <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                <i class="fas fa-database text-2xl mb-2"></i>
                <p>Pilih batch untuk menampilkan data</p>
            </td>
        </tr>
    `;

    paginationContainer.innerHTML = '';
    currentItemCount.textContent = 0;
    currentItemTo.textContent = 0;
    totalItemCount.textContent = 0;

    emptyState.classList.add('hidden');
    dataTableContainer.classList.remove('hidden');

    // ⛔ JANGAN loadPreviewData()
}

/* ===============================
   Scroll 
=============================== */
function scrollToTable() {
    const table = document.getElementById('dataTableContainer');
    if (!table) return;

    const yOffset = -20; // offset kecil biar tidak terlalu mepet
    const y = table.getBoundingClientRect().top + window.pageYOffset + yOffset;

    window.scrollTo({
        top: y,
        behavior: 'smooth'
    });
}


/* ===============================
   ON LOAD
=============================== */
document.addEventListener('DOMContentLoaded', () => {
    useAllDates.checked = true;
    batch_year.value = 'all';
    loadPreviewData();
});

/* ===============================
   EVENT LISTENER
=============================== */
batch_year.addEventListener('change', () => {
    useAllDates.checked = true;
    start_date.value = '';
    end_date.value = '';
    loadPreviewData();
});

useAllDates.addEventListener('change', () => {
    if (useAllDates.checked) {
        start_date.value = '';
        end_date.value = '';
    }
    loadPreviewData();
});

start_date.addEventListener('change', () => {
    useAllDates.checked = false;
    loadPreviewData();
});

end_date.addEventListener('change', () => {
    useAllDates.checked = false;
    loadPreviewData();
});

divisiFilter.addEventListener('change', function () {
    kategoriFilter.innerHTML = '<option value="">-- Semua Kategori --</option>';
    if (this.value && kategoriByDivisi[this.value]) {
        kategoriByDivisi[this.value].forEach(k => {
            kategoriFilter.innerHTML += `<option value="${k}">${k}</option>`;
        });
    }
    loadPreviewData();
});

kategoriFilter.addEventListener('change', loadPreviewData);

useAllCategories.addEventListener('change', function () {
    divisiFilter.disabled = this.checked;
    kategoriFilter.disabled = this.checked;
    loadPreviewData();
});

/* ===============================
   AJAX PREVIEW (FIX ALL BATCH)
=============================== */
async function loadPreviewData(page = 1) {

    if (!batch_year.value) return;

    loadingState.classList.remove('hidden');
    dataTableContainer.classList.add('hidden');
    emptyState.classList.add('hidden');

    const params = new URLSearchParams({
        page,
        useAllDates: useAllDates.checked ? 1 : 0,
        useAllCategories: useAllCategories.checked ? 1 : 0
    });

    // 🔥 BATCH: kirim hanya kalau bukan "all"
    if (batch_year.value !== 'all') {
        params.append('batch_year', batch_year.value);
    }

    if (!useAllDates.checked && start_date.value && end_date.value) {
        params.append('start_date', start_date.value);
        params.append('end_date', end_date.value);
    }

    if (!useAllCategories.checked) {
        if (divisiFilter.value) params.append('divisi', divisiFilter.value);
        if (kategoriFilter.value) params.append('kategori', kategoriFilter.value);
    }

    try {
        const res = await fetch(`{{ route('api.transactions.filtered') }}?${params}`);
        const result = await res.json();
        renderPreviewData(result);
    } catch (e) {
        console.error(e);
        loadingState.classList.add('hidden');
        emptyState.classList.remove('hidden');
    }
}

/* ===============================
   RENDER TABLE
=============================== */
function renderPreviewData(result) {

    loadingState.classList.add('hidden');

    if (result.success && result.data.length) {

        dataTableContainer.classList.remove('hidden');
        emptyState.classList.add('hidden');

        transactionTableBody.innerHTML = result.data.map((row, i) => `
            <tr>
                <td class="px-4 py-2 text-center">${i + result.from}</td>
                <td class="px-4 py-2 text-center">${row.items.join(', ')}</td>
                <td class="px-4 py-2 text-center">${row.divisi}</td>
                <td class="px-4 py-2 text-center">${row.kategori}</td>
                <td class="px-4 py-2 text-center font-bold">${row.jumlah_transaksi}</td>
            </tr>
        `).join('');

        currentItemCount.textContent = result.from;
        currentItemTo.textContent = result.to;
        totalItemCount.textContent = result.total;
        document.getElementById('totalTrxUnik').textContent = result.total_trx_unik ?? 0;

        // 🔥 TAMPILKAN PAGINATION
        paginationContainer.innerHTML = result.pagination || '';

        bindPaginationLinks();

    } else {
        dataTableContainer.classList.add('hidden');
        emptyState.classList.remove('hidden');
        paginationContainer.innerHTML = '';
        totalItemCount.textContent = 0;
    }

}
function bindPaginationLinks() {
    document.querySelectorAll('#paginationContainer a').forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();

            const url = new URL(this.href);
            const page = url.searchParams.get('page');

            loadPreviewData(page);

            // 🔥 FIX SCROLL
            scrollToTable();
        });
    });
}
</script>


@endsection
