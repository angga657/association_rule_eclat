@php $no = $transactions->firstItem(); @endphp

@forelse($transactions as $trx)
<tr class="border-b">
    <td class="px-4 py-2 text-center">{{ $no++ }}</td>
    <td class="px-4 py-2 text-center">{{ $trx->items }}</td>
    <td class="px-4 py-2 text-center">{{ $trx->divisi }}</td>
    <td class="px-4 py-2 text-center">{{ $trx->kategori }}</td>
    <td class="px-4 py-2 text-center">{{ $trx->jumlah_transaksi }}</td>
    <td class="text-center">
        {{ number_format($trx->jumlah_transaksi / $totalTrx * 100, 2) }}%
    </td>
</tr>
@empty
<tr>
    <td colspan="5" class="text-center py-4 text-gray-500">
        Tidak ada data
    </td>
</tr>
@endforelse
