@extends('layouts.app')

@section('title', 'Details - SDP Stock')

@section('content')
<style>
/* Frozen Table Styles - Like Excel Freeze Panes */
.frozen-table-container {
    max-height: calc(100vh - 280px);
    overflow: auto;
    position: relative;
}

.frozen-table {
    border-collapse: separate;
    border-spacing: 0;
}

/* Sticky Header Row */
.frozen-table thead {
    position: sticky;
    top: 0;
    z-index: 20;
}

.frozen-table thead th {
    background: rgb(248 250 252); /* slate-50 */
}

.dark .frozen-table thead th {
    background: rgb(23 23 23); /* slate-950 */
}

/* Sticky First Column (Lot Number) */
.frozen-table th.sticky-col,
.frozen-table td.sticky-col {
    position: sticky;
    left: 0;
    z-index: 10;
}

/* Header + First Column intersection needs highest z-index */
.frozen-table thead th.sticky-col {
    z-index: 30;
    background: rgb(248 250 252);
}

.dark .frozen-table thead th.sticky-col {
    background: rgb(23 23 23);
}

/* Background color for sticky cells in tbody */
.frozen-table tbody td.sticky-col {
    background: rgb(255 255 255); /* white */
}

.dark .frozen-table tbody td.sticky-col {
    background: rgb(15 23 42); /* slate-900 */
}

/* Hover state for sticky cells */
.frozen-table tbody tr:hover td.sticky-col {
    background: rgb(248 250 252); /* fully opaque slate-50 */
}

.dark .frozen-table tbody tr:hover td.sticky-col {
    background: rgb(30 41 59); /* fully opaque slate-800 */
}

/* Shadow effect for frozen column when scrolled */
.frozen-table th.sticky-col::after,
.frozen-table td.sticky-col::after {
    content: '';
    position: absolute;
    top: 0;
    right: -8px;
    bottom: 0;
    width: 8px;
    background: linear-gradient(to right, rgba(0,0,0,0.05), transparent);
    pointer-events: none;
}

.dark .frozen-table th.sticky-col::after,
.dark .frozen-table td.sticky-col::after {
    background: linear-gradient(to right, rgba(0,0,0,0.2), transparent);
}
</style>
<div x-data="itemTable()" x-init="init()" class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-800 overflow-hidden theme-transition">
    
    <!-- Toolbar -->
    <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex flex-col gap-4">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                    <a href="{{ route('dashboard') }}" class="text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    </a>
                    {{ ucfirst(str_replace('_', ' ', $category ?? 'All Items')) }}
                    @if($sub)
                    <span class="text-slate-300 dark:text-slate-600">/</span>
                    <span class="text-indigo-600 dark:text-indigo-400 font-medium">{{ $sub }}</span>
                    @endif
                </h1>
                <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Showing <span x-text="filteredItems.length"></span> items</p>
            </div>

            <div class="flex flex-wrap gap-2 w-full md:w-auto">
                <!-- Search -->
                <div class="relative flex-grow md:flex-grow-0 md:w-64">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <input x-model="search" type="text" class="w-full pl-10 pr-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" placeholder="Search...">
                </div>

                <!-- Columns Dropdown -->
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" @click.away="open = false" class="flex items-center gap-2 px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-600 dark:text-slate-300 font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path></svg>
                        Cols
                    </button>
                    <div x-show="open" class="absolute right-0 mt-2 w-56 bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-100 dark:border-slate-700 py-2 z-50 max-h-96 overflow-y-auto">
                        <template x-for="(col, id) in columns" :key="id">
                            <label class="flex items-center px-4 py-2 hover:bg-slate-50 dark:hover:bg-slate-700 cursor-pointer">
                                <input type="checkbox" x-model="col.visible" class="rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500 mr-2">
                                <span class="text-sm text-slate-700 dark:text-slate-300" x-text="col.label"></span>
                            </label>
                        </template>
                    </div>
                </div>

                <!-- Export Dropdown -->
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" @click.away="open = false" class="flex items-center gap-2 px-4 py-2 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-xl hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition-colors font-medium text-sm border border-emerald-100 dark:border-emerald-800">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        Export
                        <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div x-show="open" class="absolute right-0 mt-2 w-48 bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-100 dark:border-slate-700 py-2 z-50">
                        <a href="{{ route('export', array_merge(request()->all(), ['format' => 'csv'])) }}" class="block px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700">CSV (.csv)</a>
                        <a href="{{ route('export', array_merge(request()->all(), ['format' => 'xlsx'])) }}" class="block px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700">Excel (.xlsx)</a>
                        <a href="{{ route('export', array_merge(request()->all(), ['format' => 'pdf'])) }}" class="block px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700">PDF (.pdf)</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Row -->
        <div class="flex flex-wrap gap-3 pb-2 overflow-x-auto no-scrollbar">
            <!-- Location Filter -->
            <select x-model="filters.location" class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 text-sm text-slate-600 dark:text-slate-300 focus:outline-none focus:border-indigo-500 bg-white dark:bg-slate-800 transition-colors">
                <option value="">All Locations</option>
                @foreach($locations as $loc)
                <option value="{{ $loc }}">{{ $loc }}</option>
                @endforeach
            </select>

            <!-- Role Filter -->
            <select x-model="filters.role" class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 text-sm text-slate-600 dark:text-slate-300 focus:outline-none focus:border-indigo-500 bg-white dark:bg-slate-800 transition-colors">
                <option value="">All Roles</option>
                @foreach($roles as $role)
                <option value="{{ $role }}">{{ $role }}</option>
                @endforeach
            </select>

            <!-- Type Filter -->
            <select x-model="filters.type" class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 text-sm text-slate-600 dark:text-slate-300 focus:outline-none focus:border-indigo-500 bg-white dark:bg-slate-800 transition-colors">
                <option value="">All Types</option>
                @foreach($types as $type)
                <option value="{{ $type }}">{{ $type }}</option>
                @endforeach
            </select>

            <!-- Year Filter -->
            <select x-model="filters.year" class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 text-sm text-slate-600 dark:text-slate-300 focus:outline-none focus:border-indigo-500 bg-white dark:bg-slate-800 transition-colors">
                <option value="">All Years</option>
                @foreach($years as $yr)
                <option value="{{ $yr }}">{{ $yr }}</option>
                @endforeach
            </select>
            
            <button @click="resetFilters()" x-show="hasActiveFilters" class="px-3 py-1.5 rounded-lg text-sm text-red-500 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 font-medium transition-colors">
                Reset
            </button>
        </div>
    </div>

    <!-- Table -->
    <div class="frozen-table-container custom-scrollbar pb-2">
        <table class="frozen-table w-full text-left" :style="'table-layout: fixed; min-width: ' + Math.max(1200, tableWidth) + 'px;'">
            <thead class="bg-slate-50 dark:bg-slate-950 sticky top-0 z-10 text-xs uppercase font-semibold text-slate-500 dark:text-slate-400">
                <tr>
                    <th x-show="columns.lot_number.visible" :style="'width: ' + columns.lot_number.width + 'px'" class="sticky-col relative p-4 border-b border-slate-100 dark:border-slate-800 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors select-none group">
                        <div @click="sortBy('lot_number')" class="flex items-center gap-1">Lot Number <span x-show="sortCol === 'lot_number'" x-text="sortAsc ? '↑' : '↓'"></span></div>
                        <div @mousedown="startResize($event, 'lot_number')" class="absolute right-0 top-0 bottom-0 w-1 cursor-col-resize hover:bg-indigo-400 group-hover:bg-slate-300 dark:group-hover:bg-slate-700 transition-colors"></div>
                    </th>
                    <th x-show="columns.product.visible" :style="'width: ' + columns.product.width + 'px'" class="relative p-4 border-b border-slate-100 dark:border-slate-800 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors select-none group">
                        <div @click="sortBy('product')" class="flex items-center gap-1">Product <span x-show="sortCol === 'product'" x-text="sortAsc ? '↑' : '↓'"></span></div>
                         <div @mousedown="startResize($event, 'product')" class="absolute right-0 top-0 bottom-0 w-1 cursor-col-resize hover:bg-indigo-400 group-hover:bg-slate-300 dark:group-hover:bg-slate-700 transition-colors"></div>
                    </th>
                    <th x-show="columns.year.visible" :style="'width: ' + columns.year.width + 'px'" class="relative p-4 border-b border-slate-100 dark:border-slate-800 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors select-none group">
                        <div @click="sortBy('year')" class="flex items-center gap-1">Year <span x-show="sortCol === 'year'" x-text="sortAsc ? '↑' : '↓'"></span></div>
                         <div @mousedown="startResize($event, 'year')" class="absolute right-0 top-0 bottom-0 w-1 cursor-col-resize hover:bg-indigo-400 group-hover:bg-slate-300 dark:group-hover:bg-slate-700 transition-colors"></div>
                    </th>
                    <th x-show="columns.location.visible" :style="'width: ' + columns.location.width + 'px'" class="relative p-4 border-b border-slate-100 dark:border-slate-800 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors select-none group">
                        <div @click="sortBy('location')" class="flex items-center gap-1">Location <span x-show="sortCol === 'location'" x-text="sortAsc ? '↑' : '↓'"></span></div>
                         <div @mousedown="startResize($event, 'location')" class="absolute right-0 top-0 bottom-0 w-1 cursor-col-resize hover:bg-indigo-400 group-hover:bg-slate-300 dark:group-hover:bg-slate-700 transition-colors"></div>
                    </th>
                    <th x-show="columns.qty.visible" :style="'width: ' + columns.qty.width + 'px'" class="relative p-4 border-b border-slate-100 dark:border-slate-800 text-center cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors select-none group">
                        <div @click="sortBy('on_hand_quantity')" class="flex items-center justify-center gap-1">Qty <span x-show="sortCol === 'on_hand_quantity'" x-text="sortAsc ? '↑' : '↓'"></span></div>
                         <div @mousedown="startResize($event, 'qty')" class="absolute right-0 top-0 bottom-0 w-1 cursor-col-resize hover:bg-indigo-400 group-hover:bg-slate-300 dark:group-hover:bg-slate-700 transition-colors"></div>
                    </th>
                    <th x-show="columns.type.visible" :style="'width: ' + columns.type.width + 'px'" class="relative p-4 border-b border-slate-100 dark:border-slate-800 text-center cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors select-none group">
                        <div @click="sortBy('is_vendor_rent')" class="flex items-center justify-center gap-1">Type <span x-show="sortCol === 'is_vendor_rent'" x-text="sortAsc ? '↑' : '↓'"></span></div>
                         <div @mousedown="startResize($event, 'type')" class="absolute right-0 top-0 bottom-0 w-1 cursor-col-resize hover:bg-indigo-400 group-hover:bg-slate-300 dark:group-hover:bg-slate-700 transition-colors"></div>
                    </th>
                    <th x-show="columns.current_customer.visible" :style="'width: ' + columns.current_customer.width + 'px'" class="relative p-4 border-b border-slate-100 dark:border-slate-800 text-left cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors select-none group">
                        <div @click="sortBy('current_customer')" class="flex items-center gap-1">Customer <span x-show="sortCol === 'current_customer'" x-text="sortAsc ? '↑' : '↓'"></span></div>
                         <div @mousedown="startResize($event, 'current_customer')" class="absolute right-0 top-0 bottom-0 w-1 cursor-col-resize hover:bg-indigo-400 group-hover:bg-slate-300 dark:group-hover:bg-slate-700 transition-colors"></div>
                    </th>
                    <th x-show="columns.warehouse.visible" :style="'width: ' + columns.warehouse.width + 'px'" class="relative p-4 border-b border-slate-100 dark:border-slate-800 text-left cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors select-none group">
                        <div @click="sortBy('warehouse')" class="flex items-center gap-1">Warehouse <span x-show="sortCol === 'warehouse'" x-text="sortAsc ? '↑' : '↓'"></span></div>
                         <div @mousedown="startResize($event, 'warehouse')" class="absolute right-0 top-0 bottom-0 w-1 cursor-col-resize hover:bg-indigo-400 group-hover:bg-slate-300 dark:group-hover:bg-slate-700 transition-colors"></div>
                    </th>
                    <th x-show="columns.status.visible" :style="'width: ' + columns.status.width + 'px'" class="relative p-4 border-b border-slate-100 dark:border-slate-800 text-center cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors select-none group">
                        <div @click="sortBy('rental_id')" class="flex items-center justify-center gap-1">Rental Status <span x-show="sortCol === 'rental_id'" x-text="sortAsc ? '↑' : '↓'"></span></div>
                         <div @mousedown="startResize($event, 'status')" class="absolute right-0 top-0 bottom-0 w-1 cursor-col-resize hover:bg-indigo-400 group-hover:bg-slate-300 dark:group-hover:bg-slate-700 transition-colors"></div>
                    </th>
                    <!-- New Columns -->
                    <th x-show="columns.start_date.visible" :style="'width: ' + columns.start_date.width + 'px'" class="relative p-4 border-b border-slate-100 dark:border-slate-800 text-center cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors select-none group">
                        <div @click="sortBy('actual_start_rental')" class="flex items-center justify-center gap-1">Start <span x-show="sortCol === 'actual_start_rental'" x-text="sortAsc ? '↑' : '↓'"></span></div>
                        <div @mousedown="startResize($event, 'start_date')" class="absolute right-0 top-0 bottom-0 w-1 cursor-col-resize hover:bg-indigo-400 group-hover:bg-slate-300 dark:group-hover:bg-slate-700 transition-colors"></div>
                    </th>
                    <th x-show="columns.end_date.visible" :style="'width: ' + columns.end_date.width + 'px'" class="relative p-4 border-b border-slate-100 dark:border-slate-800 text-center cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors select-none group">
                        <div @click="sortBy('actual_end_rental')" class="flex items-center justify-center gap-1">End <span x-show="sortCol === 'actual_end_rental'" x-text="sortAsc ? '↑' : '↓'"></span></div>
                        <div @mousedown="startResize($event, 'end_date')" class="absolute right-0 top-0 bottom-0 w-1 cursor-col-resize hover:bg-indigo-400 group-hover:bg-slate-300 dark:group-hover:bg-slate-700 transition-colors"></div>
                    </th>
                    <th x-show="columns.vehicle_role.visible" :style="'width: ' + columns.vehicle_role.width + 'px'" class="relative p-4 border-b border-slate-100 dark:border-slate-800 text-center cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors select-none group">
                        <div @click="sortBy('vehicle_role')" class="flex items-center justify-center gap-1">Role <span x-show="sortCol === 'vehicle_role'" x-text="sortAsc ? '↑' : '↓'"></span></div>
                        <div @mousedown="startResize($event, 'vehicle_role')" class="absolute right-0 top-0 bottom-0 w-1 cursor-col-resize hover:bg-indigo-400 group-hover:bg-slate-300 dark:group-hover:bg-slate-700 transition-colors"></div>
                    </th>
                    <th x-show="columns.linked.visible" :style="'width: ' + columns.linked.width + 'px'" class="relative p-4 border-b border-slate-100 dark:border-slate-800 text-center cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors select-none group">
                        <div @click="sortBy('linked_vehicle')" class="flex items-center justify-center gap-1">Linked <span x-show="sortCol === 'linked_vehicle'" x-text="sortAsc ? '↑' : '↓'"></span></div>
                        <div @mousedown="startResize($event, 'linked')" class="absolute right-0 top-0 bottom-0 w-1 cursor-col-resize hover:bg-indigo-400 group-hover:bg-slate-300 dark:group-hover:bg-slate-700 transition-colors"></div>
                    </th>
                    <th x-show="columns.in_stock.visible" :style="'width: ' + columns.in_stock.width + 'px'" class="relative p-4 border-b border-slate-100 dark:border-slate-800 text-center cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors select-none group">
                        <div @click="sortBy('in_stock')" class="flex items-center justify-center gap-1">Stock <span x-show="sortCol === 'in_stock'" x-text="sortAsc ? '↑' : '↓'"></span></div>
                        <div @mousedown="startResize($event, 'in_stock')" class="absolute right-0 top-0 bottom-0 w-1 cursor-col-resize hover:bg-indigo-400 group-hover:bg-slate-300 dark:group-hover:bg-slate-700 transition-colors"></div>
                    </th>
                    <th x-show="columns.purchase_date.visible" :style="'width: ' + columns.purchase_date.width + 'px'" class="relative p-4 border-b border-slate-100 dark:border-slate-800 text-center cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors select-none group">
                        <div @click="sortBy('purchase_date')" class="flex items-center justify-center gap-1">Purc. Date <span x-show="sortCol === 'purchase_date'" x-text="sortAsc ? '↑' : '↓'"></span></div>
                        <div @mousedown="startResize($event, 'purchase_date')" class="absolute right-0 top-0 bottom-0 w-1 cursor-col-resize hover:bg-indigo-400 group-hover:bg-slate-300 dark:group-hover:bg-slate-700 transition-colors"></div>
                    </th>
                    <!-- Repair Order Columns -->
                    <th x-show="columns.repair_order.visible" :style="'width: ' + columns.repair_order.width + 'px'" class="relative p-4 border-b border-slate-100 dark:border-slate-800 text-left cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors select-none group">
                        <div @click="sortBy('repair_order_name')" class="flex items-center gap-1">Repair Order <span x-show="sortCol === 'repair_order_name'" x-text="sortAsc ? '↑' : '↓'"></span></div>
                        <div @mousedown="startResize($event, 'repair_order')" class="absolute right-0 top-0 bottom-0 w-1 cursor-col-resize hover:bg-indigo-400 group-hover:bg-slate-300 dark:group-hover:bg-slate-700 transition-colors"></div>
                    </th>
                    <th x-show="columns.repair_jo_date.visible" :style="'width: ' + columns.repair_jo_date.width + 'px'" class="relative p-4 border-b border-slate-100 dark:border-slate-800 text-center cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors select-none group">
                        <div @click="sortBy('repair_schedule_date')" class="flex items-center justify-center gap-1">JO Date <span x-show="sortCol === 'repair_schedule_date'" x-text="sortAsc ? '↑' : '↓'"></span></div>
                        <div @mousedown="startResize($event, 'repair_jo_date')" class="absolute right-0 top-0 bottom-0 w-1 cursor-col-resize hover:bg-indigo-400 group-hover:bg-slate-300 dark:group-hover:bg-slate-700 transition-colors"></div>
                    </th>
                    <th x-show="columns.repair_service_type.visible" :style="'width: ' + columns.repair_service_type.width + 'px'" class="relative p-4 border-b border-slate-100 dark:border-slate-800 text-center cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors select-none group">
                        <div @click="sortBy('repair_service_type')" class="flex items-center justify-center gap-1">Service Type <span x-show="sortCol === 'repair_service_type'" x-text="sortAsc ? '↑' : '↓'"></span></div>
                        <div @mousedown="startResize($event, 'repair_service_type')" class="absolute right-0 top-0 bottom-0 w-1 cursor-col-resize hover:bg-indigo-400 group-hover:bg-slate-300 dark:group-hover:bg-slate-700 transition-colors"></div>
                    </th>
                    <th x-show="columns.repair_vendor.visible" :style="'width: ' + columns.repair_vendor.width + 'px'" class="relative p-4 border-b border-slate-100 dark:border-slate-800 text-left cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors select-none group">
                        <div @click="sortBy('repair_vendor')" class="flex items-center gap-1">Vendor <span x-show="sortCol === 'repair_vendor'" x-text="sortAsc ? '↑' : '↓'"></span></div>
                        <div @mousedown="startResize($event, 'repair_vendor')" class="absolute right-0 top-0 bottom-0 w-1 cursor-col-resize hover:bg-indigo-400 group-hover:bg-slate-300 dark:group-hover:bg-slate-700 transition-colors"></div>
                    </th>
                    <th x-show="columns.repair_odometer.visible" :style="'width: ' + columns.repair_odometer.width + 'px'" class="relative p-4 border-b border-slate-100 dark:border-slate-800 text-center cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors select-none group">
                        <div @click="sortBy('repair_odometer')" class="flex items-center justify-center gap-1">Odometer <span x-show="sortCol === 'repair_odometer'" x-text="sortAsc ? '↑' : '↓'"></span></div>
                        <div @mousedown="startResize($event, 'repair_odometer')" class="absolute right-0 top-0 bottom-0 w-1 cursor-col-resize hover:bg-indigo-400 group-hover:bg-slate-300 dark:group-hover:bg-slate-700 transition-colors"></div>
                    </th>
                    <th x-show="columns.repair_est_end.visible" :style="'width: ' + columns.repair_est_end.width + 'px'" class="relative p-4 border-b border-slate-100 dark:border-slate-800 text-center cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors select-none group">
                        <div @click="sortBy('repair_estimation_end')" class="flex items-center justify-center gap-1">Est. End <span x-show="sortCol === 'repair_estimation_end'" x-text="sortAsc ? '↑' : '↓'"></span></div>
                        <div @mousedown="startResize($event, 'repair_est_end')" class="absolute right-0 top-0 bottom-0 w-1 cursor-col-resize hover:bg-indigo-400 group-hover:bg-slate-300 dark:group-hover:bg-slate-700 transition-colors"></div>
                    </th>
                    <th x-show="columns.repair_history.visible" :style="'width: ' + columns.repair_history.width + 'px'" class="relative p-4 border-b border-slate-100 dark:border-slate-800 text-center select-none group">
                        History
                        <div @mousedown="startResize($event, 'repair_history')" class="absolute right-0 top-0 bottom-0 w-1 cursor-col-resize hover:bg-indigo-400 group-hover:bg-slate-300 dark:group-hover:bg-slate-700 transition-colors"></div>
                    </th>
                    <th x-show="columns.traceability.visible" :style="'width: ' + columns.traceability.width + 'px'" class="relative p-4 border-b border-slate-100 dark:border-slate-800 text-center select-none group">
                        Traceability
                        <div @mousedown="startResize($event, 'traceability')" class="absolute right-0 top-0 bottom-0 w-1 cursor-col-resize hover:bg-indigo-400 group-hover:bg-slate-300 dark:group-hover:bg-slate-700 transition-colors"></div>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                <template x-for="item in paginatedItems" :key="item.id">
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition-colors group">
                        <td x-show="columns.lot_number.visible" class="sticky-col p-4 font-mono text-sm font-medium text-indigo-600 dark:text-indigo-400 group-hover:text-indigo-800 dark:group-hover:text-indigo-300 break-words" x-text="item.lot_number"></td>
                        <td x-show="columns.product.visible" class="p-4 break-words">
                            <div class="font-medium text-slate-800 dark:text-slate-200" x-text="item.product"></div>
                            <div class="text-xs text-slate-500 dark:text-slate-400 font-mono" x-show="item.internal_reference && item.internal_reference !== 'No Ref'" x-text="item.internal_reference"></div>
                        </td>
                        <td x-show="columns.year.visible" class="p-4 text-center text-sm font-medium text-slate-600 dark:text-slate-400" x-text="item.year || '-'"></td>
                        <td x-show="columns.location.visible" class="p-4 break-words">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200" x-text="item.location"></span>
                        </td>
                        <td x-show="columns.qty.visible" class="p-4 text-center text-slate-600 dark:text-slate-400 font-bold" x-text="item.on_hand_quantity"></td>
                        <td x-show="columns.type.visible" class="p-4 text-center">
                            <span x-show="item.is_vendor_rent" class="px-2 py-1 rounded text-[10px] font-bold bg-cyan-100 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-400">Vendor</span>
                            <span x-show="!item.is_vendor_rent" class="px-2 py-1 rounded text-[10px] font-bold bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400">Owned</span>
                        </td>
                        <td x-show="columns.current_customer.visible" class="p-4 break-words text-xs text-slate-600 dark:text-slate-400" x-text="item.current_customer || '-'"></td>
                        <td x-show="columns.warehouse.visible" class="p-4 break-words text-xs text-slate-600 dark:text-slate-400" x-text="item.warehouse || '-'"></td>
                        <td x-show="columns.status.visible" class="p-4 text-center">
                            <template x-if="item.rental_id">
                                <div class="flex flex-col items-center">
                                    <span class="px-2 py-1 rounded text-[10px] font-bold bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 mb-1 break-all" x-text="item.rental_id"></span>
                                    <span class="text-[10px] text-slate-400 dark:text-slate-500" x-text="item.rental_type"></span>
                                </div>
                            </template>
                            <template x-if="!item.rental_id && item.in_stock">
                                <span class="px-2 py-1 rounded text-[10px] font-bold bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400">In Stock</span>
                            </template>
                            <template x-if="!item.rental_id && !item.in_stock">
                                <span class="px-2 py-1 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400">-</span>
                            </template>
                        </td>
                        <!-- New Columns Data -->
                        <td x-show="columns.start_date.visible" class="p-4 text-center text-xs text-slate-500 dark:text-slate-400" x-text="formatDate(item.actual_start_rental)"></td>
                        <td x-show="columns.end_date.visible" class="p-4 text-center text-xs text-slate-500 dark:text-slate-400" x-text="formatDate(item.actual_end_rental)"></td>
                        <td x-show="columns.vehicle_role.visible" class="p-4 text-center">
                            <span x-show="item.vehicle_role" class="px-2 py-0.5 rounded text-[10px] bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400" x-text="item.vehicle_role"></span>
                        </td>
                        <td x-show="columns.linked.visible" class="p-4 text-center text-xs font-mono">
                            <template x-if="item.linked_vehicle && item.linked_vehicle !== '-'">
                                <div class="flex flex-wrap gap-1 justify-center">
                                    <template x-for="lot in item.linked_vehicle.split(',')" :key="lot">
                                        <a :href="'/details?category=search&q=' + encodeURIComponent(lot.trim())" 
                                           class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 hover:underline cursor-pointer"
                                           x-text="lot.trim()"></a>
                                    </template>
                                </div>
                            </template>
                            <template x-if="!item.linked_vehicle || item.linked_vehicle === '-'">
                                <span class="text-slate-300 dark:text-slate-600">-</span>
                            </template>
                        </td>
                        <td x-show="columns.in_stock.visible" class="p-4 text-center">
                            <span class="w-2 h-2 inline-block rounded-full" :class="item.in_stock ? 'bg-green-500' : 'bg-red-400 dark:bg-red-500'"></span>
                        </td>
                        <td x-show="columns.purchase_date.visible" class="p-4 text-center text-xs text-slate-500 dark:text-slate-400" x-text="formatDate(item.purchase_date)"></td>
                        <!-- Repair Order Data -->
                        <td x-show="columns.repair_order.visible" class="p-4 text-xs">
                            <span x-show="item.repair_order_name" class="font-mono font-medium text-orange-600 dark:text-orange-400" x-text="item.repair_order_name"></span>
                            <span x-show="!item.repair_order_name" class="text-slate-300 dark:text-slate-600">-</span>
                        </td>
                        <td x-show="columns.repair_jo_date.visible" class="p-4 text-center text-xs text-slate-500 dark:text-slate-400" x-text="formatDate(item.repair_schedule_date)"></td>
                        <td x-show="columns.repair_service_type.visible" class="p-4 text-center">
                            <span x-show="item.repair_service_type" class="px-2 py-0.5 rounded text-[10px] font-bold" :class="item.repair_service_type === 'accident' ? 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400' : 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400'" x-text="item.repair_service_type"></span>
                            <span x-show="!item.repair_service_type" class="text-slate-300 dark:text-slate-600">-</span>
                        </td>
                        <td x-show="columns.repair_vendor.visible" class="p-4 text-xs text-slate-600 dark:text-slate-400 break-words" x-text="item.repair_vendor || '-'"></td>
                        <td x-show="columns.repair_odometer.visible" class="p-4 text-center text-xs text-slate-500 dark:text-slate-400">
                            <span x-show="item.repair_odometer" x-text="Number(item.repair_odometer).toLocaleString() + ' km'"></span>
                            <span x-show="!item.repair_odometer" class="text-slate-300 dark:text-slate-600">-</span>
                        </td>
                        <td x-show="columns.repair_est_end.visible" class="p-4 text-center text-xs text-slate-500 dark:text-slate-400" x-text="formatDate(item.repair_estimation_end)"></td>
                        <td x-show="columns.repair_history.visible" class="p-4 text-center">
                            <button @click="openRepairHistory(item.lot_number)" class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[11px] font-medium bg-orange-50 dark:bg-orange-900/20 text-orange-600 dark:text-orange-400 hover:bg-orange-100 dark:hover:bg-orange-900/40 transition-colors border border-orange-200 dark:border-orange-800">
                                🔧 History
                            </button>
                        </td>
                        <td x-show="columns.traceability.visible" class="p-4 text-center">
                            <button @click="openTraceability(item.lot_number)" class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[11px] font-medium bg-teal-50 dark:bg-teal-900/20 text-teal-600 dark:text-teal-400 hover:bg-teal-100 dark:hover:bg-teal-900/40 transition-colors border border-teal-200 dark:border-teal-800">
                                📋 Trace
                            </button>
                        </td>
                    </tr>
                </template>
                <tr x-show="filteredItems.length === 0">
                    <td :colspan="Object.values(columns).filter(c => c.visible).length" class="p-10 text-center text-slate-400 dark:text-slate-600">
                        No items found matching your filters.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <div class="p-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-950" x-show="filteredItems.length > pageSize">
        <button @click="prevPage" :disabled="currentPage === 1" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-bold hover:bg-slate-50 dark:hover:bg-slate-700 disabled:opacity-50 transition-colors shadow-sm">Previous</button>
        <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Page <span x-text="currentPage"></span> of <span x-text="totalPages"></span></span>
        <button @click="nextPage" :disabled="currentPage === totalPages" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-bold hover:bg-slate-50 dark:hover:bg-slate-700 disabled:opacity-50 transition-colors shadow-sm">Next</button>
    </div>

    <!-- Repair History Modal -->
    <div x-show="repairHistoryModal.open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="repairHistoryModal.open = false">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="repairHistoryModal.open = false"></div>
        <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-3xl max-h-[80vh] flex flex-col">
            <!-- Header -->
            <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                        🔧 Repair History
                    </h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                        Lot: <span class="font-mono font-medium text-indigo-600 dark:text-indigo-400" x-text="repairHistoryModal.lotNumber"></span>
                    </p>
                </div>
                <button @click="repairHistoryModal.open = false" class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <!-- Body -->
            <div class="flex-1 overflow-auto p-5">
                <!-- Loading -->
                <div x-show="repairHistoryModal.loading" class="flex items-center justify-center py-12">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                    <span class="ml-3 text-slate-500 dark:text-slate-400">Fetching from Odoo...</span>
                </div>
                <!-- Error -->
                <div x-show="repairHistoryModal.error" class="text-center py-8">
                    <div class="text-red-500 dark:text-red-400 text-sm" x-text="repairHistoryModal.error"></div>
                </div>
                <!-- Empty -->
                <div x-show="!repairHistoryModal.loading && !repairHistoryModal.error && repairHistoryModal.data.length === 0" class="text-center py-8">
                    <div class="text-slate-400 dark:text-slate-500">No repair history found for this vehicle.</div>
                </div>
                <!-- Data Table -->
                <div x-show="!repairHistoryModal.loading && !repairHistoryModal.error && repairHistoryModal.data.length > 0">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 dark:bg-slate-800 text-xs uppercase text-slate-500 dark:text-slate-400">
                            <tr>
                                <th class="p-3 rounded-tl-lg">Order</th>
                                <th class="p-3">Status</th>
                                <th class="p-3">Date</th>
                                <th class="p-3">Type</th>
                                <th class="p-3">Vendor</th>
                                <th class="p-3">KM</th>
                                <th class="p-3">Est. End</th>
                                <th class="p-3 rounded-tr-lg">Completed</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <template x-for="(r, i) in repairHistoryModal.data" :key="i">
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                    <td class="p-3 font-mono font-medium text-orange-600 dark:text-orange-400 text-xs" x-text="r.name"></td>
                                    <td class="p-3">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold" :class="{
                                            'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400': r.state === 'under_repair',
                                            'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400': r.state === 'done',
                                            'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300': r.state !== 'under_repair' && r.state !== 'done'
                                        }" x-text="r.state"></span>
                                    </td>
                                    <td class="p-3 text-xs text-slate-500 dark:text-slate-400" x-text="r.schedule_date || '-'"></td>
                                    <td class="p-3">
                                        <span x-show="r.service_type" class="px-2 py-0.5 rounded text-[10px] font-bold" :class="r.service_type === 'accident' ? 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400' : 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400'" x-text="r.service_type"></span>
                                        <span x-show="!r.service_type" class="text-slate-300">-</span>
                                    </td>
                                    <td class="p-3 text-xs text-slate-600 dark:text-slate-400" x-text="r.vendor || '-'"></td>
                                    <td class="p-3 text-xs text-slate-500 dark:text-slate-400">
                                        <span x-show="r.km_pickup" x-text="Number(r.km_pickup).toLocaleString()"></span>
                                        <span x-show="!r.km_pickup" class="text-slate-300">-</span>
                                    </td>
                                    <td class="p-3 text-xs text-slate-500 dark:text-slate-400" x-text="r.estimation_end_date || '-'"></td>
                                    <td class="p-3 text-xs text-slate-500 dark:text-slate-400" x-text="r.repair_end_datetime || '-'"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Traceability Report Modal -->
    <div x-show="traceabilityModal.open" x-cloak 
         x-effect="if (traceabilityModal.open) { document.body.style.overflow = 'hidden'; } else { document.body.style.overflow = ''; }"
         class="fixed inset-0 z-50 p-4 flex items-center justify-center bg-black/60 backdrop-blur-sm" @keydown.escape.window="traceabilityModal.open = false">
        
        <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-7xl flex flex-col overflow-hidden" 
             style="max-height: 90vh;">
            
            <!-- Header (always fixed) -->
            <div class="flex-shrink-0 p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                        📋 Traceability Report
                    </h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                        Lot: <span class="font-mono font-medium text-teal-600 dark:text-teal-400" x-text="traceabilityModal.lotNumber"></span>
                    </p>
                </div>
                <button @click="traceabilityModal.open = false" class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <!-- Body (scrollable portion) -->
            <div class="flex-1 overflow-y-auto p-5 custom-scrollbar bg-white dark:bg-slate-900">
                <!-- Loading -->
                <div x-show="traceabilityModal.loading" class="flex items-center justify-center py-12">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-teal-600"></div>
                    <span class="ml-3 text-slate-500 dark:text-slate-400">Fetching from Odoo...</span>
                </div>
                <!-- Error -->
                <div x-show="traceabilityModal.error" class="text-center py-8">
                    <div class="text-red-500 dark:text-red-400 text-sm" x-text="traceabilityModal.error"></div>
                </div>
                <!-- Empty -->
                <div x-show="!traceabilityModal.loading && !traceabilityModal.error && traceabilityModal.data.length === 0" class="text-center py-8">
                    <div class="text-slate-400 dark:text-slate-500">No traceability records found for this lot.</div>
                </div>
                <!-- Data Table -->
                <div x-show="!traceabilityModal.loading && !traceabilityModal.error && traceabilityModal.data.length > 0">
                    <div class="mb-3 text-xs text-slate-400 dark:text-slate-500">
                        <span x-text="traceabilityModal.data.length"></span> move(s) found
                    </div>
                    <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm min-w-[1100px]">
                        <thead class="bg-slate-50 dark:bg-slate-800 text-xs uppercase text-slate-500 dark:text-slate-400">
                            <tr>
                                <th class="p-3 rounded-tl-lg">Reference</th>
                                <th class="p-3">From</th>
                                <th class="p-3">To</th>
                                <th class="p-3">Contact</th>
                                <th class="p-3">Scheduled Date</th>
                                <th class="p-3">Lots</th>
                                <th class="p-3">Effective Date</th>
                                <th class="p-3">Source Document</th>
                                <th class="p-3">SO Reserved Lot</th>
                                <th class="p-3 rounded-tr-lg text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <template x-for="(m, i) in traceabilityModal.data" :key="i">
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                    <td class="p-3 font-mono text-xs">
                                        <span class="font-medium text-teal-600 dark:text-teal-400" x-text="m.reference"></span>
                                    </td>
                                    <td class="p-3 text-xs text-slate-600 dark:text-slate-400" x-text="m.from || '-'"></td>
                                    <td class="p-3 text-xs text-slate-600 dark:text-slate-400" x-text="m.to || '-'"></td>
                                    <td class="p-3 text-xs text-slate-600 dark:text-slate-400" x-text="m.contact || '-'"></td>
                                    <td class="p-3 text-xs text-slate-500 dark:text-slate-400 whitespace-nowrap" x-text="formatTraceDate(m.scheduled_date)"></td>
                                    <td class="p-3">
                                        <span class="font-mono text-xs font-medium text-indigo-600 dark:text-indigo-400" x-text="m.lots"></span>
                                    </td>
                                    <td class="p-3 text-xs text-slate-500 dark:text-slate-400 whitespace-nowrap" x-text="formatTraceDateTime(m.effective_date)"></td>
                                    <td class="p-3 text-xs">
                                        <span class="font-mono font-medium text-slate-700 dark:text-slate-300" x-text="m.source_document || '-'"></span>
                                    </td>
                                    <td class="p-3 text-xs font-mono text-slate-600 dark:text-slate-400" x-text="m.so_reserved_lot || '-'"></td>
                                    <td class="p-3 text-center">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold" :class="{
                                            'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400': m.state === 'done',
                                            'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400': m.state === 'assigned',
                                            'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400': m.state === 'confirmed' || m.state === 'partially_available',
                                            'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300': m.state !== 'done' && m.state !== 'assigned' && m.state !== 'confirmed' && m.state !== 'partially_available'
                                        }" x-text="m.state_label"></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    </div>
                </div>

                <!-- Related Groups (Rental Order Replacements) -->
                <template x-for="(group, gi) in traceabilityModal.related_groups" :key="gi">
                    <div class="mt-6">
                        <!-- Replacement Header -->
                        <div class="px-4 py-3 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 mb-3">
                            <div class="flex items-center gap-2 text-sm font-medium text-amber-800 dark:text-amber-300">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                                <span x-text="'Rental Order : ' + group.rental_order + ' Original ' + group.original_lot + ' replaced by ' + group.replacement_lot + ' at ' + group.replacement_date_formatted"></span>
                            </div>
                        </div>
                        <!-- Replacement Moves Table -->
                        <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm min-w-[1100px]">
                            <thead class="bg-slate-50 dark:bg-slate-800 text-xs uppercase text-slate-500 dark:text-slate-400">
                                <tr>
                                    <th class="p-3 rounded-tl-lg">Reference</th>
                                    <th class="p-3">From</th>
                                    <th class="p-3">To</th>
                                    <th class="p-3">Contact</th>
                                    <th class="p-3">Scheduled Date</th>
                                    <th class="p-3">Lots</th>
                                    <th class="p-3">Effective Date</th>
                                    <th class="p-3">Source Document</th>
                                    <th class="p-3">SO Reserved Lot</th>
                                    <th class="p-3 rounded-tr-lg text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <template x-for="(m, mi) in group.moves" :key="mi">
                                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                        <td class="p-3 font-mono text-xs">
                                            <span class="font-medium text-teal-600 dark:text-teal-400" x-text="m.reference"></span>
                                        </td>
                                        <td class="p-3 text-xs text-slate-600 dark:text-slate-400" x-text="m.from || '-'"></td>
                                        <td class="p-3 text-xs text-slate-600 dark:text-slate-400" x-text="m.to || '-'"></td>
                                        <td class="p-3 text-xs text-slate-600 dark:text-slate-400" x-text="m.contact || '-'"></td>
                                        <td class="p-3 text-xs text-slate-500 dark:text-slate-400 whitespace-nowrap" x-text="formatTraceDate(m.scheduled_date)"></td>
                                        <td class="p-3">
                                            <span class="font-mono text-xs font-medium text-indigo-600 dark:text-indigo-400" x-text="m.lots"></span>
                                        </td>
                                        <td class="p-3 text-xs text-slate-500 dark:text-slate-400 whitespace-nowrap" x-text="formatTraceDateTime(m.effective_date)"></td>
                                        <td class="p-3 text-xs">
                                            <span class="font-mono font-medium text-slate-700 dark:text-slate-300" x-text="m.source_document || '-'"></span>
                                        </td>
                                        <td class="p-3 text-xs font-mono text-slate-600 dark:text-slate-400" x-text="m.so_reserved_lot || '-'"></td>
                                        <td class="p-3 text-center">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold" :class="{
                                                'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400': m.state === 'done',
                                                'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400': m.state === 'assigned',
                                                'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400': m.state === 'confirmed' || m.state === 'partially_available',
                                                'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300': m.state !== 'done' && m.state !== 'assigned' && m.state !== 'confirmed' && m.state !== 'partially_available'
                                            }" x-text="m.state_label"></span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function itemTable() {
        return {
            items: @json($items),
            search: '',
            filters: {
                location: '',
                role: '',
                type: '',
                year: ''
            },
            sortCol: 'product',
            sortAsc: true,
            currentPage: 1,
            pageSize: 20,
            
            // Column State
            columns: {
                lot_number: { label: 'Lot Number', visible: true, width: 150 },
                product: { label: 'Product', visible: true, width: 250 },
                year: { label: 'Year', visible: true, width: 70 },
                location: { label: 'Location', visible: true, width: 140 },
                qty: { label: 'Qty', visible: true, width: 60 },
                type: { label: 'Type', visible: true, width: 80 },
                current_customer: { label: 'Customer', visible: true, width: 150 },
                warehouse: { label: 'Warehouse', visible: true, width: 120 },
                status: { label: 'Rental Status', visible: true, width: 140 },
                start_date: { label: 'Start', visible: false, width: 100 },
                end_date: { label: 'End', visible: false, width: 100 },
                vehicle_role: { label: 'Role', visible: false, width: 80 },
                linked: { label: 'Linked', visible: false, width: 100 },
                in_stock: { label: 'Stock', visible: false, width: 60 },
                purchase_date: { label: 'Purc. Date', visible: false, width: 100 },
                repair_order: { label: 'Repair Order', visible: false, width: 160 },
                repair_jo_date: { label: 'JO Date', visible: false, width: 100 },
                repair_service_type: { label: 'Service Type', visible: false, width: 100 },
                repair_vendor: { label: 'Vendor', visible: false, width: 150 },
                repair_odometer: { label: 'Odometer', visible: false, width: 100 },
                repair_est_end: { label: 'Est. End', visible: false, width: 100 },
                repair_history: { label: 'History', visible: true, width: 80 },
                traceability: { label: 'Traceability', visible: true, width: 90 }
            },
            
            // Repair History Modal State
            repairHistoryModal: {
                open: false,
                lotNumber: '',
                loading: false,
                error: null,
                data: []
            },
            
            // Traceability Report Modal State
            traceabilityModal: {
                open: false,
                lotNumber: '',
                loading: false,
                error: null,
                data: [],
                related_groups: []
            },
            
            get tableWidth() {
                return Object.values(this.columns).reduce((sum, col) => col.visible ? sum + (col.width || 100) : sum, 0);
            },
            
            resizingCol: null,
            startX: 0,
            startWidth: 0,

            init() {
                // Auto-show repair columns for service categories
                const category = '{{ $category ?? '' }}';
                const serviceCategories = ['external_service', 'service_external', 'internal_service', 'service_internal', 'insurance', 'service_insurance', 'in_service'];
                const isServiceView = serviceCategories.includes(category);
                
                // Load saved columns
                let saved = localStorage.getItem('sdp_table_columns');
                if (saved) {
                    try {
                        let parsed = JSON.parse(saved);
                        // Merge saved config with default structure (handles new columns)
                        for (let key in this.columns) {
                            if (parsed[key]) {
                                this.columns[key].visible = parsed[key].visible;
                                this.columns[key].width = parsed[key].width;
                            }
                        }
                    } catch (e) {
                        console.error('Failed to load table prefs', e);
                    }
                }
                
                // Override: Force repair columns visible on service views
                if (isServiceView) {
                    this.columns.repair_order.visible = true;
                    this.columns.repair_jo_date.visible = true;
                    this.columns.repair_service_type.visible = true;
                    this.columns.repair_vendor.visible = true;
                    this.columns.repair_odometer.visible = true;
                    this.columns.repair_est_end.visible = true;
                    this.columns.repair_history.visible = true;
                }

                // Save columns on change
                this.$watch('columns', (val) => {
                    localStorage.setItem('sdp_table_columns', JSON.stringify(val));
                }, { deep: true });

                // Resize Listeners
                window.addEventListener('mousemove', (e) => {
                    if (this.resizingCol) {
                        const diff = e.clientX - this.startX;
                        // Min width 50
                        this.columns[this.resizingCol].width = Math.max(50, this.startWidth + diff);
                    }
                });
                window.addEventListener('mouseup', () => {
                    this.resizingCol = null;
                    document.body.style.cursor = 'default';
                });
            },

            formatDate(dateStr) {
                if (!dateStr) return '-';
                const bulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                const d = new Date(dateStr);
                if (isNaN(d.getTime())) return dateStr.substring(0, 10);
                return d.getDate() + ' ' + bulan[d.getMonth()] + ' ' + d.getFullYear();
            },

            formatDateTime(dateStr) {
                if (!dateStr) return '-';
                const bulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                const d = new Date(dateStr);
                if (isNaN(d.getTime())) return dateStr.substring(0, 19);
                const hh = String(d.getHours()).padStart(2, '0');
                const mm = String(d.getMinutes()).padStart(2, '0');
                return d.getDate() + ' ' + bulan[d.getMonth()] + ' ' + d.getFullYear() + ' ' + hh + ':' + mm;
            },

            formatTraceDate(dateStr) {
                if (!dateStr || dateStr === false) return '-';
                return this.formatDate(dateStr);
            },

            formatTraceDateTime(dateStr) {
                if (!dateStr || dateStr === false) return '-';
                return this.formatDateTime(dateStr);
            },

            startResize(e, colId) {
                this.resizingCol = colId;
                this.startX = e.clientX;
                this.startWidth = this.columns[colId].width;
                document.body.style.cursor = 'col-resize';
                e.stopPropagation(); // Prevent sorting
            },
            
            get hasActiveFilters() {
                return this.filters.location || this.filters.role || this.filters.type || this.filters.year;
            },
            
            resetFilters() {
                this.filters.location = '';
                this.filters.role = '';
                this.filters.type = '';
                this.filters.year = '';
                this.search = '';
            },

            get filteredItems() {
                let term = this.search.toLowerCase();
                
                let result = this.items.filter(item => {
                    // Global Search
                    let matchesSearch = true;
                    if (term) {
                        matchesSearch = (item.lot_number && item.lot_number.toLowerCase().includes(term)) ||
                                        (item.product && item.product.toLowerCase().includes(term)) ||
                                        (item.location && item.location.toLowerCase().includes(term)) ||
                                        (item.rental_id && item.rental_id.toLowerCase().includes(term)) ||
                                        (item.internal_reference && item.internal_reference.toLowerCase().includes(term)) ||
                                        (item.year && String(item.year).includes(term));
                    }
                    
                    // Specific Filters
                    let matchesLoc = !this.filters.location || (item.location === this.filters.location);
                    let matchesRole = !this.filters.role || (item.vehicle_role === this.filters.role);
                    let matchesType = !this.filters.type || (item.rental_type === this.filters.type);
                    let matchesYear = !this.filters.year || (String(item.year) === this.filters.year);

                    return matchesSearch && matchesLoc && matchesRole && matchesType && matchesYear;
                });
                
                // Sorting
                result.sort((a, b) => {
                    let valA = a[this.sortCol] || '';
                    let valB = b[this.sortCol] || '';
                    if (typeof valA === 'string') valA = valA.toLowerCase();
                    if (typeof valB === 'string') valB = valB.toLowerCase();
                    
                    if (valA < valB) return this.sortAsc ? -1 : 1;
                    if (valA > valB) return this.sortAsc ? 1 : -1;
                    return 0;
                });
                
                return result;
            },
            
            get totalPages() {
                return Math.ceil(this.filteredItems.length / this.pageSize);
            },
            
            get paginatedItems() {
                let start = (this.currentPage - 1) * this.pageSize;
                return this.filteredItems.slice(start, start + this.pageSize);
            },
            
            nextPage() {
                if (this.currentPage < this.totalPages) this.currentPage++;
            },
            
            prevPage() {
                if (this.currentPage > 1) this.currentPage--;
            },
            
            sortBy(col) {
                if (this.sortCol === col) {
                    this.sortAsc = !this.sortAsc;
                } else {
                    this.sortCol = col;
                    this.sortAsc = true;
                }
            },

            async openRepairHistory(lotNumber) {
                this.repairHistoryModal.open = true;
                this.repairHistoryModal.lotNumber = lotNumber;
                this.repairHistoryModal.loading = true;
                this.repairHistoryModal.error = null;
                this.repairHistoryModal.data = [];
                
                try {
                    const response = await fetch(`/api/repair-history/${encodeURIComponent(lotNumber)}`);
                    const result = await response.json();
                    
                    if (result.success) {
                        this.repairHistoryModal.data = result.data;
                    } else {
                        this.repairHistoryModal.error = result.message || 'Failed to fetch repair history';
                    }
                } catch (e) {
                    this.repairHistoryModal.error = 'Network error: ' + e.message;
                } finally {
                    this.repairHistoryModal.loading = false;
                }
            },

            async openTraceability(lotNumber) {
                this.traceabilityModal.open = true;
                this.traceabilityModal.lotNumber = lotNumber;
                this.traceabilityModal.loading = true;
                this.traceabilityModal.error = null;
                this.traceabilityModal.data = [];
                this.traceabilityModal.related_groups = [];
                
                try {
                    const response = await fetch(`/api/traceability/${encodeURIComponent(lotNumber)}`);
                    const result = await response.json();
                    
                    if (result.success) {
                        this.traceabilityModal.data = result.data;
                        this.traceabilityModal.related_groups = result.related_groups || [];
                    } else {
                        this.traceabilityModal.error = result.message || 'Failed to fetch traceability report';
                    }
                } catch (e) {
                    this.traceabilityModal.error = 'Network error: ' + e.message;
                } finally {
                    this.traceabilityModal.loading = false;
                }
            }
        }
    }
</script>
@endsection
