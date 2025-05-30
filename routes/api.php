<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

route::post('/login', [AuthController::class, 'login']);
route::post('/register', [AuthController::class, 'register']);

route::middleware('auth:sanctum')->group(function () {
    route::get('/me', [AuthController::class, 'me']);
    route::post('/logout', [AuthController::class, 'logout']);
    
});
