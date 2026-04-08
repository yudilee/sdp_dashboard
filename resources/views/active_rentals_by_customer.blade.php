@extends('layouts.app')

@section('title', 'Active Rentals by Customer - SDP Stock')

@section('content')
<div x-data="customerGroupPage()" x-init="init()" class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                <a href="{{ route('dashboard') }}" class="text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                Active Rentals by Customer
            </h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Grouped by customer with lot serial count</p>
        </div>

        <div class="flex flex-wrap gap-2 w-full md:w-auto">
            <!-- Search -->
            <div class="relative flex-grow md:flex-grow-0 md:w-64">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <input x-model="search" type="text" class="w-full pl-10 pr-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" placeholder="Search customer...">
            </div>

            <!-- Export Dropdown -->
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" @click.away="open = false" class="flex items-center gap-2 px-4 py-2 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-xl hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition-colors font-medium text-sm border border-emerald-100 dark:border-emerald-800">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    Export
                    <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <div x-show="open" class="absolute right-0 mt-2 w-48 bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-100 dark:border-slate-700 py-2 z-50">
                    <a href="{{ route('active-rentals.by-customer.export', ['format' => 'xlsx']) }}" class="block px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700">Excel (.xlsx)</a>
                    <a href="{{ route('active-rentals.by-customer.export', ['format' => 'csv']) }}" class="block px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700">CSV (.csv)</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-100 dark:border-slate-800 shadow-sm text-center">
            <span class="block text-3xl font-black text-indigo-600 dark:text-indigo-400">{{ number_format($totalCustomers) }}</span>
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mt-1">Customers</span>
        </div>
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-100 dark:border-slate-800 shadow-sm text-center">
            <span class="block text-3xl font-black text-amber-600 dark:text-amber-400">{{ number_format($totalLots) }}</span>
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mt-1">Total Lot Serials</span>
        </div>
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-100 dark:border-slate-800 shadow-sm text-center">
            <span class="block text-3xl font-black text-emerald-600 dark:text-emerald-400">{{ $totalCustomers > 0 ? number_format($totalLots / $totalCustomers, 1) : 0 }}</span>
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mt-1">Avg Lots/Customer</span>
        </div>
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-100 dark:border-slate-800 shadow-sm text-center">
            <span class="block text-3xl font-black text-purple-600 dark:text-purple-400">{{ $result->count() > 0 ? $result->first()['lot_count'] : 0 }}</span>
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mt-1">Max Lots (Top 1)</span>
        </div>
    </div>

    <!-- Main Table Card -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800 overflow-hidden">
        
        <!-- Info Bar -->
        <div class="px-6 py-3 border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 flex items-center justify-between">
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Showing <span class="font-bold text-slate-700 dark:text-slate-200" x-text="filteredData.length"></span> of <span class="font-bold" x-text="allData.length"></span> customers
            </p>
            <button @click="expandAll = !expandAll" class="text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 transition-colors">
                <span x-text="expandAll ? 'Collapse All' : 'Expand All'"></span>
            </button>
        </div>

        <!-- Customer List -->
        <div class="divide-y divide-slate-100 dark:divide-slate-800">
            <template x-for="(row, idx) in filteredData" :key="row.customer">
                <div>
                    <!-- Customer Row -->
                    <button @click="row._expanded = !row._expanded" 
                            class="w-full flex items-center gap-3 px-6 py-4 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors text-left group">
                        <!-- Expand Arrow -->
                        <svg class="w-4 h-4 text-slate-400 transition-transform duration-200 flex-shrink-0" :class="row._expanded ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        <!-- Rank -->
                        <span class="w-8 h-8 flex-shrink-0 rounded-full flex items-center justify-center text-xs font-bold"
                              :class="row._rank <= 3 ? 'bg-gradient-to-br from-amber-100 to-amber-200 dark:from-amber-900/40 dark:to-amber-800/30 text-amber-700 dark:text-amber-400 ring-1 ring-amber-300 dark:ring-amber-700' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400'"
                              x-text="row._rank"></span>
                        <!-- Customer Name -->
                        <span class="flex-1 text-sm font-semibold text-slate-700 dark:text-slate-200 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors" x-text="row.customer"></span>
                        <!-- Lot Count Badge -->
                        <span class="flex-shrink-0 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 text-xs font-bold px-3 py-1.5 rounded-lg min-w-[70px] text-center" x-text="row.lot_count + ' lots'"></span>
                    </button>
                    <!-- Expanded Lot List -->
                    <div x-show="row._expanded" x-collapse>
                        <div class="bg-slate-50/80 dark:bg-slate-800/30 border-t border-slate-100 dark:border-slate-800">
                            <div class="px-6 py-3">
                                <table class="w-full text-left text-sm">
                                    <thead>
                                        <tr class="text-xs uppercase text-slate-400 dark:text-slate-500">
                                            <th class="pb-2 font-bold">Lot Number</th>
                                            <th class="pb-2 font-bold">Product</th>
                                            <th class="pb-2 font-bold">Location</th>
                                            <th class="pb-2 font-bold">Type</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                                        <template x-for="(lot, li) in row.lots" :key="li">
                                            <tr class="hover:bg-white dark:hover:bg-slate-800 transition-colors">
                                                <td class="py-2 pr-4">
                                                    <a :href="'/details?category=search&q=' + encodeURIComponent(lot.lot_number)" 
                                                       class="font-mono text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:underline" x-text="lot.lot_number"></a>
                                                </td>
                                                <td class="py-2 pr-4 text-xs text-slate-600 dark:text-slate-400" x-text="lot.product"></td>
                                                <td class="py-2 pr-4">
                                                    <span class="text-[10px] font-medium px-2 py-0.5 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300" x-text="lot.location"></span>
                                                </td>
                                                <td class="py-2">
                                                    <span x-show="lot.rental_type" class="text-[10px] font-medium px-2 py-0.5 rounded-full" 
                                                          :class="lot.rental_type === 'Subscription' ? 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400' : 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400'"
                                                          x-text="lot.rental_type"></span>
                                                    <span x-show="!lot.rental_type" class="text-slate-300 dark:text-slate-600 text-xs">-</span>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <!-- No results -->
            <div x-show="filteredData.length === 0" class="py-16 text-center">
                <svg class="w-12 h-12 text-slate-300 dark:text-slate-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                <p class="text-slate-400 dark:text-slate-500 font-medium">No customers matching "<span x-text="search"></span>"</p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function customerGroupPage() {
    return {
        allData: @json($result),
        search: '',
        expandAll: false,

        init() {
            // Add _expanded and _rank flags
            this.allData = this.allData.map((row, idx) => ({ ...row, _expanded: false, _rank: idx + 1 }));

            // Watch expandAll toggle
            this.$watch('expandAll', (val) => {
                this.allData.forEach(row => row._expanded = val);
            });
        },

        get filteredData() {
            if (!this.search) return this.allData;
            const q = this.search.toLowerCase();
            return this.allData.filter(row => row.customer.toLowerCase().includes(q));
        }
    };
}
</script>
@endsection
