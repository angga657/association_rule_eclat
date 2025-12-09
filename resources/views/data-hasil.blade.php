@extends('layouts.app')

@section('title', 'Data Hasil - Association Rule ECLAT')

@section('content')
<div class="p-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Data Hasil</h1>
        <p class="text-gray-600 mt-1">Hasil analisis asosiasi dengan algoritma ECLAT</p>
    </div>
    
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-gray-700">Min Support: <span class="font-semibold">{{ $minSupport }}%</span></p>
                </div>
                <div>
                    <p class="text-gray-700">Min Confidence: <span class="font-semibold">{{ $minConfidence }}%</span></p>
                </div>
            </div>
        </div>
        
        <div>
            <h3 class="text-lg font-semibold mb-4">Itemset</h3>
            <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="border border-gray-300 px-4 py-2 text-left">ATTRIBUT</th>
                            <th class="border border-gray-300 px-4 py-2 text-left">SUPPORT (%)</th>
                            <th class="border border-gray-300 px-4 py-2 text-left">CONFIDENCE (%)</th>
                            <th class="border border-gray-300 px-4 py-2 text-left">LIFT</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($results) && count($results) > 0)
                            @foreach($results as $result)
                            <tr class="bg-gray-50 hover:bg-gray-100 transition-colors">
                                <td class="border border-gray-300 px-4 py-2 font-medium">{{ $result['atribut'] }}</td>
                                <td class="border border-gray-300 px-4 py-2 text-center">{{ $result['support'] }}</td>
                                <td class="border border-gray-300 px-4 py-2 text-center">{{ $result['confidence'] }}</td>
                                <td class="border border-gray-300 px-4 py-2 text-center">{{ $result['lift'] }}</td>
                            </tr>
                            @endforeach
                        @else
                            <tr class="bg-gray-50">
                                <td colspan="4" class="border border-gray-300 px-4 py-2 text-center text-gray-500">
                                    Data Hasil - Belum ada hasil
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="mt-6 flex gap-2">
            <a href="{{ route('data.uji') }}" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700 transition-colors inline-block">
                <i class="fas fa-arrow-right mr-2"></i>Lanjut ke Data Uji
            </a>
            <button class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700 transition-colors">
                <i class="fas fa-download mr-2"></i>Download Hasil
            </button>
        </div>
    </div>
</div>
@endsection