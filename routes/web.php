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
use App\Http\Controllers\Portal\ClientePortalController;
use App\Http\Controllers\TicketController;

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

    // Tickets de soporte
    Route::get('tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    Route::post('tickets/{ticket}/responder', [TicketController::class, 'responder'])->name('tickets.responder');
    Route::post('tickets/{ticket}/estado', [TicketController::class, 'cambiarEstado'])->name('tickets.estado');
});

// API para la app móvil (sin autenticación web)
Route::prefix('api')->group(function () {
    Route::get('clientes', [ClienteController::class, 'apiIndex']);
    Route::get('clientes/{cliente}/facturas', [ClienteController::class, 'apiFacturas']);
    Route::post('pagos', [PagoController::class, 'apiStore']);
    Route::get('cobros/{cobro}', [CobroController::class, 'apiShow']);
});

// Portal de Clientes
Route::prefix('portal')->name('portal.')->group(function () {
    Route::get('login', [ClientePortalController::class, 'showLogin'])->name('login');
    Route::post('login', [ClientePortalController::class, 'login'])->name('login.post');
    Route::post('logout', [ClientePortalController::class, 'logout'])->name('logout');
    
    // Rutas protegidas del portal
    Route::middleware('portal.auth')->group(function () {
        Route::get('/', [ClientePortalController::class, 'dashboard'])->name('dashboard');
        Route::get('estado-cuenta', [ClientePortalController::class, 'estadoCuenta'])->name('estado-cuenta');
        Route::get('tickets', [ClientePortalController::class, 'tickets'])->name('tickets');
        Route::get('tickets/crear', [ClientePortalController::class, 'crearTicket'])->name('tickets.crear');
        Route::post('tickets', [ClientePortalController::class, 'guardarTicket'])->name('tickets.guardar');
        Route::get('tickets/{id}', [ClientePortalController::class, 'verTicket'])->name('tickets.ver');
        Route::get('perfil', [ClientePortalController::class, 'perfil'])->name('perfil');
        Route::put('perfil', [ClientePortalController::class, 'actualizarPerfil'])->name('perfil.actualizar');
        Route::put('perfil/pin', [ClientePortalController::class, 'cambiarPin'])->name('perfil.pin');
    });
});
