<?php

use App\Http\Controllers\CalculatorController;
use App\Http\Controllers\LanguageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CalculatorController::class, 'index'])->name('calculator.index');

Route::post('/calculate', [CalculatorController::class, 'calculate'])->name('calculator.calculate');

Route::get('/about', [CalculatorController::class, 'about'])->name('calculator.about');

Route::get('/language', [LanguageController::class, 'update'])->name('language.update');
