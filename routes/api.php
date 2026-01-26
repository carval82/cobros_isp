<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CobradorAppController;

// Rutas públicas
Route::post('/cobrador/login', [CobradorAppController::class, 'login']);

// Rutas protegidas (requieren token)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/cobrador/proyectos', [CobradorAppController::class, 'proyectos']);
    Route::get('/cobrador/sync', [CobradorAppController::class, 'sync']);
    Route::get('/cobrador/sync/{proyecto_id}', [CobradorAppController::class, 'syncProyecto']);
    Route::post('/cobrador/pago', [CobradorAppController::class, 'registrarPago']);
    Route::post('/cobrador/cliente', [CobradorAppController::class, 'registrarCliente']);
    Route::post('/cobrador/cerrar-cobro', [CobradorAppController::class, 'cerrarCobro']);
    Route::get('/cobrador/resumen-dia', [CobradorAppController::class, 'resumenDia']);
    
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
