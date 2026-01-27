@extends('layouts.app')

@section('title', 'Dashboard - SDP Stock')

@section('content')
    <div x-data="{ showUploadModal: false, isUploading: false }">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-indigo-600 to-purple-600 dark:from-indigo-400 dark:to-purple-400">
                Dashboard Overview
            </h1>
            <p class="text-slate-500 dark:text-slate-400 font-medium mt-1">Real-time inventory insights</p>
        </div>
        
        <div class="flex gap-3">
            @if(isset($summary))
            <a href="{{ route('print') }}" target="_blank" class="flex items-center gap-2 px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-600 dark:text-slate-300 font-medium hover:bg-slate-50 dark:hover:bg-slate-700 hover:text-indigo-600 dark:hover:text-indigo-400 transition-all shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Print Report
            </a>
            @endif
        </div>
    </div>

    @if(isset($summary))
    <!-- Search Bar -->
    <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800 mb-8">
        <form action="{{ route('details') }}" method="GET" class="flex flex-col md:flex-row gap-4 items-center">
            <input type="hidden" name="category" value="search">
            <div class="relative w-full md:w-96">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <input type="text" name="q" class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" placeholder="Search lot number, product...">
            </div>
            <button type="submit" class="w-full md:w-auto px-6 py-2.5 bg-slate-800 dark:bg-indigo-600 text-white rounded-xl font-medium hover:bg-slate-700 dark:hover:bg-indigo-700 transition-colors shadow-lg shadow-slate-200 dark:shadow-indigo-900/20">Search</button>
            <div class="w-full md:w-auto flex gap-2 md:ml-auto overflow-x-auto pb-2 md:pb-0">
                <div class="flex gap-2">
                    <a href="{{ route('details', ['category' => 'vendor_rent']) }}" class="whitespace-nowrap px-4 py-2 rounded-lg bg-cyan-50 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-300 font-medium text-sm hover:bg-cyan-100 dark:hover:bg-cyan-900/50 transition-colors border border-cyan-100 dark:border-cyan-800">Vendor Rent</a>
                    <a href="{{ route('details', ['category' => 'in_stock']) }}" class="whitespace-nowrap px-4 py-2 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 font-medium text-sm hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition-colors border border-emerald-100 dark:border-emerald-800">In Stock</a>
                    <a href="{{ route('details', ['category' => 'rented']) }}" class="whitespace-nowrap px-4 py-2 rounded-lg bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 font-medium text-sm hover:bg-amber-100 dark:hover:bg-amber-900/50 transition-colors border border-amber-100 dark:border-amber-800">Rented</a>
                </div>
            </div>
        </form>
    </div>

    <!-- Hero Card -->
    <a href="{{ route('total.stock') }}" class="block relative overflow-hidden bg-gradient-to-br from-indigo-600 to-violet-700 rounded-3xl p-8 mb-8 text-white shadow-xl shadow-indigo-200 hover:scale-[1.01] transition-transform cursor-pointer">
        <div class="absolute top-0 right-0 -mr-16 -mt-16 w-64 h-64 rounded-full bg-white opacity-10 blur-3xl"></div>
        <div class="absolute bottom-0 left-0 -ml-16 -mb-16 w-64 h-64 rounded-full bg-white opacity-10 blur-3xl"></div>
        
        <div class="relative flex justify-between items-center">
            <div>
                <p class="text-indigo-100 font-medium mb-1 uppercase tracking-wider text-sm">Total Active Stock</p>
                <h2 class="text-5xl font-bold tracking-tight mb-4">{{ number_format($summary['sdp_stock']) }}</h2>
                @if(isset($metadata['imported_at']))
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/20 backdrop-blur-sm text-sm border border-white/10">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>Updated {{ \Carbon\Carbon::parse($metadata['imported_at'])->diffForHumans() }}</span>
                </div>
                @endif
            </div>
            <div class="hidden md:block">
                <svg class="w-32 h-32 text-white opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
            </div>
        </div>
    </a>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8 animate-enter delay-100">
        <!-- In Stock -->
        <a href="{{ route('details', ['category' => 'in_stock']) }}" class="group bg-white dark:bg-slate-900 p-6 rounded-2xl shadow-sm dark:shadow-none border border-slate-100 dark:border-slate-800 hover:shadow-lg dark:hover:bg-slate-800 hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
            <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-emerald-500 rounded-l-2xl"></div>
            <div class="flex items-center">
                <p class="text-sm font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wide mb-1">In Stock</p>
                @include('partials.info_tooltip', ['text' => 'Items physically present in warehouse'])
            </div>
            <h3 class="text-3xl font-bold text-slate-800 dark:text-slate-100">{{ number_format($summary['in_stock']['total']) }}</h3>
            <div class="mt-4 flex items-center text-xs font-semibold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/30 w-fit px-2 py-1 rounded">
                Available
            </div>
        </a>

        <!-- Rented -->
        <a href="{{ route('details', ['category' => 'rented']) }}" class="group bg-white dark:bg-slate-900 p-6 rounded-2xl shadow-sm dark:shadow-none border border-slate-100 dark:border-slate-800 hover:shadow-lg dark:hover:bg-slate-800 hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
            <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-amber-500 rounded-l-2xl"></div>
            <div class="flex items-center">
                <p class="text-sm font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wide mb-1">Rented In Customer</p>
                <div class="mb-1">@include('partials.info_tooltip', ['text' => 'Units currently with customers'])</div>
            </div>
            <h3 class="text-3xl font-bold text-slate-800 dark:text-slate-100">{{ number_format($summary['rented_in_customer']['total']) }}</h3>
            <div class="mt-4 flex items-center text-xs font-semibold text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/30 w-fit px-2 py-1 rounded">
                Active Rentals
            </div>
        </a>

        <!-- In Service -->
        <a href="{{ route('details', ['category' => 'in_service']) }}" class="group bg-white dark:bg-slate-900 p-6 rounded-2xl shadow-sm dark:shadow-none border border-slate-100 dark:border-slate-800 hover:shadow-lg dark:hover:bg-slate-800 hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
            <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-red-500 rounded-l-2xl"></div>
            <div class="flex items-center">
                <p class="text-sm font-bold text-red-600 dark:text-red-400 uppercase tracking-wide mb-1">In Service</p>
                @include('partials.info_tooltip', ['text' => 'Units in repair/maintenance'])
            </div>
            <h3 class="text-3xl font-bold text-slate-800 dark:text-slate-100">{{ number_format($summary['stock_external_service']['total'] + $summary['stock_internal_service']['total'] + ($summary['stock_insurance']['total'] ?? 0)) }}</h3>
            <div class="mt-4 text-xs text-slate-500 dark:text-slate-400 flex gap-2">
                <span class="bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 px-1.5 py-0.5 rounded font-medium">Ext: {{ $summary['stock_external_service']['total'] }}</span>
                <span class="bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 px-1.5 py-0.5 rounded font-medium">Int: {{ $summary['stock_internal_service']['total'] }}</span>
            </div>
        </a>

        <!-- Rental Pairs -->
        @if(isset($summary['rental_pairs_count']) && $summary['rental_pairs_count'] > 0)
        <a href="{{ route('rental.pairs') }}" class="group bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-900/20 dark:to-amber-800/20 p-6 rounded-2xl shadow-sm dark:shadow-none border border-amber-200 dark:border-amber-800/50 hover:shadow-lg hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
            <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-amber-500 rounded-l-2xl"></div>
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm font-bold text-amber-700 dark:text-amber-400 uppercase tracking-wide mb-1">Rental Pairs</p>
                    <h3 class="text-3xl font-bold text-slate-800 dark:text-slate-100">{{ $summary['rental_pairs_count'] }}</h3>
                </div>
                <div class="p-2 bg-white/50 dark:bg-slate-800/50 rounded-lg text-amber-500 dark:text-amber-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                </div>
            </div>
            <p class="mt-4 text-xs font-semibold text-amber-700 dark:text-amber-400">Active paired vehicles</p>
        </a>
        @else
        <div class="bg-slate-50 dark:bg-slate-900 p-6 rounded-2xl shadow-inner border border-slate-200 dark:border-slate-800 opacity-75">
            <p class="text-sm font-bold text-slate-400 uppercase tracking-wide mb-1">Rental Pairs</p>
            <h3 class="text-3xl font-bold text-slate-400">-</h3>
            <p class="mt-4 text-xs text-slate-400">No active pairs detected</p>
        </div>
        @endif
    </div>

    <!-- Historical Trend Chart -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm dark:shadow-none border border-slate-100 dark:border-slate-800 p-6 mb-8">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="font-bold text-slate-800 dark:text-slate-100 text-lg">Historical Trends</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400">Inventory movement over the last 30 days</p>
            </div>
        </div>
        <div id="trendChart" class="h-80 w-full"></div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8 animate-enter delay-200">
        
        <!-- Left Column: Breakdowns -->
        <div class="lg:col-span-2 space-y-8">
            
            <!-- In Stock Breakdown -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm dark:shadow-none border border-slate-100 dark:border-slate-800 p-6">
                <div class="flex items-center gap-2 mb-6 pb-4 border-b border-slate-100 dark:border-slate-800">
                    <div class="p-2 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100">In Stock Breakdown</h3>
                </div>
                
                <!-- Mini Stats Grid -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                    <a href="{{ route('details', ['category' => 'stock_pure']) }}" class="flex flex-col items-center p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl border border-emerald-100 dark:border-emerald-800 hover:border-emerald-300 dark:hover:border-emerald-700 transition-colors text-center group/mini relative">
                        <span class="text-2xl font-bold text-emerald-700 dark:text-emerald-400">{{ $summary['in_stock']['rental_status']['pure_stock'] ?? 0 }}</span>
                        <div class="flex items-center justify-center">
                            <span class="text-xs font-bold uppercase text-emerald-600 dark:text-emerald-500 mt-1">Pure Stock</span>
                            @include('partials.info_tooltip', ['text' => 'Available items with NO Rental ID'])
                        </div>
                    </a>
                    <a href="{{ route('details', ['category' => 'stock_reserve']) }}" class="flex flex-col items-center p-3 bg-pink-50 dark:bg-pink-900/20 rounded-xl border border-pink-100 dark:border-pink-800 hover:border-pink-300 dark:hover:border-pink-700 transition-colors text-center group/mini">
                        <span class="text-2xl font-bold text-pink-700 dark:text-pink-400">{{ $summary['in_stock']['rental_status']['reserve'] ?? 0 }}</span>
                        <div class="flex items-center justify-center">
                            <span class="text-xs font-bold uppercase text-pink-600 dark:text-pink-500 mt-1">Reserve</span>
                            @include('partials.info_tooltip', ['text' => 'Assigned to FUTURE rentals'])
                        </div>
                    </a>
                    <a href="{{ route('details', ['category' => 'stock_original', 'sub' => 'with_replace']) }}" class="flex flex-col items-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-100 dark:border-blue-800 hover:border-blue-300 dark:hover:border-blue-700 transition-colors text-center">
                        <span class="text-2xl font-bold text-blue-700 dark:text-blue-400">{{ $summary['in_stock']['rental_status']['original_with_replace'] ?? 0 }}</span>
                        <span class="text-xs font-bold uppercase text-blue-600 dark:text-blue-500 mt-1">Orig (Repl)</span>
                    </a>
                    <a href="{{ route('details', ['category' => 'stock_original', 'sub' => 'no_replace']) }}" class="flex flex-col items-center p-3 bg-red-50 dark:bg-red-900/20 rounded-xl border border-red-100 dark:border-red-800 hover:border-red-300 dark:hover:border-red-700 transition-colors text-center">
                        <span class="text-2xl font-bold text-red-700 dark:text-red-400">{{ $summary['in_stock']['rental_status']['original_without_replace'] ?? 0 }}</span>
                        <span class="text-xs font-bold uppercase text-red-600 dark:text-red-500 mt-1">Orig (No Repl)</span>
                    </a>
                </div>

                <h4 class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-4">By Location</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @if(isset($summary['in_stock']['details']['SDP/OPERATION']))
                    <a href="{{ route('details', ['category' => 'in_stock', 'sub' => 'Operation']) }}" class="flex justify-between items-center p-3 rounded-xl border border-slate-100 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors group">
                        <span class="text-slate-600 dark:text-slate-300 font-medium group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">Operation</span>
                        <span class="bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 text-xs font-bold px-2 py-1 rounded-md">{{ $summary['in_stock']['details']['SDP/OPERATION']['count'] }}</span>
                    </a>
                    @endif
                    
                    @if(isset($summary['in_stock']['details']['locations']['SDP/STOCK SOLD']) && $summary['in_stock']['details']['locations']['SDP/STOCK SOLD'] > 0)
                    <a href="{{ route('details', ['category' => 'in_stock', 'sub' => 'SDP/STOCK SOLD']) }}" class="flex justify-between items-center p-3 rounded-xl border border-indigo-100 dark:border-indigo-900/30 bg-indigo-50/50 dark:bg-indigo-900/20 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition-colors group">
                        <span class="text-indigo-700 dark:text-indigo-300 font-medium">Stock for Sold</span>
                        <span class="bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-200 text-xs font-bold px-2 py-1 rounded-md">{{ $summary['in_stock']['details']['locations']['SDP/STOCK SOLD'] }}</span>
                    </a>
                    @endif

                    @if(isset($summary['in_stock']['details']['locations']))
                        @foreach($summary['in_stock']['details']['locations'] as $loc => $val)
                        @if($val > 0 && $loc !== 'SDP/STOCK SOLD')
                        <a href="{{ route('details', ['category' => 'in_stock', 'sub' => $loc]) }}" class="flex justify-between items-center p-3 rounded-xl border border-slate-100 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors group">
                            <span class="text-slate-600 dark:text-slate-300 font-medium group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">{{ $loc }}</span>
                            <span class="bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-xs font-bold px-2 py-1 rounded-md">{{ $val }}</span>
                        </a>
                        @endif
                        @endforeach
                    @endif
                </div>
            </div>

            <!-- Rented Breakdown -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800 p-6">
                <div class="flex items-center gap-2 mb-6 pb-4 border-b border-slate-100 dark:border-slate-800">
                    <div class="p-2 bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100">Rented In Customer Breakdown</h3>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
                     <!-- Left Side: Main Categories -->
                     <div class="space-y-3">
                         @if(isset($summary['rented_in_customer']['details']['Original in Customer']) && $summary['rented_in_customer']['details']['Original in Customer'] > 0)
                         <a href="{{ route('details', ['category' => 'rented', 'sub' => 'Original in Customer']) }}" class="flex justify-between items-center p-3 rounded-xl border border-slate-100 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors group">
                             <div class="flex items-center gap-3">
                                 <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                 <span class="text-slate-600 dark:text-slate-300 font-medium group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">Original in Customer</span>
                             </div>
                             <span class="bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 text-xs font-bold px-2 py-1 rounded-md">{{ $summary['rented_in_customer']['details']['Original in Customer'] }}</span>
                         </a>
                         @endif
                         
                         @if(isset($summary['rented_in_customer']['details']['Vendor Rent']) && $summary['rented_in_customer']['details']['Vendor Rent'] > 0)
                         <a href="{{ route('details', ['category' => 'rented', 'sub' => 'Vendor Rent']) }}" class="flex justify-between items-center p-3 rounded-xl border border-slate-100 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors group">
                             <div class="flex items-center gap-3">
                                 <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                 <span class="text-slate-600 dark:text-slate-300 font-medium group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">Vendor Rent</span>
                             </div>
                             <span class="bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 text-xs font-bold px-2 py-1 rounded-md">{{ $summary['rented_in_customer']['details']['Vendor Rent'] }}</span>
                         </a>
                         @endif
                     </div>

                     <!-- Right Side: Replacements & Issues -->
                     <div class="space-y-3">
                         @if(isset($summary['rented_in_customer']['details']['Replacement - Service']) && $summary['rented_in_customer']['details']['Replacement - Service'] > 0)
                         <a href="{{ route('details', ['category' => 'rented', 'sub' => 'Replacement - Service']) }}" class="flex justify-between items-center p-3 rounded-xl border border-slate-100 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors group">
                             <div class="flex items-center gap-3">
                                 <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                 <span class="text-slate-600 dark:text-slate-300 font-medium group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">Replacement (Service)</span>
                             </div>
                             <span class="bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 text-xs font-bold px-2 py-1 rounded-md">{{ $summary['rented_in_customer']['details']['Replacement - Service'] }}</span>
                         </a>
                         @endif

                         @if(isset($summary['rented_in_customer']['details']['Replacement - RBO']) && $summary['rented_in_customer']['details']['Replacement - RBO'] > 0)
                         <a href="{{ route('details', ['category' => 'rented', 'sub' => 'Replacement - RBO']) }}" class="flex justify-between items-center p-3 rounded-xl border border-slate-100 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors group">
                             <div class="flex items-center gap-3">
                                 <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                                 <span class="text-slate-600 dark:text-slate-300 font-medium group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">Replacement (RBO)</span>
                             </div>
                             <span class="bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400 text-xs font-bold px-2 py-1 rounded-md">{{ $summary['rented_in_customer']['details']['Replacement - RBO'] }}</span>
                         </a>
                         @endif
                         
                         @if(isset($summary['rented_in_customer']['details']['Check Rent position']) && $summary['rented_in_customer']['details']['Check Rent position'] > 0)
                         <a href="{{ route('details', ['category' => 'rented', 'sub' => 'Check Rent position']) }}" class="flex justify-between items-center p-3 rounded-xl border border-red-100 dark:border-red-900/30 bg-red-50/50 dark:bg-red-900/20 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors group">
                             <div class="flex items-center gap-3">
                                 <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                                 <span class="text-red-700 dark:text-red-400 font-medium group-hover:text-red-800 dark:group-hover:text-red-300 transition-colors">Check Rent Position</span>
                             </div>
                             <span class="bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300 text-xs font-bold px-2 py-1 rounded-md">{{ $summary['rented_in_customer']['details']['Check Rent position'] }}</span>
                         </a>
                         @endif
                     </div>
                </div>
            </div>

            <!-- In Service Breakdown -->
             <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800 p-6">
                <div class="flex items-center gap-2 mb-6 pb-4 border-b border-slate-100 dark:border-slate-800">
                    <div class="p-2 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100">In Service Breakdown</h3>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-x-8 gap-y-6">
                    <div>
                         <h4 class="text-xs font-bold text-red-500 uppercase tracking-wider mb-3">External ({{ $summary['stock_external_service']['total'] }})</h4>
                         <div class="space-y-2">
                            @foreach($summary['stock_external_service']['details'] as $desc => $val)
                            <a href="{{ route('details', ['category' => 'external_service', 'sub' => $desc]) }}" class="flex justify-between items-center p-2 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors text-sm">
                                <span class="text-slate-600 dark:text-slate-300">{{ str_replace(['Orig ', 'Original Rented '], ['Original ', 'Original '], $desc) }}</span>
                                <span class="bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 text-xs font-bold px-2 py-0.5 rounded">{{ $val }}</span>
                            </a>
                            @endforeach
                         </div>
                    </div>
                    <div>
                         <h4 class="text-xs font-bold text-blue-500 uppercase tracking-wider mb-3">Internal ({{ $summary['stock_internal_service']['total'] }})</h4>
                         <div class="space-y-2">
                            @foreach($summary['stock_internal_service']['details'] as $desc => $val)
                            <a href="{{ route('details', ['category' => 'internal_service', 'sub' => $desc]) }}" class="flex justify-between items-center p-2 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors text-sm">
                                <span class="text-slate-600 dark:text-slate-300">{{ str_replace(['Orig ', 'Original Rented '], ['Original ', 'Original '], $desc) }}</span>
                                <span class="bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-xs font-bold px-2 py-0.5 rounded">{{ $val }}</span>
                            </a>
                            @endforeach
                         </div>
                    </div>
                    <div>
                         <h4 class="text-xs font-bold text-purple-500 uppercase tracking-wider mb-3">Insurance ({{ $summary['stock_insurance']['total'] ?? 0 }})</h4>
                         <div class="space-y-2">
                            @foreach(($summary['stock_insurance']['details'] ?? []) as $desc => $val)
                            <a href="{{ route('details', ['category' => 'insurance', 'sub' => $desc]) }}" class="flex justify-between items-center p-2 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors text-sm">
                                <span class="text-slate-600 dark:text-slate-300">{{ str_replace(['Orig ', 'Original Rented '], ['Original ', 'Original '], $desc) }}</span>
                                <span class="bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 text-xs font-bold px-2 py-0.5 rounded">{{ $val }}</span>
                            </a>
                            @endforeach
                         </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Right Column: Ownership & Charts -->
        <div class="space-y-8">
            
            <!-- Ownership -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800 p-6">
                <div class="flex items-center gap-2 mb-4">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    <h3 class="font-bold text-slate-800 dark:text-slate-100">Ownership</h3>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <a href="{{ route('details', ['category' => 'sdp_owned']) }}" class="p-4 rounded-xl bg-indigo-50 border border-indigo-100 flex flex-col items-center justify-center text-center hover:bg-indigo-100 transition-colors">
                        <span class="text-3xl font-bold text-indigo-600">{{ number_format($summary['sdp_stock'] - $summary['vendor_rent']) }}</span>
                        <span class="text-xs font-semibold text-indigo-400 mt-1 uppercase">SDP Owned</span>
                    </a>
                    <a href="{{ route('details', ['category' => 'vendor_rent']) }}" class="p-4 rounded-xl bg-cyan-50 border border-cyan-100 flex flex-col items-center justify-center text-center hover:bg-cyan-100 transition-colors">
                        <span class="text-3xl font-bold text-cyan-600">{{ number_format($summary['vendor_rent']) }}</span>
                        <span class="text-xs font-semibold text-cyan-500 mt-1 uppercase">Vendor Rent</span>
                    </a>
                </div>

                @if(($summary['uncategorized']['total'] ?? 0) > 0)
                <a href="{{ route('details', ['category' => 'uncategorized']) }}" class="mt-4 flex items-center justify-center gap-2 w-full py-2 bg-red-50 text-red-600 rounded-lg text-sm font-semibold hover:bg-red-100 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    {{ $summary['uncategorized']['total'] }} Uncategorized Items
                </a>
                @endif
            </div>

            <!-- Charts Placeholder (using ApexCharts) -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800 p-6 h-auto">
                <h3 class="font-bold text-slate-800 dark:text-slate-100 mb-4">Stock Distribution</h3>
                <div id="drilldownChart" class="h-64"></div>
            </div>
        </div>
    </div>



    @else
        <!-- Empty State -->
        <div class="flex flex-col items-center justify-center min-h-[60vh] text-center">
            <div class="w-24 h-24 bg-indigo-50 rounded-full flex items-center justify-center text-indigo-500 mb-6">
                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
            </div>
            <h2 class="text-3xl font-bold text-slate-800 mb-2">No Data Available</h2>
            <p class="text-slate-500 max-w-md mb-8">Upload your Excel inventory file to generate the dashboard. Supported formats: .xlsx, .xls, .csv</p>
            <button class="px-8 py-3 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 transition-all shadow-lg hover:shadow-indigo-200 animate-pulse" 
                    @click="showUploadModal = true">
                Import Data Now
            </button>
        </div>
        


    
    @endif

    <!-- Upload Modal (Alpine.js) -->
    <div x-show="showUploadModal" 
         x-cloak 
         class="fixed inset-0 z-50 overflow-y-auto" 
         aria-labelledby="modal-title" role="dialog" aria-modal="true">
         
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Backdrop -->
            <div x-show="showUploadModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-slate-900/75 transition-opacity" 
                 @click="showUploadModal = false"
                 aria-hidden="true"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <!-- Modal Panel -->
            <div x-show="showUploadModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative z-50 inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                
                <form action="{{ route('summary.generate') }}" method="POST" enctype="multipart/form-data" @submit="isUploading = true">
                    @csrf
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-slate-900" id="modal-title">Import Data</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-slate-500 mb-4">Upload your Excel file to update the dashboard.</p>
                                    <input type="file" name="file" required class="block w-full text-sm text-slate-500
                                      file:mr-4 file:py-2 file:px-4
                                      file:rounded-full file:border-0
                                      file:text-sm file:font-semibold
                                      file:bg-indigo-50 file:text-indigo-700
                                      hover:file:bg-indigo-100
                                    "/>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-slate-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" 
                                class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                                :disabled="isUploading">
                            <svg x-show="isUploading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="isUploading ? 'Processing...' : 'Upload'"></span>
                        </button>
                        <button type="button" 
                                class="mt-3 w-full inline-flex justify-center rounded-xl border border-slate-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-slate-700 hover:bg-slate-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50" 
                                @click="showUploadModal = false"
                                :disabled="isUploading">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </div>
@endsection



@section('scripts')
@if(isset($summary))
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    // Data from Controller
    const historyData = @json($history ?? []);
    
    // Prepare Series
    const dates = historyData.map(h => h.date);
    const inStockSeries = historyData.map(h => h.in_stock);
    const rentedSeries = historyData.map(h => h.rented);
    const inServiceSeries = historyData.map(h => h.in_service);
    
    // 1. Trend Chart
    if (historyData.length > 0) {
        const trendOptions = {
            chart: {
                type: 'area',
                height: 320,
                fontFamily: 'Outfit, sans-serif',
                toolbar: { show: false },
                animations: { enabled: true }
            },
            stroke: { curve: 'smooth', width: 2 },
            fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05, stops: [0, 90, 100] } },
            dataLabels: { enabled: false },
            series: [
                { name: 'In Stock', data: inStockSeries },
                { name: 'Rented', data: rentedSeries },
                { name: 'In Service', data: inServiceSeries }
            ],
            xaxis: {
                categories: dates,
                type: 'datetime',
                tooltip: { enabled: false },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: { show: true },
            colors: ['#10b981', '#f59e0b', '#ef4444'], // Green, Amber, Red
            grid: { borderColor: '#f1f5f9', strokeDashArray: 4 },
            legend: { position: 'top', horizontalAlign: 'right' }
        };
        
        const trendChart = new ApexCharts(document.querySelector("#trendChart"), trendOptions);
        trendChart.render();
    }

    // 2. Drilldown Chart
    const mainData = {
        series: [{{ $summary['in_stock']['total'] }}, {{ $summary['rented_in_customer']['total'] }}, {{ $summary['stock_external_service']['total'] + $summary['stock_internal_service']['total'] + ($summary['stock_insurance']['total'] ?? 0) }}],
        labels: ['In Stock', 'Rented', 'In Service'],
        colors: ['#10b981', '#f59e0b', '#ef4444']
    };
    // ... (rest of drilldown chart logic) ...

    const drillData = {
        'In Stock': {
            series: [
                @if(isset($summary['in_stock']['details']['SDP/OPERATION'])){{ $summary['in_stock']['details']['SDP/OPERATION']['count'] }},@endif
                @if(isset($summary['in_stock']['details']['locations']))
                    @foreach($summary['in_stock']['details']['locations'] as $loc => $val){{ $val }},@endforeach
                @endif
            ],
            labels: [
                @if(isset($summary['in_stock']['details']['SDP/OPERATION']))'Operation',@endif
                @if(isset($summary['in_stock']['details']['locations']))
                    @foreach($summary['in_stock']['details']['locations'] as $loc => $val)'{{ $loc }}',@endforeach
                @endif
            ]
        },
        'Rented': {
            series: [@foreach($summary['rented_in_customer']['details'] as $val){{ $val }},@endforeach],
            labels: [@foreach($summary['rented_in_customer']['details'] as $desc => $val)'{{ $desc }}',@endforeach]
        },
        'In Service': {
            series: [
                @foreach($summary['stock_external_service']['details'] as $val){{ $val }},@endforeach
                @foreach($summary['stock_internal_service']['details'] as $val){{ $val }},@endforeach
                @foreach(($summary['stock_insurance']['details'] ?? []) as $val){{ $val }},@endforeach
            ],
            labels: [
                @foreach($summary['stock_external_service']['details'] as $desc => $val)'Ext: {{ $desc }}',@endforeach
                @foreach($summary['stock_internal_service']['details'] as $desc => $val)'Int: {{ $desc }}',@endforeach
                @foreach(($summary['stock_insurance']['details'] ?? []) as $desc => $val)'Ins: {{ $desc }}',@endforeach
            ]
        }
    };

    let isDrilled = false;

    const chartOptions = {
        chart: {
            type: 'donut',
            height: 250,
            fontFamily: 'Outfit, sans-serif',
            events: {
                dataPointSelection: (event, chartContext, config) => {
                    if (!isDrilled) {
                        const label = mainData.labels[config.dataPointIndex];
                        if (drillData[label] && drillData[label].series.length > 0) {
                            chart.updateOptions({
                                series: drillData[label].series,
                                labels: drillData[label].labels,
                                title: { text: label + ' Breakdown', align: 'center', style: { fontSize: '14px' } },
                                colors: undefined
                            });
                            isDrilled = true;
                        }
                    }
                }
            }
        },
        series: mainData.series,
        labels: mainData.labels,
        colors: mainData.colors,
        dataLabels: { enabled: false },
        plotOptions: {
            pie: {
                donut: {
                    size: '65%',
                    labels: { show: true, total: { show: true, label: 'Total', fontSize: '14px', fontWeight: 600, color: '#334155' } }
                }
            }
        },
        stroke: { show: false },
        legend: { position: 'right', fontSize: '13px', fontFamily: 'Outfit, sans-serif', markers: { radius: 12 } },
    };

    var chart = new ApexCharts(document.querySelector("#drilldownChart"), chartOptions);
    chart.render();
</script>
@endif
    </div>
@endsection
