@extends('layouts.app')

@section('title', 'CRM - HARENT Dashboard')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    @if(session('error'))
    <div x-data="{ show: true }" x-show="show" class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 flex items-center justify-between shadow-sm">
        <div class="flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span class="font-medium">{{ session('error') }}</span>
        </div>
        <button @click="show = false" class="text-red-500 hover:text-red-700"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
    </div>
    @endif

    @if(!$authenticated)
    <!-- Locked State -->
    <div class="glass rounded-2xl p-8 max-w-md mx-auto mt-20 text-center shadow-lg border border-slate-200 dark:border-slate-800">
        <div class="w-16 h-16 bg-indigo-100 dark:bg-indigo-900/50 rounded-full flex items-center justify-center mx-auto mb-6 shadow-inner">
            <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
        </div>
        <h2 class="text-2xl font-bold text-slate-800 dark:text-slate-100 mb-2">CRM Protected Area</h2>
        <p class="text-slate-500 dark:text-slate-400 mb-8">Please enter your password to access the CRM tools.</p>
        
        <form action="{{ route('crm.auth') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <input type="password" name="password" required placeholder="Enter Password" 
                       class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all shadow-sm text-center tracking-widest text-lg">
            </div>
            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-3 px-4 rounded-xl transition-colors shadow-md flex justify-center items-center gap-2">
                Unlock Access
            </button>
        </form>
    </div>
    @else
    <!-- Unlocked State -->
    <div x-data="{ 
        syncing: false, 
        async syncData() {
            if (this.syncing) return;
            this.syncing = true;
            try {
                const response = await fetch('{{ route('import.odoo.sync') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const result = await response.json();
                if (result.success) {
                    window.location.reload();
                } else {
                    alert('Sync failed: ' + result.message);
                }
            } catch (e) {
                alert('Sync failed: ' + e.message);
            } finally {
                this.syncing = false;
            }
        }
    }">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-3xl font-bold text-slate-800 dark:text-slate-100">Customer Relationship Management</h1>
                <p class="text-slate-500 dark:text-slate-400 mt-1">Master-Detail view of all active customers and their rentals.</p>
            </div>
            <button @click="syncData()" :disabled="syncing" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-400 disabled:cursor-not-allowed text-white transition-colors shadow-sm">
                <svg x-show="!syncing" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                <svg x-show="syncing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                <span x-text="syncing ? 'Syncing...' : 'Sync Odoo Data'"></span>
            </button>
        </div>

    @if($customers->isEmpty())
    <div class="glass p-12 rounded-2xl flex flex-col items-center justify-center text-center shadow-sm border border-slate-200 dark:border-slate-800">
        <div class="w-20 h-20 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center mb-4">
            <svg class="w-10 h-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
        </div>
        <h3 class="text-xl font-bold text-slate-800 dark:text-slate-200 mb-2">No CRM data available yet</h3>
        <p class="text-slate-500 dark:text-slate-400 max-w-md mx-auto mb-6">CRM data is automatically pulled when you sync the database with Odoo. Head over to the Import Data page to sync.</p>
        <a href="{{ route('import') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2.5 px-6 rounded-xl transition-colors shadow-md inline-flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
            Sync Data Now
        </a>
    </div>
    @else
    <!-- Results Card -->
    <div x-data="{ searchQuery: '' }" class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800 overflow-hidden mt-6">
        
        <!-- Search Bar -->
        <div class="p-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30">
            <div class="relative max-w-md">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <input x-model="searchQuery" type="text" class="block w-full pl-10 pr-3 py-2.5 border border-slate-300 dark:border-slate-700 rounded-xl leading-5 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-colors shadow-sm" placeholder="Search Customer Name...">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[800px]">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800 text-xs uppercase tracking-wider text-slate-500 dark:text-slate-400 font-bold">
                        <th class="py-4 px-6">Customer Name</th>
                        <th class="py-4 px-6">PIC Name</th>
                        <th class="py-4 px-6">Email</th>
                        <th class="py-4 px-6 text-right">Rentals</th>
                        <th class="py-4 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                @foreach($customers as $customer)
                <tbody 
                    x-data="{ expanded: false, customerName: '{{ strtolower(addslashes($customer->customer)) }}' }" 
                    x-show="searchQuery === '' || customerName.includes(searchQuery.toLowerCase())"
                    class="divide-y divide-slate-100 dark:divide-slate-800/50 border-b border-slate-100 dark:border-slate-800/50 last:border-0"
                >
                    <tr class="hover:bg-indigo-50/50 dark:hover:bg-indigo-900/10 transition-colors group cursor-pointer" @click="expanded = !expanded">
                        <td class="py-4 px-6 font-bold text-slate-800 dark:text-slate-200 align-middle">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-xs flex-shrink-0">
                                    {{ substr($customer->customer, 0, 2) }}
                                </div>
                                <span class="break-words">{{ $customer->customer }}</span>
                            </div>
                        </td>
                        <td class="py-4 px-6 text-slate-600 dark:text-slate-400 align-middle">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                <span>{{ $customer->pic_name ?: '-' }}</span>
                            </div>
                        </td>
                        <td class="py-4 px-6 text-slate-600 dark:text-slate-400 align-middle">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                <span>{{ $customer->pic_email ?: '-' }}</span>
                            </div>
                        </td>
                        <td class="py-4 px-6 text-right align-middle">
                            <span class="inline-flex items-center justify-center bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 text-xs font-bold px-2.5 py-1 rounded-full border border-indigo-200 dark:border-indigo-800">
                                {{ $customer->rentals->count() }}
                            </span>
                        </td>
                        <td class="py-4 px-6 text-right align-middle">
                            <button class="p-2 text-slate-400 hover:text-indigo-600 transition-colors rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-900/20" title="View Details">
                                <svg class="w-5 h-5 transform transition-transform duration-200" :class="expanded ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>
                        </td>
                    </tr>
                    
                    <!-- Detail Row -->
                    <tr x-show="expanded" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2" style="display: none;">
                        <td colspan="5" class="p-0 border-t border-indigo-100 dark:border-indigo-900/30 bg-slate-50/50 dark:bg-slate-800/20">
                            <div class="p-6">
                                <h4 class="text-sm font-bold text-indigo-800 dark:text-indigo-300 mb-4 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                    Active Rentals for this Customer
                                </h4>
                                <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-sm">
                                    <table class="w-full text-sm text-left whitespace-nowrap">
                                        <thead class="bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-semibold border-b border-slate-200 dark:border-slate-700">
                                            <tr>
                                                <th class="py-3 px-4">Rental ID</th>
                                                <th class="py-3 px-4">Product</th>
                                                <th class="py-3 px-4">Reserved Lot</th>
                                                <th class="py-3 px-4">Rental period</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50">
                                            @foreach($customer->rentals as $rental)
                                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                                <td class="py-3 px-4 font-medium text-slate-800 dark:text-slate-200">
                                                    <span class="px-2 py-1 bg-slate-100 dark:bg-slate-800 rounded font-mono text-xs border border-slate-200 dark:border-slate-700">{{ $rental->rental_id }}</span>
                                                </td>
                                                <td class="py-3 px-4 text-slate-600 dark:text-slate-400 whitespace-normal min-w-[200px]">{{ $rental->product ?: '-' }}</td>
                                                <td class="py-3 px-4 font-mono text-xs font-bold text-indigo-600 dark:text-indigo-400">
                                                    {{ $rental->reserved_lot ?: $rental->lot_number }}
                                                </td>
                                                <td class="py-3 px-4 text-slate-600 dark:text-slate-400">
                                                    @if($rental->rental_period_start || $rental->rental_period_end)
                                                        {{ $rental->rental_period_start ? \Carbon\Carbon::parse($rental->rental_period_start)->format('d/m/Y H:i:s') : '-' }} &rarr; {{ $rental->rental_period_end ? \Carbon\Carbon::parse($rental->rental_period_end)->format('d/m/Y H:i:s') : '-' }}
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
                @endforeach
            </table>
        </div>
    </div>
    @endif
    </div>
    @endif

</div>
@endsection
