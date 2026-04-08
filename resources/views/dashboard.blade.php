@extends('layouts.app')

@section('title', 'Dashboard - SDP Stock')

@section('content')
    <div x-data="dashboard()">
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

    <!-- Hero Card - Total Active Stock -->
    <div class="block relative overflow-hidden bg-gradient-to-r from-indigo-600 via-violet-600 to-purple-700 rounded-2xl p-6 mb-8 text-white shadow-xl">
        <!-- Decorative Elements -->
        <div class="absolute top-0 right-0 -mr-16 -mt-16 w-64 h-64 rounded-full bg-white opacity-5 blur-3xl pointer-events-none"></div>
        <div class="absolute bottom-0 left-1/3 -mb-20 w-48 h-48 rounded-full bg-cyan-400 opacity-10 blur-2xl pointer-events-none"></div>
        
        <div class="relative">
            <!-- Header Row -->
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-white/20 backdrop-blur-sm rounded-lg">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    </div>
                    <h2 class="text-lg font-bold text-white">Total Active Stock</h2>
                </div>
                @if(isset($metadata['imported_at']))
                <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white/15 backdrop-blur-sm text-xs border border-white/20 text-white/90">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>Updated {{ \Carbon\Carbon::parse($metadata['imported_at'])->diffForHumans() }}</span>
                </div>
                @endif
            </div>
            
            <!-- Stats Row - Spread across with dividers -->
            <div class="flex items-center justify-between">
                <!-- Total Count - Left -->
                <a href="{{ route('total.stock') }}" class="flex-1 group hover:scale-105 transition-transform cursor-pointer">
                    <p class="text-5xl font-black tracking-tight text-white group-hover:text-indigo-100 transition-colors">{{ number_format($summary['sdp_stock']) }}</p>
                    <p class="text-indigo-200 text-sm font-medium mt-1">Active Inventory Units</p>
                </a>

                <!-- Divider -->
                <div class="hidden sm:block w-px h-16 bg-white/20 mx-6"></div>

                <!-- SDP Owned - Center -->
                <a href="{{ route('details', ['category' => 'sdp_owned']) }}" class="flex-1 text-center group hover:scale-105 transition-transform cursor-pointer">
                    <p class="text-3xl font-bold text-white group-hover:text-indigo-100 transition-colors">{{ number_format($summary['sdp_stock'] - $summary['vendor_rent']) }}</p>
                    <p class="text-indigo-200 text-xs font-semibold uppercase tracking-wider mt-1">SDP Owned</p>
                </a>

                <!-- Divider -->
                <div class="hidden sm:block w-px h-16 bg-white/20 mx-6"></div>

                <!-- Vendor Rent -->
                <a href="{{ route('details', ['category' => 'vendor_rent']) }}" class="flex-1 text-center group hover:scale-105 transition-transform cursor-pointer">
                    <p class="text-3xl font-bold text-cyan-300 group-hover:text-cyan-200 transition-colors">{{ number_format($summary['vendor_rent']) }}</p>
                    <p class="text-indigo-200 text-xs font-semibold uppercase tracking-wider mt-1">Vendor Rent</p>
                </a>

                <!-- Divider -->
                <div class="hidden sm:block w-px h-16 bg-white/20 mx-6"></div>

                <!-- Active Rental - Right -->
                <a href="{{ route('details', ['category' => 'active_rentals']) }}" class="flex-1 text-right group hover:scale-105 transition-transform cursor-pointer">
                    <p class="text-3xl font-bold text-amber-300 group-hover:text-amber-200 transition-colors">{{ number_format($activeRentalData['total'] ?? 0) }}</p>
                    <p class="text-indigo-200 text-xs font-semibold uppercase tracking-wider mt-1">Active Rental</p>
                </a>
            </div>
        </div>
    </div>

    @if(($dashboardLayout ?? 'kpi_progress') === 'kpi_progress')
    <!-- KPI Progress Cards -->
    @php
        $total = $summary['sdp_stock'] ?? 0;
        $inStockVal = $summary['in_stock']['total'] ?? 0;
        $activeRentalVal = $activeRentalData['total'] ?? 0;
        $inServiceVal = ($summary['stock_external_service']['total'] ?? 0) + ($summary['stock_internal_service']['total'] ?? 0) + ($summary['stock_insurance']['total'] ?? 0);
        
        // Get target percentages from settings
        $targetInStockPct = (float) \App\Models\Setting::get('target_in_stock_pct', 10);
        $targetActiveRentalPct = (float) \App\Models\Setting::get('target_active_rental_pct', 82);
        $targetInServicePct = (float) \App\Models\Setting::get('target_in_service_pct', 8);
        
        // Calculate current percentages
        $currentInStockPct = $total > 0 ? round(($inStockVal / $total) * 100, 1) : 0;
        $currentActiveRentalPct = $total > 0 ? round(($activeRentalVal / $total) * 100, 1) : 0;
        $currentInServicePct = $total > 0 ? round(($inServiceVal / $total) * 100, 1) : 0;
        
        // Determine status (meet/exceed = good if >= target, stay under = good if <= target)
        $inStockStatus = $currentInStockPct >= $targetInStockPct ? 'success' : 'warning';
        $activeRentalStatus = $currentActiveRentalPct >= $targetActiveRentalPct ? 'success' : 'warning';
        $inServiceStatus = $currentInServicePct <= $targetInServicePct ? 'success' : 'danger';
    @endphp
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <!-- In Stock KPI -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-100 dark:border-slate-800 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <div class="p-1.5 bg-emerald-100 dark:bg-emerald-900/50 rounded-lg">
                        <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    </div>
                    <span class="text-sm font-bold text-slate-700 dark:text-slate-300">In Stock</span>
                </div>
                @if($inStockStatus === 'success')
                <span class="flex items-center gap-1 text-xs font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/30 px-2 py-1 rounded-full">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    On Track
                </span>
                @else
                <span class="flex items-center gap-1 text-xs font-bold text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/30 px-2 py-1 rounded-full">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01"></path></svg>
                    Below Target
                </span>
                @endif
            </div>
            <div class="flex items-end gap-2 mb-2">
                <span class="text-3xl font-black text-slate-800 dark:text-slate-100">{{ $currentInStockPct }}%</span>
                <span class="text-sm text-slate-500 dark:text-slate-400 mb-1">/ {{ $targetInStockPct }}% target</span>
            </div>
            <div class="relative h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                <div class="absolute inset-y-0 left-0 rounded-full transition-all duration-500 {{ $inStockStatus === 'success' ? 'bg-emerald-500' : 'bg-amber-500' }}" 
                     style="width: {{ min($currentInStockPct, 100) }}%"></div>
                <div class="absolute inset-y-0 bg-slate-400 dark:bg-slate-600 w-0.5" style="left: {{ $targetInStockPct }}%"></div>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">{{ number_format($inStockVal) }} of {{ number_format($total) }} units</p>
        </div>

        <!-- Active Rental KPI -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-100 dark:border-slate-800 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <div class="p-1.5 bg-amber-100 dark:bg-amber-900/50 rounded-lg">
                        <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    </div>
                    <span class="text-sm font-bold text-slate-700 dark:text-slate-300">Active Rental</span>
                </div>
                @if($activeRentalStatus === 'success')
                <span class="flex items-center gap-1 text-xs font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/30 px-2 py-1 rounded-full">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    On Track
                </span>
                @else
                <span class="flex items-center gap-1 text-xs font-bold text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/30 px-2 py-1 rounded-full">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01"></path></svg>
                    Below Target
                </span>
                @endif
            </div>
            <div class="flex items-end gap-2 mb-2">
                <span class="text-3xl font-black text-slate-800 dark:text-slate-100">{{ $currentActiveRentalPct }}%</span>
                <span class="text-sm text-slate-500 dark:text-slate-400 mb-1">/ {{ $targetActiveRentalPct }}% target</span>
            </div>
            <div class="relative h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                <div class="absolute inset-y-0 left-0 rounded-full transition-all duration-500 {{ $activeRentalStatus === 'success' ? 'bg-emerald-500' : 'bg-amber-500' }}" 
                     style="width: {{ min($currentActiveRentalPct, 100) }}%"></div>
                <div class="absolute inset-y-0 bg-slate-400 dark:bg-slate-600 w-0.5" style="left: {{ $targetActiveRentalPct }}%"></div>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">{{ number_format($activeRentalVal) }} of {{ number_format($total) }} units</p>
        </div>

        <!-- In Service KPI -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-100 dark:border-slate-800 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <div class="p-1.5 bg-red-100 dark:bg-red-900/50 rounded-lg">
                        <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    </div>
                    <span class="text-sm font-bold text-slate-700 dark:text-slate-300">In Service</span>
                </div>
                @if($inServiceStatus === 'success')
                <span class="flex items-center gap-1 text-xs font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/30 px-2 py-1 rounded-full">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    Under Target
                </span>
                @else
                <span class="flex items-center gap-1 text-xs font-bold text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/30 px-2 py-1 rounded-full">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01"></path></svg>
                    Over Target
                </span>
                @endif
            </div>
            <div class="flex items-end gap-2 mb-2">
                <span class="text-3xl font-black text-slate-800 dark:text-slate-100">{{ $currentInServicePct }}%</span>
                <span class="text-sm text-slate-500 dark:text-slate-400 mb-1">/ {{ $targetInServicePct }}% max</span>
            </div>
            <div class="relative h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                <div class="absolute inset-y-0 left-0 rounded-full transition-all duration-500 {{ $inServiceStatus === 'success' ? 'bg-emerald-500' : 'bg-red-500' }}" 
                     style="width: {{ min($currentInServicePct, 100) }}%"></div>
                <div class="absolute inset-y-0 bg-slate-400 dark:bg-slate-600 w-0.5" style="left: {{ $targetInServicePct }}%"></div>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">{{ number_format($inServiceVal) }} of {{ number_format($total) }} units (lower is better)</p>
        </div>
    </div>

    @else
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
                Vehicle In Customer
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
                <span class="bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 px-1.5 py-0.5 rounded font-medium">Ins: {{ $summary['stock_insurance']['total'] ?? 0 }}</span>
            </div>
        </a>

        <!-- Rental Pairs -->
        @if(isset($summary['rental_pairs_count']) && $summary['rental_pairs_count'] > 0)
        <div class="group bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-900/20 dark:to-amber-800/20 p-6 rounded-2xl shadow-sm dark:shadow-none border border-amber-200 dark:border-amber-800/50 hover:shadow-lg hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
            <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-amber-500 rounded-l-2xl"></div>
            
            <a href="{{ route('rental.pairs') }}" class="block">
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
            
            <div class="mt-4 pt-4 border-t border-amber-200/50 dark:border-amber-700/50">
                <a href="{{ route('rental.pairs', ['filter' => 'customer']) }}" class="flex items-center gap-2 text-xs font-bold text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 transition-colors group/link animate-pulse">
                    <span class="uppercase tracking-wide">Check Rental Pair Position</span>
                    <svg class="w-3 h-3 group-hover/link:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                </a>
            </div>
        </div>
        @else
        <div class="bg-slate-50 dark:bg-slate-900 p-6 rounded-2xl shadow-inner border border-slate-200 dark:border-slate-800 opacity-75">
            <p class="text-sm font-bold text-slate-400 uppercase tracking-wide mb-1">Rental Pairs</p>
            <h3 class="text-3xl font-bold text-slate-400">-</h3>
            <p class="mt-4 text-xs text-slate-400">No active pairs detected</p>
        </div>
        @endif
    </div>
    @endif


    <!-- Main Content Grid -->
    <!-- Main Content Breakdown (Row 1) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8 animate-enter delay-200">
        
        <!-- Left Column: Breakdowns -->
        <!-- Left Column: In Stock Breakdown -->
        <div>
            
            <!-- In Stock Breakdown -->
            <div class="bg-emerald-100 dark:bg-emerald-900/30 rounded-2xl shadow-sm dark:shadow-none border border-emerald-100 dark:border-emerald-800 p-6">
                <div class="flex items-center gap-2 mb-6 pb-4 border-b border-emerald-200/50 dark:border-emerald-800">
                    <div class="p-2 bg-emerald-200 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-400 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    </div>
                    <div>
                        <a href="{{ route('details', ['category' => 'in_stock']) }}" class="group/title">
                            <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100 group-hover/title:text-indigo-600 transition-colors">In Stock Detail</h3>
                        </a>
                        <p class="text-sm font-bold text-slate-600 dark:text-slate-400 mt-1">Total In Stock: {{ number_format($summary['in_stock']['total'] ?? 0) }}</p>
                    </div>
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

                    @php
                        $locations = $summary['in_stock']['details']['locations'] ?? [];
                        if (!isset($locations['SDP/LOST'])) {
                            $locations['SDP/LOST'] = 0;
                        }
                    @endphp

                    @foreach($locations as $loc => $val)
                        @if($loc === 'SDP/LOST' || ($val > 0 && $loc !== 'SDP/STOCK SOLD'))
                        <div class="flex items-center gap-2 group/loc">
                            <a href="{{ route('details', ['category' => 'in_stock', 'sub' => $loc]) }}" 
                               class="flex-grow flex justify-between items-center p-3 rounded-xl border transition-all 
                                      {{ $loc === 'SDP/LOST' ? 'border-red-200 dark:border-red-900/50 bg-red-50/50 dark:bg-red-900/10 hover:bg-red-100 dark:hover:bg-red-900/20' : 'border-slate-100 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                                <span class="{{ $loc === 'SDP/LOST' ? 'text-red-700 dark:text-red-400 font-bold' : 'text-slate-600 dark:text-slate-300 font-medium' }}">{{ $loc }}</span>
                                <span class="{{ $loc === 'SDP/LOST' ? 'bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400' }} text-xs font-bold px-2 py-1 rounded-md">{{ $val }}</span>
                            </a>
                            
                            @if($loc === 'SDP/LOST')
                            <button @click="openLocationHistory('{{ $loc }}')" 
                                    class="p-2.5 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:border-indigo-200 transition-all shadow-sm"
                                    title="View Movement History">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </button>
                            @endif
                        </div>
                        @endif
                    @endforeach
                </div>
            </div>

        </div>

        <!-- Right Column: Active & Service Stack -->
        <div class="space-y-8">
            
            <!-- Attention Needed (Moved to Top of Stack) -->
            @if(($summary['uncategorized']['total'] ?? 0) > 0)
            <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800 p-6">
                 <div class="flex items-center gap-2 mb-4">
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <h3 class="font-bold text-slate-800 dark:text-slate-100">Attention Needed</h3>
                </div>
                <a href="{{ route('details', ['category' => 'uncategorized']) }}" class="flex items-center justify-center gap-2 w-full py-3 bg-red-50 text-red-600 rounded-xl text-sm font-semibold hover:bg-red-100 transition-colors border border-red-100">
                    {{ $summary['uncategorized']['total'] }} Uncategorized Items
                </a>
            </div>
            @endif
            
            <!-- Active Rental Detail -->
            <div class="bg-amber-100 dark:bg-amber-900/30 rounded-2xl shadow-sm border border-amber-100 dark:border-amber-800 p-6">
                <div class="flex items-center justify-between gap-2 mb-6 pb-4 border-b border-amber-200/50 dark:border-amber-800">
                    <div class="flex items-center gap-2">
                        <div class="p-2 bg-amber-200 dark:bg-amber-900/50 text-amber-700 dark:text-amber-400 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div>
                            <a href="{{ route('details', ['category' => 'active_rentals']) }}" class="group/title">
                                 <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100 group-hover/title:text-indigo-600 transition-colors">Active Rental Detail</h3>
                            </a>
                            <p class="text-sm font-bold text-slate-600 dark:text-slate-400 mt-1">Total Active: {{ number_format($activeRentalData['total'] ?? 0) }}</p>
                        </div>
                    </div>
                    <!-- Group by Customer Link -->
                    <a href="{{ route('active-rentals.by-customer') }}" id="btn-group-by-customer"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold 
                              bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 
                              border border-indigo-200 dark:border-indigo-800 
                              hover:bg-indigo-100 dark:hover:bg-indigo-900/40 transition-all hover:shadow-sm whitespace-nowrap">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        Group by Customer
                    </a>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-6">
                     <!-- Left Side: In Customer -->
                     <div>
                         <h4 class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-3">In Customer ({{ number_format($activeRentalData['customer'] ?? 0) }})</h4>
                         
                         <div class="grid grid-cols-2 gap-3 mb-3">
                             <!-- Original -->
                             <a href="{{ route('details', ['category' => 'rented_original_customer']) }}" class="flex flex-col items-center p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl border border-emerald-100 dark:border-emerald-800 hover:border-emerald-300 dark:hover:border-emerald-700 transition-colors text-center group">
                                 <span class="text-2xl font-bold text-emerald-700 dark:text-emerald-400">{{ $summary['rented_in_customer']['details']['Original in Customer'] ?? 0 }}</span>
                                 <span class="text-xs font-bold uppercase text-emerald-600 dark:text-emerald-500 mt-1">Original</span>
                             </a>

                             <!-- Vendor Rent -->
                             <a href="{{ route('details', ['category' => 'vendor_rent']) }}" class="flex flex-col items-center p-3 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-100 dark:border-amber-800 hover:border-amber-300 dark:hover:border-amber-700 transition-colors text-center group">
                                 <span class="text-2xl font-bold text-amber-700 dark:text-amber-400">{{ $summary['rented_in_customer']['details']['Vendor Rent'] ?? 0 }}</span>
                                 <span class="text-xs font-bold uppercase text-amber-600 dark:text-amber-500 mt-1">Vendor Rent</span>
                             </a>

                             <!-- Replacement (Service) -->
                             <a href="{{ route('details', ['category' => 'rented', 'sub' => 'Replacement - Service']) }}" class="flex flex-col items-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-100 dark:border-blue-800 hover:border-blue-300 dark:hover:border-blue-700 transition-colors text-center group">
                                 <span class="text-2xl font-bold text-blue-700 dark:text-blue-400">{{ $summary['rented_in_customer']['details']['Replacement - Service'] ?? 0 }}</span>
                                 <span class="text-[10px] font-bold uppercase text-blue-600 dark:text-blue-500 mt-1">Repl. (Service)</span>
                             </a>

                             <!-- Replacement (RBO) -->
                             <a href="{{ route('details', ['category' => 'rented', 'sub' => 'Replacement - RBO']) }}" class="flex flex-col items-center p-3 bg-purple-50 dark:bg-purple-900/20 rounded-xl border border-purple-100 dark:border-purple-800 hover:border-purple-300 dark:hover:border-purple-700 transition-colors text-center group">
                                 <span class="text-2xl font-bold text-purple-700 dark:text-purple-400">{{ $summary['rented_in_customer']['details']['Replacement - RBO'] ?? 0 }}</span>
                                 <span class="text-[10px] font-bold uppercase text-purple-600 dark:text-purple-500 mt-1">Repl. (RBO)</span>
                             </a>
                         </div>

                         <!-- Check Rent Position (If > 0) -->
                         @if(isset($summary['rented_in_customer']['details']['Check Rent position']) && $summary['rented_in_customer']['details']['Check Rent position'] > 0)
                         <a href="{{ route('details', ['category' => 'rented', 'sub' => 'Check Rent position']) }}" class="flex justify-between items-center p-3 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-800 hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors group">
                             <span class="text-red-700 dark:text-red-400 text-sm font-bold">Check Rent Position</span>
                             <span class="text-xl font-bold text-red-700 dark:text-red-400">{{ $summary['rented_in_customer']['details']['Check Rent position'] }}</span>
                         </a>
                         @endif
                     </div>

                      <!-- Right Side: Other Active Locations -->
                      <div>
                          <h4 class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-3">Other Active Locations</h4>
                          
                          <div class="grid grid-cols-2 gap-3">
                              <!-- Active In Stock -->
                              @if(($activeRentalData['stock'] ?? 0) > 0)
                              <a href="{{ route('details', ['category' => 'stock_original', 'sub' => 'no_replace']) }}" class="flex flex-col items-center p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl border border-emerald-100 dark:border-emerald-800 hover:border-emerald-300 dark:hover:border-emerald-700 transition-colors text-center group">
                                  <span class="text-2xl font-bold text-emerald-700 dark:text-emerald-400">{{ $activeRentalData['stock'] }}</span>
                                  <span class="text-[10px] font-bold uppercase text-emerald-600 dark:text-emerald-500 mt-1">Active Stock</span>
                              </a>
                              @endif

                              <!-- External Service -->
                              @if(($activeRentalData['service']['external'] ?? 0) > 0)
                              <a href="{{ route('details', ['category' => 'external_service', 'sub' => 'Original Rented without Replace']) }}" class="flex flex-col items-center p-3 bg-red-50 dark:bg-red-900/20 rounded-xl border border-red-100 dark:border-red-800 hover:border-red-300 dark:hover:border-red-700 transition-colors text-center group">
                                  <span class="text-2xl font-bold text-red-700 dark:text-red-400">{{ $activeRentalData['service']['external'] }}</span>
                                  <span class="text-[10px] font-bold uppercase text-red-600 dark:text-red-500 mt-1">Ext. Service</span>
                              </a>
                              @endif

                              <!-- Internal Service - Always Displayed -->
                              <a href="{{ route('details', ['category' => 'internal_service', 'sub' => 'Original Rented without Replace']) }}" class="flex flex-col items-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-100 dark:border-blue-800 hover:border-blue-300 dark:hover:border-blue-700 transition-colors text-center group">
                                  <span class="text-2xl font-bold text-blue-700 dark:text-blue-400">{{ $activeRentalData['service']['internal'] ?? 0 }}</span>
                                  <span class="text-[10px] font-bold uppercase text-blue-600 dark:text-blue-500 mt-1">Int. Service</span>
                              </a>

                              <!-- Insurance -->
                              @if(($activeRentalData['service']['insurance'] ?? 0) > 0)
                              <a href="{{ route('details', ['category' => 'service_insurance', 'sub' => 'original_no_replace']) }}" class="flex flex-col items-center p-3 bg-purple-50 dark:bg-purple-900/20 rounded-xl border border-purple-100 dark:border-purple-800 hover:border-purple-300 dark:hover:border-purple-700 transition-colors text-center group">
                                  <span class="text-2xl font-bold text-purple-700 dark:text-purple-400">{{ $activeRentalData['service']['insurance'] }}</span>
                                  <span class="text-[10px] font-bold uppercase text-purple-600 dark:text-purple-500 mt-1">Insurance</span>
                              </a>
                              @endif
                          </div>
                      </div>
                </div>

                {{-- Overdue Rentals Alert (Full Width Footer) --}}
                @if(($activeRentalData['overdue'] ?? 0) > 0)
                <div class="mt-4 p-3 rounded-xl bg-gradient-to-r from-orange-50 to-red-50 dark:from-orange-900/20 dark:to-red-900/20 border border-orange-200 dark:border-orange-800">
                    <a href="{{ route('details', ['category' => 'overdue_rentals']) }}" class="flex items-center gap-3 group">
                        <div class="p-2 bg-orange-100 dark:bg-orange-900/50 rounded-lg">
                            <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-bold text-orange-700 dark:text-orange-300 group-hover:text-orange-800 transition-colors">Overdue Rentals (Include Today's End)</p>
                            <p class="text-xs text-orange-600 dark:text-orange-400">Vehicles with rental end ≤ today</p>
                        </div>
                        <span class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ $activeRentalData['overdue'] }}</span>
                    </a>
                </div>
                @endif
            </div>

            <!-- In Service Breakdown -->
             <div class="bg-red-100 dark:bg-red-900/30 rounded-2xl shadow-sm border border-red-100 dark:border-red-800 p-6">
                <div class="flex items-center gap-2 mb-6 pb-4 border-b border-red-200/50 dark:border-red-800">
                    <div class="p-2 bg-red-200 dark:bg-red-900/50 text-red-700 dark:text-red-400 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    </div>
                    <div>
                        <a href="{{ route('details', ['category' => 'in_service']) }}" class="group/title">
                            <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100 group-hover/title:text-indigo-600 transition-colors">In Service Detail</h3>
                        </a>
                        <p class="text-sm font-bold text-slate-600 dark:text-slate-400 mt-1">Total In Service: {{ number_format(($summary['stock_external_service']['total'] ?? 0) + ($summary['stock_internal_service']['total'] ?? 0) + ($summary['stock_insurance']['total'] ?? 0)) }}</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-x-8 gap-y-6">
                    <div>
                         <a href="{{ route('details', ['category' => 'external_service']) }}" class="flex flex-col items-center p-3 bg-red-50 dark:bg-red-900/20 rounded-xl border border-red-100 dark:border-red-800 hover:border-red-300 dark:hover:border-red-700 transition-colors text-center group mb-4">
                             <span class="text-2xl font-bold text-red-700 dark:text-red-400">{{ $summary['stock_external_service']['total'] ?? 0 }}</span>
                             <span class="text-xs font-bold uppercase text-red-600 dark:text-red-500 mt-1">External</span>
                         </a>
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
                         <a href="{{ route('details', ['category' => 'internal_service']) }}" class="flex flex-col items-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-100 dark:border-blue-800 hover:border-blue-300 dark:hover:border-blue-700 transition-colors text-center group mb-4">
                             <span class="text-2xl font-bold text-blue-700 dark:text-blue-400">{{ $summary['stock_internal_service']['total'] ?? 0 }}</span>
                             <span class="text-xs font-bold uppercase text-blue-600 dark:text-blue-500 mt-1">Internal</span>
                         </a>
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
                         <a href="{{ route('details', ['category' => 'insurance']) }}" class="flex flex-col items-center p-3 bg-purple-50 dark:bg-purple-900/20 rounded-xl border border-purple-100 dark:border-purple-800 hover:border-purple-300 dark:hover:border-purple-700 transition-colors text-center group mb-4">
                             <span class="text-2xl font-bold text-purple-700 dark:text-purple-400">{{ $summary['stock_insurance']['total'] ?? 0 }}</span>
                             <span class="text-xs font-bold uppercase text-purple-600 dark:text-purple-500 mt-1">Insurance</span>
                         </a>
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
    </div>

    <!-- Charts Row (Row 2) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8 animate-enter delay-300">
        <!-- Stock Distribution Chart -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800 p-6 h-auto">
            <h3 class="font-bold text-slate-800 dark:text-slate-100 mb-4">Stock Distribution</h3>
            <div id="drilldownChart" class="h-64"></div>
        </div>

        <!-- Active Rental Chart -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800 p-6 h-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-slate-800 dark:text-slate-100">Active Rental Distribution</h3>
                <span class="text-xs text-slate-500 dark:text-slate-400">Total: {{ number_format($activeRentalData['total'] ?? 0) }}</span>
            </div>
            <div id="activeRentalChart" class="h-64"></div>
        </div>
    </div>

    <!-- Historical Trend Chart -->
    @if($showHistory ?? true)
    <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm dark:shadow-none border border-slate-100 dark:border-slate-800 p-6 mb-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="font-bold text-slate-800 dark:text-slate-100 text-lg">Historical Trends</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400">Inventory movement over the last 30 days</p>
            </div>
            
            <div class="flex items-center gap-4">
                <!-- Target Toggle -->
                <div class="flex items-center gap-2">
                    <input type="checkbox" id="showTarget" onchange="toggleTargetLine()" class="w-4 h-4 rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500 dark:bg-slate-700">
                    <label for="showTarget" class="text-sm font-medium text-slate-600 dark:text-slate-400 cursor-pointer select-none">Show Target</label>
                </div>
                
                <!-- Trend Filter Dropdown -->
                <div class="relative">
                    <select id="trendFilter" onchange="updateTrendChart(this.value)" class="appearance-none bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 py-2 pl-4 pr-10 rounded-xl text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer">
                        <option value="overview">Overview</option>
                        <option value="stacked_overview">Overview (Stacked)</option>
                        <option value="percentage_stacked">% of Total (Stacked)</option>
                        <option value="rental_types">Rental Types</option>
                        <option value="locations">Key Locations</option>
                        <option value="rented_detail">Rented Breakdown</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-500">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </div>
                </div>
            </div>
        </div>
        <div id="trendChart" class="h-80 w-full"></div>
    </div>
    @endif




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
    
    <!-- Location History Modal -->
    <div x-show="locationHistoryModal.open" x-cloak 
         x-effect="if (locationHistoryModal.open) { document.body.style.overflow = 'hidden'; } else { document.body.style.overflow = ''; }"
         class="fixed inset-0 z-50 p-4 flex items-center justify-center bg-black/60 backdrop-blur-sm" @keydown.escape.window="locationHistoryModal.open = false">
        
        <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-5xl flex flex-col overflow-hidden" 
             style="max-height: 90vh;">
            
            <!-- Header -->
            <div class="flex-shrink-0 p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/50">
                <div>
                    <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Movement History
                    </h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                        Location: <span class="font-bold text-slate-700 dark:text-slate-200" x-text="locationHistoryModal.locationName"></span>
                    </p>
                </div>
                <button @click="locationHistoryModal.open = false" class="p-2 rounded-xl hover:bg-white dark:hover:bg-slate-700 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors shadow-sm border border-transparent hover:border-slate-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <!-- Body -->
            <div class="flex-1 overflow-y-auto p-5 custom-scrollbar bg-white dark:bg-slate-910">
                <!-- Loading -->
                <div x-show="locationHistoryModal.loading" class="flex flex-col items-center justify-center py-20">
                    <div class="relative w-16 h-16">
                        <div class="absolute inset-0 rounded-full border-4 border-slate-100 dark:border-slate-800"></div>
                        <div class="absolute inset-0 rounded-full border-4 border-indigo-500 border-t-transparent animate-spin"></div>
                    </div>
                    <span class="mt-4 text-sm font-medium text-slate-500 dark:text-slate-400">Fetching records from Odoo...</span>
                </div>

                <!-- Error -->
                <div x-show="locationHistoryModal.error" class="text-center py-12 px-4 bg-red-50 dark:bg-red-900/10 rounded-2xl border border-red-100 dark:border-red-900/30">
                    <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <p class="text-slate-800 dark:text-slate-200 font-bold" x-text="locationHistoryModal.error"></p>
                    <button @click="openLocationHistory(locationHistoryModal.locationName)" class="mt-4 text-sm text-indigo-600 dark:text-indigo-400 font-bold hover:underline">Try Again</button>
                </div>

                <!-- Data Table -->
                <div x-show="!locationHistoryModal.loading && !locationHistoryModal.error">
                    <template x-if="locationHistoryModal.data.length === 0">
                        <div class="text-center py-20 bg-slate-50 dark:bg-slate-800/20 rounded-2xl border border-dashed border-slate-200 dark:border-slate-800">
                            <p class="text-slate-400 dark:text-slate-500 font-medium tracking-wide">No recent movement records found.</p>
                        </div>
                    </template>

                    <template x-if="locationHistoryModal.data.length > 0">
                        <div class="overflow-x-auto rounded-xl border border-slate-100 dark:border-slate-800">
                            <table class="w-full text-left text-sm">
                                <thead class="bg-slate-50 dark:bg-slate-800 text-xs uppercase tracking-wider text-slate-500 dark:text-slate-400 font-bold">
                                    <tr>
                                        <th class="p-4">Date</th>
                                        <th class="p-4">Lot/Serial</th>
                                        <th class="p-4">Product</th>
                                        <th class="p-4">From</th>
                                        <th class="p-4">To</th>
                                        <th class="p-4">Reference</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <template x-for="(m, i) in locationHistoryModal.data" :key="i">
                                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                            <td class="p-4 whitespace-nowrap text-slate-500 dark:text-slate-400 font-medium" x-text="formatDate(m.date)"></td>
                                            <td class="p-4 font-mono text-xs font-bold text-indigo-600 dark:text-indigo-400" x-text="m.lot"></td>
                                            <td class="p-4 text-slate-700 dark:text-slate-300 font-medium" x-text="m.product"></td>
                                            <td class="p-4 text-xs text-slate-500 dark:text-slate-400" x-text="m.from"></td>
                                            <td class="p-4 text-xs text-slate-500 dark:text-slate-400" x-text="m.to"></td>
                                            <td class="p-4 font-mono text-[10px] text-slate-400 dark:text-slate-500" x-text="m.reference"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </template>
                </div>
            </div>
            <!-- Footer -->
            <div x-show="!locationHistoryModal.loading && locationHistoryModal.data.length > 0" class="p-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/30 dark:bg-slate-800/30 text-right">
                <span class="text-[10px] uppercase tracking-widest font-bold text-slate-400 dark:text-slate-500" x-text="locationHistoryModal.data.length + ' Recent Moves Shown'"></span>
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
    // Prepare Base Data
    const dates = historyData.map(h => h.date);
    
    // 1. Trend Chart Instance
    let trendChart = null;
    let showTarget = false;
    let currentFilter = 'overview';
    
    // Target values (loaded from API)
    let targetValues = {
        in_stock: 500,
        rented: 2500,
        in_service: 100,
        subscription: 1500,
        regular: 1000,
        in_stock_pct: 10,
        active_rental_pct: 82,
        in_service_pct: 8
    };
    
    // Load target values from settings API
    fetch('/api/settings/targets')
        .then(res => res.json())
        .then(data => {
            targetValues = data;
        })
        .catch(() => {
            // Use defaults if fetch fails
        });
    
    // Helper to safely extract nested data from summary_json
    const getNestedVal = (obj, path) => {
        return path.split('.').reduce((acc, part) => acc && acc[part], obj) || 0;
    };

    function initTrendChart() {
        if (historyData.length === 0) return;
        
        const chartEl = document.querySelector("#trendChart");
        if (!chartEl) return;


        const options = {
            chart: {
                type: 'bar',
                height: 320,
                fontFamily: 'Outfit, sans-serif',
                toolbar: { show: false },
                animations: { enabled: true, dynamicAnimation: { speed: 350 } },
                stacked: false
            },
            stroke: { width: [0, 0, 0, 3], curve: 'smooth' },
            plotOptions: {
                bar: {
                    columnWidth: '70%',
                    borderRadius: 4,
                    dataLabels: { position: 'top' }
                }
            },
            dataLabels: { enabled: false },
            series: [], // Populated by updateTrendChart
            xaxis: {
                categories: dates,
                type: 'datetime',
                tooltip: { enabled: false },
                axisBorder: { show: false },
                axisTicks: { show: false },
                labels: {
                    datetimeFormatter: {
                        year: 'yyyy',
                        month: 'MMM',
                        day: 'dd MMM',
                        hour: 'HH:mm'
                    }
                }
            },
            yaxis: [
                { show: true, title: { text: 'Count' } }
            ],
            grid: { borderColor: '#f1f5f9', strokeDashArray: 4 },
            legend: { position: 'top', horizontalAlign: 'right' },
            tooltip: {
                shared: true,
                intersect: false
            }
        };
        
        trendChart = new ApexCharts(document.querySelector("#trendChart"), options);
        trendChart.render();
        
        // Initial load
        updateTrendChart('overview');
    }
    
    // Toggle target line
    window.toggleTargetLine = function() {
        showTarget = document.getElementById('showTarget').checked;
        updateTrendChart(currentFilter);
    };

    // Dynamic Update Function
    window.updateTrendChart = function(filter) {
        if (!trendChart) return;
        currentFilter = filter;

        let newSeries = [];
        let newColors = [];
        let yAxisConfig = [{ show: true, title: { text: 'Count' } }];

        if (filter === 'overview' || filter === 'stacked_overview') {
            newSeries = [
                { name: 'In Stock', type: 'bar', data: historyData.map(h => h.in_stock) },
                { name: 'Rented', type: 'bar', data: historyData.map(h => h.rented) },
                { name: 'In Service', type: 'bar', data: historyData.map(h => h.in_service) }
            ];
            newColors = ['#10b981', '#f59e0b', '#ef4444'];
            
            // Add target line if enabled (only for non-stacked view to avoid confusion, or keep it?)
            // Usually target lines on stacked charts are tricky unless it's a total target. 
            // The user wants to see "addition", so maybe the target is for the TOTAL fleet?
            // Existing target logic adds "Rented Target". 
            // Let's keep it for now but maybe disable if stacked? 
            // Actually, if stacked, the bars add up to Total. A target line for "Rented" would appear low down.
            // Let's keep it consistent with Overview for now.
            if (showTarget) {
                newSeries.push({
                    name: 'Rented Target',
                    type: 'line',
                    data: historyData.map(() => targetValues.rented)
                });
                newColors.push('#6366f1');
            }
        } 
        else if (filter === 'rental_types') {
            // Subscription vs Regular
            newSeries = [
                { name: 'Subscription', type: 'bar', data: historyData.map(h => getNestedVal(h.summary_json || {}, 'rental_type_summary.Subscription')) },
                { name: 'Regular', type: 'bar', data: historyData.map(h => getNestedVal(h.summary_json || {}, 'rental_type_summary.Regular')) }
            ];
            newColors = ['#8b5cf6', '#3b82f6']; // Purple, Blue
            
            if (showTarget) {
                newSeries.push(
                    { name: 'Sub Target', type: 'line', data: historyData.map(() => targetValues.subscription) },
                    { name: 'Reg Target', type: 'line', data: historyData.map(() => targetValues.regular) }
                );
                newColors.push('#c084fc', '#60a5fa');
            }
        }
        else if (filter === 'percentage_stacked') {
            // Use RAW values and let stackType: '100%' handle the percentage calculation
            newSeries = [
                { name: 'In Stock', type: 'bar', data: historyData.map(h => h.in_stock) },
                { name: 'Rented', type: 'bar', data: historyData.map(h => h.rented) },
                { name: 'In Service', type: 'bar', data: historyData.map(h => h.in_service) }
            ];
            newColors = ['#10b981', '#f59e0b', '#ef4444'];
            // Do NOT force max: 100, let ApexCharts 100% stack mode handle the axis scale
            yAxisConfig = [{ show: true, title: { text: 'Percentage (%)' } }];
            
            if (showTarget) {
                const stockTgt = targetValues.in_stock_pct || 10;
                const rentalTgt = stockTgt + (targetValues.active_rental_pct || 82);
                
                newSeries.push(
                    { name: 'Stock Target', type: 'line', data: historyData.map(() => stockTgt) },
                    { name: 'Service Limit', type: 'line', data: historyData.map(() => rentalTgt) }
                );
                // Green for Stock Target, Red for Service Limit (upper bound)
                newColors.push('#10b981', '#ef4444');
            }
        }
        else if (filter === 'locations') {
            // Key Cities
            const cities = ['Jakarta', 'Surabaya', 'Semarang', 'Bandung'];
            const colors = ['#6366f1', '#f43f5e', '#14b8a6', '#f59e0b']; // Indigo, Rose, Teal, Amber
            
            cities.forEach((city, index) => {
                newSeries.push({
                    name: city,
                    type: 'bar',
                    data: historyData.map(h => {
                        // Look for city in location details
                        let val = 0;
                        const locs = getNestedVal(h.summary_json || {}, 'in_stock.details.locations') || {};
                        for (let k in locs) {
                            if (k.toLowerCase().includes(city.toLowerCase())) val += locs[k];
                        }
                        return val;
                    })
                });
                newColors.push(colors[index]);
            });
        }
        else if (filter === 'rented_detail') {
            // Original vs Vendor vs Replacement
            newSeries = [
                { name: 'Original', type: 'bar', data: historyData.map(h => getNestedVal(h.summary_json || {}, 'rented_in_customer.details.Original in Customer')) },
                { name: 'Vendor Rent', type: 'bar', data: historyData.map(h => getNestedVal(h.summary_json || {}, 'rented_in_customer.details.Vendor Rent')) },
                { name: 'Replacement', type: 'bar', data: historyData.map(h => {
                    const r1 = getNestedVal(h.summary_json || {}, 'rented_in_customer.details.Replacement - Service');
                    const r2 = getNestedVal(h.summary_json || {}, 'rented_in_customer.details.Replacement - RBO');
                    return r1 + r2;
                }) }
            ];
            newColors = ['#10b981', '#06b6d4', '#f97316']; // Green, Cyan, Orange
        }

        const isStacked = filter === 'stacked_overview' || filter === 'percentage_stacked';

        trendChart.updateOptions({
            series: newSeries,
            colors: newColors,
            yaxis: yAxisConfig,
            chart: {
                type: 'bar', // Base type bar, series override
                stacked: isStacked,
                stackType: filter === 'percentage_stacked' ? '100%' : 'normal'
            },
            stroke: {
                width: newSeries.map(s => s.type === 'line' ? 2 : 0), // Thinner lines (2px)
                curve: 'straight', // Straight lines for targets look cleaner
                dashArray: newSeries.map(s => s.type === 'line' ? 5 : 0) // Dashed lines for targets
            },
            plotOptions: {
                bar: {
                    columnWidth: '70%',
                    borderRadius: isStacked ? 0 : 4, // Remove radius for stacked internal bars
                    dataLabels: { position: 'top' }
                }
            }
        });
    };

    // Initialize after page load
    initTrendChart();

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
                                title: { text: label + ' Detail', align: 'center', style: { fontSize: '14px' } },
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

    // 3. Active Rental Distribution Chart
    const activeRentalData = {
        customer: {{ $activeRentalData['customer'] ?? 0 }},
        stock: {{ $activeRentalData['stock'] ?? 0 }},
        external: {{ $activeRentalData['service']['external'] ?? 0 }},
        internal: {{ $activeRentalData['service']['internal'] ?? 0 }},
        insurance: {{ $activeRentalData['service']['insurance'] ?? 0 }}
    };
    
    const activeRentalOptions = {
        chart: {
            type: 'donut',
            height: 250,
            fontFamily: 'Outfit, sans-serif',
        },
        series: [
            activeRentalData.customer,
            activeRentalData.stock,
            activeRentalData.external + activeRentalData.internal + activeRentalData.insurance
        ],
        labels: ['In Customer', 'In Stock (Active)', 'In Service (Active)'],
        colors: ['#f59e0b', '#10b981', '#ef4444'],
        dataLabels: { enabled: false },
        plotOptions: {
            pie: {
                donut: {
                    size: '65%',
                    labels: { 
                        show: true, 
                        total: { 
                            show: true, 
                            label: 'Active', 
                            fontSize: '14px', 
                            fontWeight: 600, 
                            color: '#334155' 
                        } 
                    }
                }
            }
        },
        stroke: { show: false },
        legend: { position: 'bottom', fontSize: '12px', fontFamily: 'Outfit, sans-serif', markers: { radius: 12 } },
    };
    
    const activeRentalChart = new ApexCharts(document.querySelector("#activeRentalChart"), activeRentalOptions);
    activeRentalChart.render();


</script>
@endif
    <script>
        function dashboard() {
            return {
                locationHistoryModal: {
                    open: false,
                    locationName: '',
                    loading: false,
                    error: null,
                    data: []
                },
                
                formatDate(dateStr) {
                    if (!dateStr) return '-';
                    const d = new Date(dateStr);
                    if (isNaN(d.getTime())) return dateStr;
                    return d.toLocaleDateString('id-ID', { 
                        year: 'numeric', 
                        month: 'short', 
                        day: 'numeric' 
                    });
                },

                async openLocationHistory(locationName) {
                    this.locationHistoryModal.open = true;
                    this.locationHistoryModal.locationName = locationName;
                    this.locationHistoryModal.loading = true;
                    this.locationHistoryModal.error = null;
                    this.locationHistoryModal.data = [];
                    
                    try {
                        const response = await fetch(`/api/location-history?location=${encodeURIComponent(locationName)}`);
                        if (!response.ok) throw new Error('API server returned error');
                        const result = await response.json();
                        
                        if (result.success) {
                            this.locationHistoryModal.data = result.data;
                        } else {
                            this.locationHistoryModal.error = result.message || 'Failed to fetch movement history';
                        }
                    } catch (e) {
                        this.locationHistoryModal.error = 'Connection failed: ' + e.message;
                    } finally {
                        this.locationHistoryModal.loading = false;
                    }
                }
            }
        }
    </script>
@endsection
