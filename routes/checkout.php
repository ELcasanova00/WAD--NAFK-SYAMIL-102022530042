<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CheckoutController;

Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::put('/checkout/{id}', [CheckoutController::class, 'update'])->name('checkout.update');
Route::delete('/checkout/{id}', [CheckoutController::class, 'destroy'])->name('checkout.destroy');
