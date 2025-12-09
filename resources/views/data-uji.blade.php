@extends('layouts.app')

@section('title', 'Data Hasil - Association Rule ECLAT')

@section('content')
<div class="p-8">
    <h2 class="text-2xl font-bold mb-6">Data Uji</h2>
    
    {{-- ============================= --}}
    {{-- PARAMETER PEMROSESAN --}}
    {{-- ============================= --}}
    @if($processing)
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-xl font-semibold mb-4">Parameter Pemrosesan</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <p class="text-gray-600 text-sm">Periode</p>
                    <p class="font-semibold">{{ $processing->start_date->format('d/m/Y') }} s/d {{ $processing->end_date->format('d/m/Y') }}</p>
                </div>
                <div>
                    <p class="text-gray-600 text-sm">Total Transaksi</p>
                    <p class="font-semibold">{{ $processing->total_transactions }} transaksi</p>
                </div>
                <div>
                    <p class="text-gray-600 text-sm">Min Support</p>
                    <p class="font-semibold">{{ $processing->min_support }}</p>
                </div>
                <div>
                    <p class="text-gray-600 text-sm">Min Confidence</p>
                    <p class="font-semibold">{{ $processing->min_confidence }}</p>
                </div>
            </div>
            
            @if($processing->kategori || $processing->divisi || $processing->jenis_trx || $processing->customer_type)
                <div class="mt-4 pt-4 border-t">
                    <p class="text-gray-600 text-sm mb-2">Filter Tambahan:</p>
                    <div class="flex flex-wrap gap-2">
                        @if($processing->kategori)
                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-sm">
                                <i class="fas fa-tag mr-1"></i>{{ $processing->kategori }}
                            </span>
                        @endif
                        @if($processing->divisi)
                            <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">
                                <i class="fas fa-layer-group mr-1"></i>{{ $processing->divisi }}
                            </span>
                        @endif
                        @if($processing->jenis_trx)
                            <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm">
                                <i class="fas fa-exchange-alt mr-1"></i>{{ $processing->jenis_trx }}
                            </span>
                        @endif
                        @if($processing->customer_type)
                            <span class="px-2 py-1 bg-purple-100 text-purple-800 rounded-full text-sm">
                                <i class="fas fa-user mr-1"></i>{{ $processing->customer_type }}
                            </span>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    @endif


    {{-- ================================================= --}}
    {{-- SORT GLOBAL UNTUK KEDUA TABEL --}}
    {{-- ================================================= --}}
    <div class="flex items-center justify-end mb-6">
        <label class="mr-2">Sort By:</label>
        <select class="border rounded px-3 py-1" onchange="sortAllTables(this.value)">
            <option value="lift_ratio">Lift Tertinggi</option>
            <option value="support">Support Tertinggi</option>
            <option value="confidence">Confidence Tertinggi</option>
        </select>
    </div>

    {{-- =============================================== --}}
    {{-- TABEL ASSOCIATION RULES (2-ITEMSETS) --}}
    {{-- =============================================== --}}
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-semibold text-blue-700">Association Rules (2-itemsets)</h3>
            <div class="text-sm text-gray-600">
                Total: <span class="font-semibold">{{ $pairItemsets->count() }}</span> aturan
            </div>
        </div>

        @if($pairItemsets->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full table-auto" id="resultsTable">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-3">No</th>
                            <th class="px-4 py-3">Aturan</th>
                            <th class="px-4 py-3">Support</th>
                            <th class="px-4 py-3">Confidence</th>
                            <th class="px-4 py-3">Lift</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($pairItemsets as $i => $rule)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">{{ $i + 1 }}</td>
                                <td class="px-4 py-3 font-semibold">{{ $rule->itemset }}</td>
                                <td class="px-4 py-3">{{ number_format($rule->support, 4) }}</td>
                                <td class="px-4 py-3">{{ number_format($rule->confidence, 4) }}</td>
                                <td class="px-4 py-3">{{ number_format($rule->lift_ratio, 4) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-8 text-gray-500">
                <p>Tidak ada aturan asosiasi (2-itemsets)</p>
            </div>
        @endif
    </div>
</div>

    {{-- =============================================== --}}
    {{-- TABEL SINGLE ITEMSET --}}
    {{-- =============================================== --}}
    <div class="bg-white rounded-lg shadow p-6 mb-10">
        <h3 class="text-xl font-semibold mb-4 text-green-700">Single Itemsets (1-item)</h3>

        @if($singleItemsets->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full table-auto" id="singleTable">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-3">No</th>
                            <th class="px-4 py-3">Item</th>
                            <th class="px-4 py-3">Support</th>
                            <th class="px-4 py-3">Confidence</th>
                            <th class="px-4 py-3">Lift</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($singleItemsets as $i => $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">{{ $i + 1 }}</td>
                                <td class="px-4 py-3 font-semibold">{{ $item->itemset }}</td>
                                <td class="px-4 py-3">{{ number_format($item->support, 4) }}</td>
                                <td class="px-4 py-3">{{ number_format($item->confidence, 4) }}</td>
                                <td class="px-4 py-3">{{ number_format($item->lift_ratio, 4) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-gray-500 text-center py-4">Tidak ada single itemset.</p>
        @endif
    </div>


    


{{-- ====================================================== --}}
{{-- SCRIPT SORT 1x UNTUK KEDUA TABEL (OPSI B) --}}
{{-- ====================================================== --}}
<script>
function sortAllTables(by) {
    sortTable("singleTable", by);
    sortTable("resultsTable", by);
}

function sortTable(tableId, by) {
    const table = document.getElementById(tableId);
    if (!table) return;

    const rows = Array.from(table.querySelectorAll("tbody tr"));
    let colIndex = 0;

    // Kolom:
    // 0 = No
    // 1 = Item/Aturan
    // 2 = Support
    // 3 = Confidence
    // 4 = Lift

    if (by === "support") colIndex = 2;
    if (by === "confidence") colIndex = 3;
    if (by === "lift_ratio") colIndex = 4;

    rows.sort((a, b) => {
        const A = parseFloat(a.children[colIndex].innerText);
        const B = parseFloat(b.children[colIndex].innerText);
        return B - A;
    });

    const tbody = table.querySelector("tbody");
    tbody.innerHTML = "";
    rows.forEach(row => tbody.appendChild(row));
}
</script>

@endsection
