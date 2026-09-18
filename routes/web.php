<?php

use App\Http\Controllers\CalculatorController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CalculatorController::class, 'index'])->name('calculator.index');

Route::post('/calculate', [CalculatorController::class, 'calculate'])->name('calculator.calculate');

Route::get('/about', [CalculatorController::class, 'about'])->name('calculator.about');
