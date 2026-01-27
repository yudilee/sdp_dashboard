@extends('layouts.app')

@section('title', 'Help & Guide - SDP Stock')

@section('content')
<div class="max-w-5xl mx-auto space-y-10 pb-20">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 border-b border-slate-200 dark:border-slate-800 pb-6">
        <div>
            <h1 class="text-3xl font-bold text-slate-800 dark:text-slate-100 italic">Help & Documentation</h1>
            <p class="text-slate-500 dark:text-slate-400 mt-2">Complete guide to dashboard metrics, categories, and features.</p>
        </div>
        <div class="flex gap-2">
            <a href="#dashboard" class="px-3 py-1.5 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 text-sm font-medium hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition-colors">Categories</a>
            <a href="#features" class="px-3 py-1.5 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 text-sm font-medium hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition-colors">Features</a>
        </div>
    </div>

    <!-- 1. Dashboard Categories -->
    <section id="dashboard" class="scroll-mt-24">
        <h2 class="text-2xl font-bold text-slate-800 dark:text-slate-100 mb-6 flex items-center gap-3">
            <span class="p-2 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 012 2h2a2 2 0 012-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
            </span>
            Dashboard Metrics Explained
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- In Stock Card -->
            <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm">
                <h3 class="text-lg font-bold text-emerald-600 dark:text-emerald-400 mb-3 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span> In Stock
                </h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">Items physically available or accounted for in the warehouse.</p>
                <ul class="space-y-3">
                    <li class="bg-slate-50 dark:bg-slate-800 p-3 rounded-lg border border-slate-100 dark:border-slate-700">
                        <span class="block text-sm font-bold text-slate-700 dark:text-slate-200">Pure Stock</span>
                        <span class="text-xs text-slate-500 dark:text-slate-400">Items available with NO rental associations. Ready for immediate use.</span>
                    </li>
                    <li class="bg-slate-50 dark:bg-slate-800 p-3 rounded-lg border border-slate-100 dark:border-slate-700">
                        <span class="block text-sm font-bold text-slate-700 dark:text-slate-200">Reserve</span>
                        <span class="text-xs text-slate-500 dark:text-slate-400">Items assigned to future rentals or specifically held back.</span>
                    </li>
                    <li class="bg-slate-50 dark:bg-slate-800 p-3 rounded-lg border border-slate-100 dark:border-slate-700">
                        <span class="block text-sm font-bold text-slate-700 dark:text-slate-200">Orig (Repl) / Orig (No Repl)</span>
                        <span class="text-xs text-slate-500 dark:text-slate-400">Original vehicles that are currently in stock, either with or without a replacement vehicle active elsewhere.</span>
                    </li>
                </ul>
            </div>

            <!-- Rented Card -->
            <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm">
                <h3 class="text-lg font-bold text-amber-600 dark:text-amber-400 mb-3 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span> Rented / With Customer
                </h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">Items currently out with clients or partners.</p>
                <ul class="space-y-3">
                    <li class="bg-slate-50 dark:bg-slate-800 p-3 rounded-lg border border-slate-100 dark:border-slate-700">
                        <span class="block text-sm font-bold text-slate-700 dark:text-slate-200">Original in Customer</span>
                        <span class="text-xs text-slate-500 dark:text-slate-400">The primary vehicle assigned to a customer's contract.</span>
                    </li>
                    <li class="bg-slate-50 dark:bg-slate-800 p-3 rounded-lg border border-slate-100 dark:border-slate-700">
                        <span class="block text-sm font-bold text-slate-700 dark:text-slate-200">Vendor Rent</span>
                        <span class="text-xs text-slate-500 dark:text-slate-400">Vehicles we are renting FROM a vendor to fulfill a customer request.</span>
                    </li>
                    <li class="bg-slate-50 dark:bg-slate-800 p-3 rounded-lg border border-slate-100 dark:border-slate-700">
                        <span class="block text-sm font-bold text-slate-700 dark:text-slate-200">Replacement (Service/RBO)</span>
                        <span class="text-xs text-slate-500 dark:text-slate-400">Temporary vehicles provided to customers while their main vehicle is in service.</span>
                    </li>
                </ul>
            </div>

            <!-- In Service Card -->
            <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm md:col-span-2">
                <h3 class="text-lg font-bold text-red-600 dark:text-red-400 mb-3 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-red-500"></span> In Service
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div class="bg-slate-50 dark:bg-slate-800 p-3 rounded-lg border border-slate-100 dark:border-slate-700">
                        <span class="block font-bold text-red-600 dark:text-red-400 mb-1">External Service</span>
                        <span class="text-slate-500 dark:text-slate-400 text-xs">Vehicles at 3rd party workshops for repair.</span>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-800 p-3 rounded-lg border border-slate-100 dark:border-slate-700">
                        <span class="block font-bold text-blue-600 dark:text-blue-400 mb-1">Internal Service</span>
                        <span class="text-slate-500 dark:text-slate-400 text-xs">Vehicles being repaired or maintained by our own team.</span>
                    </div>
                     <div class="bg-slate-50 dark:bg-slate-800 p-3 rounded-lg border border-slate-100 dark:border-slate-700">
                        <span class="block font-bold text-purple-600 dark:text-purple-400 mb-1">Insurance</span>
                        <span class="text-slate-500 dark:text-slate-400 text-xs">Vehicles involved in accidents/claims processing.</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 2. Application Features -->
    <section id="features" class="scroll-mt-24 pt-10 border-t border-slate-100 dark:border-slate-800">
        <h2 class="text-2xl font-bold text-slate-800 dark:text-slate-100 mb-6 flex items-center gap-3">
             <span class="p-2 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
            </span>
            Application Features
        </h2>

        <div class="space-y-8">
            <!-- Total Stock & Query Builder -->
            <div class="flex flex-col md:flex-row gap-6">
                <div class="md:w-1/3">
                    <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100 mb-2">Total Stock & Filtering</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Powerful table with advanced query capabilities.</p>
                </div>
                <div class="md:w-2/3 bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-100 dark:border-slate-800">
                    <ul class="list-disc pl-5 text-sm space-y-2 text-slate-600 dark:text-slate-300">
                        <li><strong>Query Builder</strong>: Create complex filters using AND/OR logic. Example: "Product contains X AND Location equals Y".</li>
                        <li><strong>Save Views</strong>: Save your frequently used filters as "Views" to quickly reload them later. Accessible via the "Views" dropdown.</li>
                        <li><strong>Column Control</strong>: Use the "Cols" button to show/hide specific columns to declutter your view.</li>
                        <li><strong>Live Search</strong>: The top search bar uses fuzzy matching to find items across Lot Number, Product Name, or Location instantly.</li>
                    </ul>
                </div>
            </div>

            <!-- Rental Pairs -->
            <div class="flex flex-col md:flex-row gap-6">
                <div class="md:w-1/3">
                    <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100 mb-2">Rental Pairs</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Visualization of Main Vehicle vs Replacement relationships.</p>
                </div>
                <div class="md:w-2/3 bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-100 dark:border-slate-800">
                    <ul class="list-disc pl-5 text-sm space-y-2 text-slate-600 dark:text-slate-300">
                         <li><strong>Pairing Logic</strong>: Automatically groups "Original" vehicles with their "Replacement" counterparts based on Rental IDs.</li>
                         <li><strong>Status Indicators</strong>: Color-coded borders indicate health (Green = Standard, Amber = Replacement Active).</li>
                         <li><strong>Search</strong>: Filter pairs by Rental ID or Customer Name.</li>
                    </ul>
                </div>
            </div>

            <!-- Data Management -->
            <div class="flex flex-col md:flex-row gap-6">
                <div class="md:w-1/3">
                    <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100 mb-2">Data Management</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Importing, exporting, and system tools.</p>
                </div>
                <div class="md:w-2/3 bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-100 dark:border-slate-800">
                    <ul class="list-disc pl-5 text-sm space-y-2 text-slate-600 dark:text-slate-300">
                         <li><strong>Import Data</strong>: Upload Excel (.xlsx) snapshots to update the entire dashboard. The system automatically calculates histories and trends.</li>
                         <li><strong>Export</strong>: Download any table view as CSV, Excel, or PDF. Matches your current filters.</li>
                         <li><strong>Dark Mode</strong>: Toggle between Light and Dark themes using the sun/moon icon in the header. Persists across sessions.</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

</div>
@endsection
