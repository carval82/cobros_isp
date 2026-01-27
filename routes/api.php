<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CobradorAppController;
use App\Http\Controllers\Api\AdminAppController;
use App\Http\Controllers\Api\ClienteAppController;

// Rutas públicas - Login
Route::post('/cobrador/login', [CobradorAppController::class, 'login']);
Route::post('/admin/login', [AdminAppController::class, 'login']);
Route::post('/cliente/login', [ClienteAppController::class, 'login']);

// Rutas protegidas (requieren token)
Route::middleware('auth:sanctum')->group(function () {
    // Cobrador
    Route::get('/cobrador/proyectos', [CobradorAppController::class, 'proyectos']);
    Route::get('/cobrador/sync', [CobradorAppController::class, 'sync']);
    Route::get('/cobrador/sync/{proyecto_id}', [CobradorAppController::class, 'syncProyecto']);
    Route::post('/cobrador/pago', [CobradorAppController::class, 'registrarPago']);
    Route::post('/cobrador/cliente', [CobradorAppController::class, 'registrarCliente']);
    Route::post('/cobrador/cerrar-cobro', [CobradorAppController::class, 'cerrarCobro']);
    Route::get('/cobrador/resumen-dia', [CobradorAppController::class, 'resumenDia']);
    
    // Admin
    Route::get('/admin/dashboard', [AdminAppController::class, 'dashboard']);
    Route::get('/admin/proyectos', [AdminAppController::class, 'proyectos']);
    Route::get('/admin/clientes', [AdminAppController::class, 'clientes']);
    Route::get('/admin/cobradores', [AdminAppController::class, 'cobradores']);
    
    // Cliente
    Route::get('/cliente/cuenta', [ClienteAppController::class, 'cuenta']);
    Route::get('/cliente/facturas', [ClienteAppController::class, 'facturas']);
    Route::get('/cliente/pagos', [ClienteAppController::class, 'pagos']);
    
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
