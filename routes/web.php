<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProyectoController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CobradorController;
use App\Http\Controllers\PlanServicioController;
use App\Http\Controllers\ServicioController;
use App\Http\Controllers\FacturaController;
use App\Http\Controllers\CobroController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\LiquidacionController;

// Ruta temporal para crear usuarios (eliminar después de usar)
Route::get('setup-admin', function () {
    $admins = [
        ['name' => 'Pedro Capacho', 'email' => 'pcapacho24@gmail.com', 'password' => bcrypt('Anavalia331$')],
        ['name' => 'Domingo Rivero', 'email' => 'domingorivero.iutc@gmail.com', 'password' => bcrypt('Zeus19$$')],
    ];
    foreach ($admins as $admin) {
        \App\Models\User::firstOrCreate(['email' => $admin['email']], $admin);
    }
    return 'Usuarios creados: ' . \App\Models\User::count();
});

// Autenticación
Route::get('login', [AuthController::class, 'showLogin'])->name('login');
Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout'])->name('logout');

// Rutas protegidas
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Recursos
    Route::resource('proyectos', ProyectoController::class);
    Route::resource('clientes', ClienteController::class);
    Route::resource('cobradores', CobradorController::class);
    Route::resource('planes', PlanServicioController::class);
    Route::resource('servicios', ServicioController::class);
    Route::resource('facturas', FacturaController::class);
    Route::resource('cobros', CobroController::class);
    Route::resource('pagos', PagoController::class);
    Route::resource('liquidaciones', LiquidacionController::class);

    // Acciones especiales
    Route::post('cobros/{cobro}/cerrar', [CobroController::class, 'cerrar'])->name('cobros.cerrar');
    Route::post('facturas/generar-mes', [FacturaController::class, 'generarMes'])->name('facturas.generar-mes');
    Route::post('facturas/generar-mes-proyecto', [FacturaController::class, 'generarMesProyecto'])->name('facturas.generar-mes-proyecto');
    Route::get('facturas/{factura}/pdf', [FacturaController::class, 'pdf'])->name('facturas.pdf');
    Route::get('facturas/{factura}/descargar-pdf', [FacturaController::class, 'descargarPdf'])->name('facturas.descargar-pdf');
    Route::post('liquidaciones/{liquidacion}/pagar', [LiquidacionController::class, 'pagar'])->name('liquidaciones.pagar');
});

// API para la app móvil (sin autenticación web)
Route::prefix('api')->group(function () {
    Route::get('clientes', [ClienteController::class, 'apiIndex']);
    Route::get('clientes/{cliente}/facturas', [ClienteController::class, 'apiFacturas']);
    Route::post('pagos', [PagoController::class, 'apiStore']);
    Route::get('cobros/{cobro}', [CobroController::class, 'apiShow']);
});
