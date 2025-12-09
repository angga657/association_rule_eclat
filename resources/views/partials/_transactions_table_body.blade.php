<!-- resources/views/partials/_transactions_table_body.blade.php -->
@foreach($transactions as $transaction)
<tr class="hover:bg-gray-50">
    <td class="px-4 py-2">{{ $transaction->id }}</td>
    <td class="px-4 py-2 font-medium">{{ $transaction->id_trx }}</td>
    <td class="px-4 py-2">{{ $transaction->tanggal->format('d/m/Y') }}</td>
    <td class="px-4 py-2">
        <div class="max-w-xs truncate" title="{{ $transaction->items }}">
            {{ $transaction->items }}
        </div>
    </td>
    <td class="px-4 py-2">{{ $transaction->kategori }}</td>
    <td class="px-4 py-2">{{ $transaction->divisi }}</td>
    
    {{-- KONDISIONAL UNTUK MENAMPILKAN KOLOM JUMLAH TRANSAKSI --}}
    @if(!($showTransformed ?? false))
        <td class="px-4 py-2 text-center">{{ $transaction->jumlah_transaksi }}</td>
    @endif
</tr>
@endforeach