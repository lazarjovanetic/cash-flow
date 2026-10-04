<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/prijava', [AuthController::class, 'show'])->name('login');
    Route::post('/prijava', [AuthController::class, 'login'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/odjava', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', [DashboardController::class, 'index'])->name('home');
    Route::post('/demo', [DashboardController::class, 'resetDemo'])->name('demo.reset');

    Route::post('/dobavljaci', [SupplierController::class, 'store'])->name('suppliers.store');
    Route::get('/dobavljaci/{supplier}', [SupplierController::class, 'show'])->name('suppliers.show');
    Route::delete('/dobavljaci/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');

    Route::post('/dobavljaci/{supplier}/kontakti', [ContactController::class, 'store'])->name('contacts.store');
    Route::delete('/kontakti/{contact}', [ContactController::class, 'destroy'])->name('contacts.destroy');

    Route::post('/fakture', [InvoiceController::class, 'store'])->name('invoices.store');
    Route::delete('/fakture/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');

    Route::post('/fakture/{invoice}/uplate', [PaymentController::class, 'store'])->name('payments.store');
    Route::delete('/uplate/{payment}', [PaymentController::class, 'destroy'])->name('payments.destroy');
});
