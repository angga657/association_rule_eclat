@extends('layouts.app')

@section('title', 'Data Hasil - Association Rule ECLAT')

@section('content')
<div class="p-8">

    <h2 class="text-2xl font-bold mb-6">Data Hasil</h2>

    {{-- ================= PARAMETER ================= --}}
    @if($processing)
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-xl font-semibold mb-4">Parameter Pemrosesan</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <p class="text-sm text-gray-500">Periode</p>
                <p class="font-semibold">
                    @if($processing->start_date && $processing->end_date)
                        {{ $processing->start_date->format('d/m/Y') }}
                        s/d
                        {{ $processing->end_date->format('d/m/Y') }}
                    @else
                        Semua Tanggal
                    @endif
                </p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Total Transaksi</p>
                <p class="font-semibold">{{ $processing->total_transactions }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Min Support</p>
                <p class="font-semibold">{{ rtrim(rtrim(number_format($processing->min_support * 100, 2), '0'), '.') }}%</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Min Confidence</p>
                <p class="font-semibold">{{ rtrim(rtrim(number_format($processing->min_confidence * 100, 2), '0'), '.') }}%</p>
            </div>
        </div>
    </div>
    @endif

    {{-- ================= SORT GLOBAL ================= --}}
    <div class="flex justify-end mb-6">
        <select class="border rounded px-3 py-2"
                onchange="sortAllTables(this.value)">
            <option value="lift_ratio">Lift Tertinggi</option>
            <option value="support">Support Tertinggi</option>
            <option value="confidence">Confidence Tertinggi</option>
        </select>
    </div>

    {{-- ================= PAIR ITEMSET ================= --}}
    <div class="bg-white rounded-lg shadow p-6 mb-10">
        <h3 class="text-xl font-semibold text-blue-700 mb-4">
            Association Rules (2-itemsets)
        </h3>

        @if($pairItemsets->count())
        <div class="overflow-x-auto">
            <table class="min-w-full table-auto" id="pairTable">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-center">No</th>
                        <th class="px-4 py-2 text-center">Aturan</th>
                        <th class="px-4 py-2 text-center">Support</th>
                        <th class="px-4 py-2 text-center">Confidence</th>
                        <th class="px-4 py-2 text-center">Lift</th>
                    </tr>
                </thead>
                <tbody>
                    
                    @foreach($pairItemsets as $i => $item)
                    <tr>
                        <td class="px-4 py-2 text-center">{{ $i + 1 }}</td>
                        <td class="text-center">{{ $item->rule_from }} → {{ $item->rule_to }}</td>
                        <td class="text-center">{{ number_format($item->support_percent, 2) }}%</td>
                        <td class="text-center">{{ number_format($item->confidence_percent, 2) }}%</td>
                        <td class="text-center ">{{ number_format($item->lift_ratio, 2) }}</td>
                    </tr>
                    @endforeach
                    
                </tbody>
            </table>
        </div>
        @else
            <p class="text-gray-500 text-center">Tidak ada aturan asosiasi</p>
        @endif
    </div>

    {{-- ================= SINGLE ITEMSET ================= --}}
    <div class="bg-white rounded-lg shadow p-6 mb-10">
        <h3 class="text-xl font-semibold text-green-700 mb-4">
            Single Itemsets (1-itemset)
        </h3>

        @if($singleItemsets->count())
        <div class="overflow-x-auto">
            <table class="min-w-full table-auto">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-center">No</th>
                        <th class="px-4 py-2 text-left">Item</th>
                        <th class="px-4 py-2 text-center">Support</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($singleItemsets as $i => $item)
                    <tr>
                        <td class="px-4 py-2 text-center">{{ $i + 1 }}</td>
                        <td class="px-4 py-2 font-semibold">
                            {{ $item->itemset }}
                        </td>
                        <td class="px-4 py-2 text-center">
                            {{ number_format($item->support_percent, 2) }}%
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
            <p class="text-gray-500 text-center">
                Tidak ada single itemset
            </p>
        @endif
    </div>

</div>

{{-- ================= SORT SCRIPT ================= --}}
<script>
function sortAllTables(by) {
    sortTable('pairTable', by);
}

function sortTable(tableId, by) {
    const table = document.getElementById(tableId);
    if (!table) return;

    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));

    let col = by === 'support' ? 2 :
            by === 'confidence' ? 3 : 4;

    rows.sort((a, b) => {
        const valA = parseFloat(a.children[col].innerText.replace('%', '')) || 0;
        const valB = parseFloat(b.children[col].innerText.replace('%', '')) || 0;
        return valB - valA;
    });

    tbody.innerHTML = '';
    rows.forEach((row,i) => {
        row.children[0].innerText = i + 1; // 🔥 FIX NOMOR
        tbody.appendChild(row);
    });
}
</script>
@endsection
