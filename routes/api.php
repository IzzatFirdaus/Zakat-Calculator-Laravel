<?php

use App\Http\Controllers\CalculatorController;
use Illuminate\Support\Facades\Route;

Route::post('/v1/calculate', [CalculatorController::class, 'apiCalculate'])->name('api.v1.calculate');
