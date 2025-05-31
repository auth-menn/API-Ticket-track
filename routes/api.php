<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TicketController;

route::post('/login', [AuthController::class, 'login']);
route::post('/register', [AuthController::class, 'register']);

route::middleware('auth:sanctum')->group(function () {
    route::get('/me', [AuthController::class, 'me']);
    route::post('/logout', [AuthController::class, 'logout']);
    
    Route::get('/ticket', [TicketController::class, 'index']);
    Route::post('/ticket', [TicketController::class, 'store']);

});
