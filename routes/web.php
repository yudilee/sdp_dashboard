<?php

use Illuminate\Support\Facades\Route;


use App\Http\Controllers\DashboardController;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
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

