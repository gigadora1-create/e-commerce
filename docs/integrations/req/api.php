<?php

use App\Http\Controllers\Api\ReqSupplyCaseController;
use Illuminate\Support\Facades\Route;

Route::post('/v1/proveeduria/cases', [ReqSupplyCaseController::class, 'store'])
    ->middleware('throttle:30,1');
