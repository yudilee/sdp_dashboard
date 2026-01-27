@extends('layouts.app')

@section('title', 'Summary Generator - SDP Stock')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-slate-100 italic">LotSerial Summary Generator</h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm">Upload an Excel file to generate a structured inventory summary</p>
        </div>
        <a href="{{ route('dashboard') }}" class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-600 dark:text-slate-300 font-bold text-xs uppercase tracking-widest hover:bg-slate-50 dark:hover:bg-slate-700 transition-all shadow-sm">
            Back
        </a>
    </div>

    <!-- Upload Card -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800 p-6 theme-transition">
        <form action="{{ route('summary.generate') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label for="file" class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2 uppercase tracking-wider">Excel File (LotSerial Summary)</label>
                <div class="flex items-center justify-center w-full">
                    <label for="file" class="flex flex-col items-center justify-center w-full h-32 border-2 border-slate-300 dark:border-slate-700 border-dashed rounded-2xl cursor-pointer bg-slate-50 dark:bg-slate-800/50 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all">
                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                            <svg class="w-8 h-8 mb-3 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                            <p class="mb-2 text-sm text-slate-500 dark:text-slate-400 font-medium">Click to upload or drag and drop</p>
                            <p class="text-[10px] text-slate-400 uppercase font-bold tracking-widest">XLSX, XLS only</p>
                        </div>
                        <input id="file" name="file" type="file" class="hidden" required />
                    </label>
                </div>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold text-sm uppercase tracking-widest shadow-lg shadow-indigo-200 dark:shadow-none transition-all flex items-center gap-2 group">
                    <span>Generate Summary</span>
                    <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                </button>
            </div>
        </form>
    </div>

    <!-- Result -->
    @if(isset($summary))
    <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800 overflow-hidden theme-transition">
        <div class="p-6 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100">Summary Result</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50 dark:bg-slate-950 text-xs uppercase font-bold text-slate-500 dark:text-slate-400 tracking-wider">
                    <tr>
                        <th class="p-4">Category</th>
                        <th class="p-4">Description</th>
                        <th class="p-4 text-right">Value</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                    <!-- Vendor Rent -->
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                        <td class="p-4 font-bold text-slate-700 dark:text-slate-200">Vendor Rent</td>
                        <td class="p-4 text-slate-400 text-sm">Total currently rented from vendors</td>
                        <td class="p-4 text-right font-mono font-bold">{{ $summary['vendor_rent'] }}</td>
                    </tr>
                    
                    <!-- SDP Stock -->
                    <tr class="bg-indigo-50/50 dark:bg-indigo-900/10 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors">
                        <td class="p-4 font-bold text-indigo-700 dark:text-indigo-400">SDP Stock</td>
                        <td class="p-4 text-indigo-600 dark:text-indigo-300 text-sm">Total On Hand Quantity</td>
                        <td class="p-4 text-right font-mono font-bold text-indigo-700 dark:text-indigo-400 text-lg">{{ $summary['sdp_stock'] }}</td>
                    </tr>

                    <!-- In Stock Section -->
                    <tr class="bg-slate-50 dark:bg-slate-800/50">
                        <td class="p-4 pl-8 text-sm font-bold text-slate-600 dark:text-slate-300">In Stock</td>
                        <td class="p-4"></td>
                        <td class="p-4 text-right font-mono font-bold">{{ $summary['in_stock']['total'] }}</td>
                    </tr>
                    @if(isset($summary['in_stock']['details']['SDP/OPERATION']))
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50">
                         <td class="p-3 pl-12 text-xs text-slate-500 font-medium">SDP/OPERATION</td>
                         <td class="p-3 text-xs text-slate-400 italic">Operation</td>
                         <td class="p-3 text-right font-mono text-sm">{{ $summary['in_stock']['details']['SDP/OPERATION']['count'] }}</td>
                    </tr>
                    @endif
                    @if(isset($summary['in_stock']['details']['SDP/STOCK SOLD']))
                        @foreach($summary['in_stock']['details']['SDP/STOCK SOLD'] as $loc => $val)
                        @if($val > 0)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50">
                            <td class="p-3 pl-12 text-xs text-slate-500 font-medium">SDP/STOCK SOLD</td>
                            <td class="p-3 text-xs text-slate-400">{{ $loc }}</td>
                            <td class="p-3 text-right font-mono text-sm">{{ $val }}</td>
                        </tr>
                        @endif
                        @endforeach
                    @endif

                    <!-- Rented Section -->
                    <tr class="bg-slate-50 dark:bg-slate-800/50">
                        <td class="p-4 pl-8 text-sm font-bold text-slate-600 dark:text-slate-300">Rented in Customer</td>
                        <td class="p-4"></td>
                        <td class="p-4 text-right font-mono font-bold">{{ $summary['rented_in_customer']['total'] }}</td>
                    </tr>
                    @foreach($summary['rented_in_customer']['details'] as $desc => $val)
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50">
                        <td class="p-3 pl-12 text-xs text-slate-500 font-medium"></td>
                        <td class="p-3 text-xs text-slate-400">{{ $desc }}</td>
                        <td class="p-3 text-right font-mono text-sm">{{ $val }}</td>
                    </tr>
                    @endforeach

                    <!-- External Service Section -->
                    <tr class="bg-slate-50 dark:bg-slate-800/50">
                        <td class="p-4 pl-8 text-sm font-bold text-slate-600 dark:text-slate-300">Stock in External Service</td>
                        <td class="p-4"></td>
                        <td class="p-4 text-right font-mono font-bold">{{ $summary['stock_external_service']['total'] }}</td>
                    </tr>
                    @foreach($summary['stock_external_service']['details'] as $desc => $val)
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50">
                        <td class="p-3 pl-12 text-xs text-slate-500 font-medium"></td>
                        <td class="p-3 text-xs text-slate-400">{{ $desc }}</td>
                        <td class="p-3 text-right font-mono text-sm">{{ $val }}</td>
                    </tr>
                    @endforeach

                    <!-- Internal Service Section -->
                    <tr class="bg-slate-50 dark:bg-slate-800/50">
                        <td class="p-4 pl-8 text-sm font-bold text-slate-600 dark:text-slate-300">Stock in Internal Service</td>
                        <td class="p-4"></td>
                        <td class="p-4 text-right font-mono font-bold">{{ $summary['stock_internal_service']['total'] }}</td>
                    </tr>
                    @foreach($summary['stock_internal_service']['details'] as $desc => $val)
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50">
                        <td class="p-3 pl-12 text-xs text-slate-500 font-medium"></td>
                        <td class="p-3 text-xs text-slate-400">{{ $desc }}</td>
                        <td class="p-3 text-right font-mono text-sm">{{ $val }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection

