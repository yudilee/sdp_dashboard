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
    background: rgb(248 250 252 / 0.5);
}

.dark .frozen-table tbody tr:hover td.sticky-col {
    background: rgb(30 41 59 / 0.5);
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
            
            <button @click="resetFilters()" x-show="hasActiveFilters" class="px-3 py-1.5 rounded-lg text-sm text-red-500 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 font-medium transition-colors">
                Reset
            </button>
        </div>
    </div>

    <!-- Table -->
    <div class="frozen-table-container custom-scrollbar">
        <table class="frozen-table w-full text-left" style="table-layout: fixed; min-width: 1200px;">
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
                    <th x-show="columns.location.visible" :style="'width: ' + columns.location.width + 'px'" class="relative p-4 border-b border-slate-100 dark:border-slate-800 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors select-none group">
                        <div @click="sortBy('location')" class="flex items-center gap-1">Location <span x-show="sortCol === 'location'" x-text="sortAsc ? '↑' : '↓'"></span></div>
                         <div @mousedown="startResize($event, 'location')" class="absolute right-0 top-0 bottom-0 w-1 cursor-col-resize hover:bg-indigo-400 group-hover:bg-slate-300 dark:group-hover:bg-slate-700 transition-colors"></div>
                    </th>
                    <th x-show="columns.qty.visible" :style="'width: ' + columns.qty.width + 'px'" class="relative p-4 border-b border-slate-100 dark:border-slate-800 text-center select-none group">
                        Qty
                         <div @mousedown="startResize($event, 'qty')" class="absolute right-0 top-0 bottom-0 w-1 cursor-col-resize hover:bg-indigo-400 group-hover:bg-slate-300 dark:group-hover:bg-slate-700 transition-colors"></div>
                    </th>
                    <th x-show="columns.type.visible" :style="'width: ' + columns.type.width + 'px'" class="relative p-4 border-b border-slate-100 dark:border-slate-800 text-center select-none group">
                        Type
                         <div @mousedown="startResize($event, 'type')" class="absolute right-0 top-0 bottom-0 w-1 cursor-col-resize hover:bg-indigo-400 group-hover:bg-slate-300 dark:group-hover:bg-slate-700 transition-colors"></div>
                    </th>
                    <th x-show="columns.current_customer.visible" :style="'width: ' + columns.current_customer.width + 'px'" class="relative p-4 border-b border-slate-100 dark:border-slate-800 text-left select-none group">
                        Customer
                         <div @mousedown="startResize($event, 'current_customer')" class="absolute right-0 top-0 bottom-0 w-1 cursor-col-resize hover:bg-indigo-400 group-hover:bg-slate-300 dark:group-hover:bg-slate-700 transition-colors"></div>
                    </th>
                    <th x-show="columns.warehouse.visible" :style="'width: ' + columns.warehouse.width + 'px'" class="relative p-4 border-b border-slate-100 dark:border-slate-800 text-left select-none group">
                        Warehouse
                         <div @mousedown="startResize($event, 'warehouse')" class="absolute right-0 top-0 bottom-0 w-1 cursor-col-resize hover:bg-indigo-400 group-hover:bg-slate-300 dark:group-hover:bg-slate-700 transition-colors"></div>
                    </th>
                    <th x-show="columns.status.visible" :style="'width: ' + columns.status.width + 'px'" class="relative p-4 border-b border-slate-100 dark:border-slate-800 text-center select-none group">
                        Rental Status
                         <div @mousedown="startResize($event, 'status')" class="absolute right-0 top-0 bottom-0 w-1 cursor-col-resize hover:bg-indigo-400 group-hover:bg-slate-300 dark:group-hover:bg-slate-700 transition-colors"></div>
                    </th>
                    <!-- New Columns -->
                    <th x-show="columns.start_date.visible" :style="'width: ' + columns.start_date.width + 'px'" class="relative p-4 border-b border-slate-100 dark:border-slate-800 text-center select-none group">
                        Start
                        <div @mousedown="startResize($event, 'start_date')" class="absolute right-0 top-0 bottom-0 w-1 cursor-col-resize hover:bg-indigo-400 group-hover:bg-slate-300 dark:group-hover:bg-slate-700 transition-colors"></div>
                    </th>
                    <th x-show="columns.end_date.visible" :style="'width: ' + columns.end_date.width + 'px'" class="relative p-4 border-b border-slate-100 dark:border-slate-800 text-center select-none group">
                        End
                        <div @mousedown="startResize($event, 'end_date')" class="absolute right-0 top-0 bottom-0 w-1 cursor-col-resize hover:bg-indigo-400 group-hover:bg-slate-300 dark:group-hover:bg-slate-700 transition-colors"></div>
                    </th>
                    <th x-show="columns.vehicle_role.visible" :style="'width: ' + columns.vehicle_role.width + 'px'" class="relative p-4 border-b border-slate-100 dark:border-slate-800 text-center select-none group">
                        Role
                        <div @mousedown="startResize($event, 'vehicle_role')" class="absolute right-0 top-0 bottom-0 w-1 cursor-col-resize hover:bg-indigo-400 group-hover:bg-slate-300 dark:group-hover:bg-slate-700 transition-colors"></div>
                    </th>
                    <th x-show="columns.linked.visible" :style="'width: ' + columns.linked.width + 'px'" class="relative p-4 border-b border-slate-100 dark:border-slate-800 text-center select-none group">
                        Linked
                        <div @mousedown="startResize($event, 'linked')" class="absolute right-0 top-0 bottom-0 w-1 cursor-col-resize hover:bg-indigo-400 group-hover:bg-slate-300 dark:group-hover:bg-slate-700 transition-colors"></div>
                    </th>
                    <th x-show="columns.in_stock.visible" :style="'width: ' + columns.in_stock.width + 'px'" class="relative p-4 border-b border-slate-100 dark:border-slate-800 text-center select-none group">
                        Stock
                        <div @mousedown="startResize($event, 'in_stock')" class="absolute right-0 top-0 bottom-0 w-1 cursor-col-resize hover:bg-indigo-400 group-hover:bg-slate-300 dark:group-hover:bg-slate-700 transition-colors"></div>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                <template x-for="item in paginatedItems" :key="item.id">
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition-colors group">
                        <td x-show="columns.lot_number.visible" class="sticky-col p-4 font-mono text-sm font-medium text-indigo-600 dark:text-indigo-400 group-hover:text-indigo-800 dark:group-hover:text-indigo-300 break-words" x-text="item.lot_number"></td>
                        <td x-show="columns.product.visible" class="p-4 break-words">
                            <div class="font-medium text-slate-800 dark:text-slate-200" x-text="item.product"></div>
                            <div class="text-xs text-slate-400 dark:text-slate-500" x-text="item.internal_reference || 'No Ref'"></div>
                        </td>
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
                        <td x-show="columns.linked.visible" class="p-4 text-center text-xs text-slate-400 dark:text-slate-500 font-mono" x-text="item.linked_vehicle || '-'"></td>
                        <td x-show="columns.in_stock.visible" class="p-4 text-center">
                            <span class="w-2 h-2 inline-block rounded-full" :class="item.in_stock ? 'bg-green-500' : 'bg-red-400 dark:bg-red-500'"></span>
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
                type: ''
            },
            sortCol: 'product',
            sortAsc: true,
            currentPage: 1,
            pageSize: 20,
            
            // Column State
            columns: {
                lot_number: { label: 'Lot Number', visible: true, width: 150 },
                product: { label: 'Product', visible: true, width: 250 },
                location: { label: 'Location', visible: true, width: 140 },
                qty: { label: 'Qty', visible: true, width: 60 },
                type: { label: 'Type', visible: true, width: 80 },
                current_customer: { label: 'Customer', visible: true, width: 150 }, // New
                warehouse: { label: 'Warehouse', visible: true, width: 120 }, // New
                status: { label: 'Rental Status', visible: true, width: 140 },
                start_date: { label: 'Start', visible: false, width: 100 },
                end_date: { label: 'End', visible: false, width: 100 },
                vehicle_role: { label: 'Role', visible: false, width: 80 },
                linked: { label: 'Linked', visible: false, width: 100 },
                in_stock: { label: 'Stock', visible: false, width: 60 }
            },
            
            resizingCol: null,
            startX: 0,
            startWidth: 0,

            init() {
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
                    // Trigger save explicitly after resize ends (mouse up) to ensure final width is saved? 
                    // $watch should catch it as width updates in real-time.
                });
            },

            formatDate(dateStr) {
                if (!dateStr) return '-';
                // Simple date format
                return dateStr.substring(0, 10);
            },

            startResize(e, colId) {
                this.resizingCol = colId;
                this.startX = e.clientX;
                this.startWidth = this.columns[colId].width;
                document.body.style.cursor = 'col-resize';
                e.stopPropagation(); // Prevent sorting
            },
            
            get hasActiveFilters() {
                return this.filters.location || this.filters.role || this.filters.type;
            },
            
            resetFilters() {
                this.filters.location = '';
                this.filters.role = '';
                this.filters.type = '';
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
                                        (item.rental_id && item.rental_id.toLowerCase().includes(term));
                    }
                    
                    // Specific Filters
                    let matchesLoc = !this.filters.location || (item.location === this.filters.location);
                    let matchesRole = !this.filters.role || (item.vehicle_role === this.filters.role);
                    let matchesType = !this.filters.type || (item.rental_type === this.filters.type);

                    return matchesSearch && matchesLoc && matchesRole && matchesType;
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
            }
        }
    }
</script>
@endsection
