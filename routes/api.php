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
    
    // Admin - Dashboard y datos
    Route::get('/admin/dashboard', [AdminAppController::class, 'dashboard']);
    Route::get('/admin/datos-formularios', [AdminAppController::class, 'datosFormularios']);
    
    // Admin - CRUD Proyectos
    Route::get('/admin/proyectos', [AdminAppController::class, 'proyectos']);
    Route::post('/admin/proyectos', [AdminAppController::class, 'storeProyecto']);
    Route::put('/admin/proyectos/{id}', [AdminAppController::class, 'updateProyecto']);
    Route::delete('/admin/proyectos/{id}', [AdminAppController::class, 'deleteProyecto']);
    
    // Admin - CRUD Clientes
    Route::get('/admin/clientes', [AdminAppController::class, 'clientes']);
    Route::get('/admin/clientes/{id}', [AdminAppController::class, 'getCliente']);
    Route::post('/admin/clientes', [AdminAppController::class, 'storeCliente']);
    Route::put('/admin/clientes/{id}', [AdminAppController::class, 'updateCliente']);
    Route::delete('/admin/clientes/{id}', [AdminAppController::class, 'deleteCliente']);
    
    // Admin - CRUD Cobradores
    Route::get('/admin/cobradores', [AdminAppController::class, 'cobradores']);
    Route::post('/admin/cobradores', [AdminAppController::class, 'storeCobrador']);
    Route::put('/admin/cobradores/{id}', [AdminAppController::class, 'updateCobrador']);
    Route::delete('/admin/cobradores/{id}', [AdminAppController::class, 'deleteCobrador']);
    
    // Admin - CRUD Planes
    Route::get('/admin/planes', [AdminAppController::class, 'planes']);
    Route::post('/admin/planes', [AdminAppController::class, 'storePlan']);
    Route::put('/admin/planes/{id}', [AdminAppController::class, 'updatePlan']);
    Route::delete('/admin/planes/{id}', [AdminAppController::class, 'deletePlan']);
    
    // Admin - Servicios
    Route::get('/admin/clientes/{id}/servicios', [AdminAppController::class, 'serviciosCliente']);
    Route::post('/admin/servicios', [AdminAppController::class, 'storeServicio']);
    Route::put('/admin/servicios/{id}', [AdminAppController::class, 'updateServicio']);
    Route::delete('/admin/servicios/{id}', [AdminAppController::class, 'deleteServicio']);
    
    // Admin - Gastos de Proyecto
    Route::get('/admin/proyectos/{id}/gastos', [AdminAppController::class, 'gastosProyecto']);
    Route::get('/admin/proyectos/{id}/resumen', [AdminAppController::class, 'resumenProyecto']);
    Route::post('/admin/gastos', [AdminAppController::class, 'storeGasto']);
    Route::put('/admin/gastos/{id}', [AdminAppController::class, 'updateGasto']);
    Route::delete('/admin/gastos/{id}', [AdminAppController::class, 'deleteGasto']);
    
    // Admin - Facturas
    Route::get('/admin/facturas', [AdminAppController::class, 'facturas']);
    
    // Admin - Pagos
    Route::get('/admin/pagos', [AdminAppController::class, 'pagos']);
    Route::delete('/admin/pagos/{id}', [AdminAppController::class, 'anularPago']);
    
    // Cliente
    Route::get('/cliente/cuenta', [ClienteAppController::class, 'cuenta']);
    Route::get('/cliente/facturas', [ClienteAppController::class, 'facturas']);
    Route::get('/cliente/pagos', [ClienteAppController::class, 'pagos']);
    
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
