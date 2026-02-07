<?php

use Illuminate\Support\Facades\Route;


use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportController;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Import Data Routes
Route::get('/import', [ImportController::class, 'index'])->name('import');
Route::post('/import/excel', [ImportController::class, 'uploadExcel'])->name('import.excel');
Route::post('/import/odoo/config', [ImportController::class, 'saveOdooConfig'])->name('import.odoo.config');
Route::post('/import/odoo/test', [ImportController::class, 'testOdooConnection'])->name('import.odoo.test');
Route::post('/import/odoo/sync', [ImportController::class, 'syncOdoo'])->name('import.odoo.sync');
Route::get('/import/odoo/schedule', [ImportController::class, 'getSchedule'])->name('import.odoo.schedule.get');
Route::post('/import/odoo/schedule', [ImportController::class, 'saveSchedule'])->name('import.odoo.schedule.save');
Route::get('/import/history', [ImportController::class, 'history'])->name('import.history');
Route::get('/details', [DashboardController::class, 'details'])->name('details');
Route::get('/export', [DashboardController::class, 'export'])->name('export');
Route::get('/print', [DashboardController::class, 'print'])->name('print');
Route::get('/rental-pairs', [DashboardController::class, 'rentalPairs'])->name('rental.pairs');
Route::get('/summary', [DashboardController::class, 'summary'])->name('summary');
Route::get('/help', function () { return view('help'); })->name('help');
Route::post('/generate', [DashboardController::class, 'upload'])->name('summary.generate');

Route::get('/total-stock', [DashboardController::class, 'totalStock'])->name('total.stock');
Route::post('/total-stock/filter', [DashboardController::class, 'filterTotalStock'])->name('total.stock.filter');
Route::post('/total-stock/export', [DashboardController::class, 'exportTotalStock'])->name('total.stock.export');

