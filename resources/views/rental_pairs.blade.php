@extends('layouts.app')

@section('title', 'Rental Pairs - SDP Stock')

@section('content')
<div x-data="rentalPairs()" x-init="init()" class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-amber-600 to-orange-600 dark:from-amber-400 dark:to-orange-400">
                Rental Pairs Analysis
            </h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Contracts with multiple vehicles (Main + Replacements)</p>
        </div>
        <div class="flex flex-col md:flex-row items-center gap-2 w-full md:w-auto">
             <!-- Search -->
            <div class="relative w-full md:w-64">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <input x-model="search" type="text" class="w-full pl-10 pr-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all" placeholder="Search Pairs...">
            </div>

            <span class="px-3 py-1 bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-400 rounded-full text-[10px] font-bold uppercase tracking-wider whitespace-nowrap">
                <span x-text="filteredPairs.length"></span> Active Pairs
            </span>
            <a href="{{ route('dashboard') }}" class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-600 dark:text-slate-300 font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors shadow-sm whitespace-nowrap">
                Back
            </a>
        </div>
    </div>

    <!-- Main List -->
    <div class="space-y-4">
        <template x-for="(pair, index) in filteredPairs" :key="pair.rental_id">
            <div x-data="{ open: false }" class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-100 dark:border-slate-800 overflow-hidden transition-all duration-300" :class="open ? 'ring-2 ring-amber-100 dark:ring-amber-900/50' : ''">
                <!-- Header Row -->
                <div @click="open = !open" class="p-4 flex items-center justify-between cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-full bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center text-amber-600 dark:text-amber-400 font-bold text-xs shadow-sm" x-text="index + 1"></div>
                        <div>
                            <h3 class="font-bold text-slate-800 dark:text-slate-100" x-text="pair.rental_id"></h3>
                            <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                                <span><span x-text="pair.vehicles.length"></span> Vehicles</span>
                                <span class="w-1 h-1 rounded-full bg-slate-300 dark:bg-slate-700"></span>
                                <span x-text="pair.main_vehicle ? (pair.main_vehicle.rental_type || 'Unknown Type') : 'No Main Vehicle'"></span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <!-- Status Indicators -->
                        <div class="flex -space-x-2">
                             <template x-for="v in pair.vehicles" :key="v.lot_number">
                             <div :title="v.vehicle_role" class="w-8 h-8 rounded-full border-2 border-white flex items-center justify-center text-xs font-bold text-white shadow-sm"
                                  :style="'background-color: ' + (v.vehicle_role == 'Main' ? '#10b981' : '#f59e0b')">
                                 <span x-text="v.vehicle_role.substring(0, 1)"></span>
                             </div>
                             </template>
                        </div>
                        
                        <button class="p-2 rounded-lg text-slate-400 hover:text-indigo-600 transition-colors">
                            <svg class="w-5 h-5 transition-transform duration-300" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                    </div>
                </div>

                <!-- Expanded Details -->
                <div x-show="open" x-collapse class="border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50 p-4">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <!-- Main Vehicle -->
                        <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-emerald-100 dark:border-emerald-900/30 shadow-sm relative overflow-hidden">
                            <div class="absolute top-0 right-0 px-3 py-1 bg-emerald-100 dark:bg-emerald-900 text-emerald-700 dark:text-emerald-300 text-[10px] font-bold rounded-bl-xl uppercase tracking-wider">MAIN</div>
                            <template x-if="pair.main_vehicle">
                                <div>
                                    <div class="mb-2">
                                        <div class="text-[10px] text-slate-400 dark:text-slate-500 uppercase font-bold tracking-wider">Product</div>
                                        <div class="font-bold text-slate-800 dark:text-slate-100" x-text="pair.main_vehicle.product"></div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2 text-sm">
                                        <div>
                                            <span class="text-slate-400 dark:text-slate-500 block text-[10px] uppercase font-bold">Lot Number</span>
                                            <span class="font-mono text-emerald-600 dark:text-emerald-400 font-medium" x-text="pair.main_vehicle.lot_number"></span>
                                        </div>
                                        <div>
                                            <span class="text-slate-400 dark:text-slate-500 block text-[10px] uppercase font-bold">Internal Ref</span>
                                            <span class="text-slate-600 dark:text-slate-300" x-text="pair.main_vehicle.internal_reference"></span>
                                        </div>
                                        <div class="col-span-2">
                                            <span class="text-slate-400 dark:text-slate-500 block text-[10px] uppercase font-bold">Location</span>
                                            <span class="text-slate-600 dark:text-slate-300" x-text="pair.main_vehicle.location"></span>
                                        </div>
                                    </div>
                                    <div class="mt-3 pt-3 border-t border-slate-50 dark:border-slate-800 flex justify-between items-center">
                                        <span class="text-xs text-slate-400 dark:text-slate-500">Qty: <span x-text="pair.main_vehicle.on_hand_quantity"></span></span>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold" :class="pair.main_vehicle.in_stock ? 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400'"
                                              x-text="pair.main_vehicle.in_stock ? 'IN STOCK' : 'OUT'">
                                        </span>
                                    </div>
                                </div>
                            </template>
                            <template x-if="!pair.main_vehicle">
                                <div class="text-red-500 text-sm font-medium">Missing Main Vehicle Record</div>
                            </template>
                        </div>

                        <!-- Replacement Vehicles -->
                        <div class="space-y-3">
                            <template x-for="repl in pair.replacement_vehicles" :key="repl.lot_number">
                            <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-amber-100 dark:border-amber-900/30 shadow-sm relative overflow-hidden">
                                <div class="absolute top-0 right-0 px-3 py-1 bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300 text-[10px] font-bold rounded-bl-xl uppercase tracking-wider">REPLACEMENT</div>
                                 <div class="mb-2">
                                    <div class="text-[10px] text-slate-400 dark:text-slate-500 uppercase font-bold tracking-wider">Product</div>
                                    <div class="font-bold text-slate-800 dark:text-slate-100 text-sm" x-text="repl.product"></div>
                                </div>
                                <div class="grid grid-cols-2 gap-2 text-sm">
                                    <div>
                                        <span class="text-slate-400 dark:text-slate-500 block text-[10px] uppercase font-bold">Lot Number</span>
                                        <span class="font-mono text-amber-600 dark:text-amber-400 font-medium" x-text="repl.lot_number"></span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 dark:text-slate-500 block text-[10px] uppercase font-bold">Internal Ref</span>
                                        <span class="text-slate-600 dark:text-slate-300" x-text="repl.internal_reference"></span>
                                    </div>
                                    <div class="col-span-2">
                                        <span class="text-slate-400 dark:text-slate-500 block text-[10px] uppercase font-bold">Location</span>
                                        <span class="text-slate-600 dark:text-slate-300" x-text="repl.location"></span>
                                    </div>
                                </div>
                            </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </template>
        
        <div x-show="filteredPairs.length === 0" class="text-center py-12 bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 border-dashed">
            <div class="w-16 h-16 bg-slate-50 dark:bg-slate-800 rounded-full flex items-center justify-center text-slate-400 dark:text-slate-600 mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
            </div>
            <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100">No Rental Pairs Found</h3>
            <p class="text-slate-500 dark:text-slate-400">There are no active contracts matching your search.</p>
        </div>
    </div>
</div>

<script>
    function rentalPairs() {
        return {
            pairs: Object.values(@json($rentalPairs)),
            search: '',
            
            get filteredPairs() {
                if (!this.search) return this.pairs;
                const term = this.search.toLowerCase();
                
                return this.pairs.filter(pair => {
                    // Match Rental ID
                    if (pair.rental_id && pair.rental_id.toLowerCase().includes(term)) return true;
                    
                    // Match any vehicle lot/product
                    return pair.vehicles.some(v => 
                        (v.lot_number && v.lot_number.toLowerCase().includes(term)) ||
                        (v.product && v.product.toLowerCase().includes(term))
                    );
                });
            }
        }
    }
</script>
@endsection
