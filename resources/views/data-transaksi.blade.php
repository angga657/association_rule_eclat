@extends('layouts.app')

@section('title', 'Data Transaksi - Association Rule ECLAT')

@push('styles')
<style>
    /* Container untuk tabel dan paginasi */
    #data-table-container {
        transition: opacity 0.3s ease-in-out;
    }

    /* Efek saat sedang loading */
    #data-table-container.loading {
        opacity: 0.5;
        pointer-events: none; /* Mencegah klik saat loading */
    }

    /* Gaya untuk pesan loading di tengah tabel */
    .loading-message {
        text-align: center;
        padding: 20px;
        font-style: italic;
        color: #6b7280;
    }
</style>
@endpush

@section('content')
<div class="p-8">
    <h2 class="text-2xl font-bold mb-6">Data Transaksi</h2>
    
    <!-- Upload Section -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-xl font-semibold mb-4">Upload Data Transaksi</h3>
        <form action="{{ route('data-transaksi.upload') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-4">
                <label class="block mb-2 text-sm font-medium text-gray-700">Pilih File CSV</label>
                <input type="file" name="csv_file" class="w-full border rounded-lg px-3 py-2" accept=".csv" required>
                <p class="mt-2 text-sm text-gray-500">Format file harus CSV dengan header yang sesuai. Maksimal ukuran file: 10MB.</p>
            </div>
            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">
                <i class="fas fa-upload mr-2"></i>Upload Data
            </button>
        </form>
    </div>
    
    <!-- Data Table Section -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-semibold">Daftar Data Transaksi</h3>
            <div>
                <button id="deleteAllBtn" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition">
                    <i class="fas fa-trash-alt mr-2"></i>Delete all data
                </button>
            </div>
        </div>
        
        <div class="flex justify-between mb-4">
            <div class="flex items-center">
                <label class="mr-2">Show</label>
                <select id="per_page" class="border rounded px-3 py-1">
                    <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10</option>
                    <option value="25" {{ request('per_page', 10) == 25 ? 'selected' : '' }}>25</option>
                    <option value="50" {{ request('per_page', 10) == 50 ? 'selected' : '' }}>50</option>
                    <option value="100" {{ request('per_page', 10) == 100 ? 'selected' : '' }}>100</option>
                </select>
                <span class="ml-2">entries</span>
            </div>
            <div class="flex items-center">
                <label for="search" class="mr-2">Search:</label>
                <input type="text" id="search" class="border rounded px-3 py-1" placeholder="Search..." value="{{ request('search') }}">
            </div>
        </div>
        
        <div id="data-table-container">
            <div class="overflow-x-auto">
                <table class="min-w-full table-auto">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-2 text-left">ID</th>
                            <th class="px-4 py-2 text-left">ID Transaksi</th>
                            <th class="px-4 py-2 text-left">Tanggal</th>
                            <th class="px-4 py-2 text-left">Items</th>
                            <th class="px-4 py-2 text-left">Kategori</th>
                            <th class="px-4 py-2 text-left">Divisi</th>
                            <th class="px-4 py-2 text-left">Jumlah Transaksi</th>
                        </tr>
                    </thead>
                    <tbody id="table-body">
                        @include('partials._transactions_table_body')
                    </tbody>
                </table>
            </div>
            
            <div class="flex justify-between items-center mt-4">
                <div>
                    Showing {{ $transactions->firstItem() }} to {{ $transactions->lastItem() }} of {{ $transactions->total() }} entries
                </div>
                <div id="pagination-links">
                    {{ $transactions->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- JAVASCRIPT DIPERBARUI --}}
<script>
document.addEventListener('DOMContentLoaded', function () {

    const searchInput = document.getElementById('search');
    const perPageSelect = document.getElementById('per_page');
    const tableBody = document.getElementById('table-body');
    const paginationLinks = document.getElementById('pagination-links');
    const container = document.getElementById('data-table-container');
    const deleteBtn = document.getElementById('deleteAllBtn');

    let lastScrollY = 0;

    /* ------------------------------------------------------
       ANTI JUMPING: Lock height agar layout tidak ikut bergerak
    --------------------------------------------------------- */
    function lockHeight() {
        const height = container.offsetHeight;
        container.style.setProperty('--fixed-height', height + 'px');
        container.classList.add('fixed-height');
    }

    function unlockHeight() {
        container.classList.remove('fixed-height');
        container.style.removeProperty('--fixed-height');
    }

    /* Style tambahan (disuntik otomatis) */
    const style = document.createElement("style");
    style.innerHTML = `
        #data-table-container.fixed-height {
            height: var(--fixed-height) !important;
            overflow: hidden;
        }
    `;
    document.head.appendChild(style);


    /* ------------------------------------------------------
       FUNGSI UTAMA AJAX
    --------------------------------------------------------- */
    function fetchData(url, preserveScroll = true) {

        if (preserveScroll) {
            lastScrollY = window.scrollY;
        }

        lockHeight();  // <--- mencegah jumping
        container.classList.add('loading');

        tableBody.innerHTML = `
            <tr>
                <td colspan="7" class="loading-message">Memuat data...</td>
            </tr>
        `;

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {

            tableBody.innerHTML = data.table_body;
            paginationLinks.innerHTML = data.pagination;

            if (preserveScroll) {
                requestAnimationFrame(() => {
                    window.scrollTo({
                        top: lastScrollY,
                        behavior: 'instant'
                    });
                });
            }

            history.pushState({}, '', url);
        })
        .catch(error => {
            console.error('Error fetching data:', error);
            tableBody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-4 text-red-500">
                        Gagal memuat data.
                    </td>
                </tr>
            `;
        })
        .finally(() => {
            requestAnimationFrame(() => {
                container.classList.remove('loading');
                unlockHeight(); // <--- bebaskan height setelah render selesai
            });
        });
    }

    /* ------------------------------------------------------
       SEARCH + PER PAGE
    --------------------------------------------------------- */
    let searchTimeout;

    function triggerFilters() {
        clearTimeout(searchTimeout);

        const url = `{{ route('data-transaksi') }}?search=${encodeURIComponent(searchInput.value)}&per_page=${perPageSelect.value}`;

        searchTimeout = setTimeout(() => fetchData(url, false), 300);
    }

    searchInput.addEventListener('keyup', triggerFilters);
    perPageSelect.addEventListener('change', triggerFilters);


    /* ------------------------------------------------------
       PAGINATION (LIVE, TANPA HILANG EVENT)
    --------------------------------------------------------- */
    paginationLinks.addEventListener('click', function(event) {
        const target = event.target;
        if (target.tagName.toLowerCase() === 'a') {
            event.preventDefault();
            fetchData(target.href, true);
        }
    });


    /* ------------------------------------------------------
       DELETE ALL
    --------------------------------------------------------- */
    if (deleteBtn) {
        deleteBtn.addEventListener('click', function() {
            if (confirm('Apakah Anda yakin ingin menghapus semua data transaksi dan hasil ECLAT?')) {

                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route('data-transaksi.delete') }}';

                form.innerHTML = `
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="_method" value="DELETE">
                `;

                document.body.appendChild(form);
                form.submit();
            }
        });
    }

});
</script>

@endsection