<?php

use App\Http\Controllers\Panel\EquipamientoPredioController;
use App\Http\Controllers\Panel\FichaPredioController;
use App\Http\Controllers\Panel\GaleriaPredioController;
use App\Http\Controllers\Panel\PredioController;
use App\Http\Controllers\Panel\ProximamenteController;
use App\Http\Controllers\Panel\ResponsableController;
use App\Http\Controllers\Panel\SolicitudController;
use App\Http\Controllers\Panel\TarifaController;
use App\Http\Controllers\Panel\UsuarioController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\Panel\LiberacionController;
use App\Http\Controllers\Panel\PagoController;
use App\Http\Controllers\Panel\EntregaController;
use App\Http\Controllers\Panel\LimpiezaController;
use App\Http\Controllers\Panel\SancionController;
use App\Http\Controllers\Panel\MantenimientoController;
use App\Http\Controllers\Panel\ReporteController;
use App\Http\Controllers\Panel\ConsultaPredioController;
use App\Http\Controllers\Panel\MisPagosController;
use App\Http\Controllers\Panel\CalendarioSolicitudController;
use App\Http\Controllers\Panel\DeudorController;
use App\Http\Controllers\Panel\PlanCuotasController;
use App\Http\Controllers\Panel\PagoQrController;
use App\Http\Controllers\Panel\PagoFacilCallbackController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/panel', [PanelController::class, 'index'])->name('panel');

    Route::prefix('panel/usuarios')->name('panel.usuarios.')->group(function () {
        Route::get('/', [UsuarioController::class, 'index'])->name('index');
        Route::get('/crear', [UsuarioController::class, 'create'])->name('create');
        Route::post('/', [UsuarioController::class, 'store'])->name('store');
        Route::get('/{usuario}/editar', [UsuarioController::class, 'edit'])->name('edit');
        Route::put('/{usuario}', [UsuarioController::class, 'update'])->name('update');
    });

    Route::prefix('panel/responsables')->name('panel.responsables.')->group(function () {
        Route::get('/', [ResponsableController::class, 'index'])->name('index');
        Route::get('/crear', [ResponsableController::class, 'create'])->name('create');
        Route::post('/', [ResponsableController::class, 'store'])->name('store');
        Route::get('/{responsable}/editar', [ResponsableController::class, 'edit'])->name('edit');
        Route::put('/{responsable}', [ResponsableController::class, 'update'])->name('update');
    });

    Route::prefix('panel/predios')->name('panel.predios.')->group(function () {
        Route::get('/', [PredioController::class, 'index'])->name('index');
        Route::get('/crear', [PredioController::class, 'create'])->name('create');
        Route::post('/', [PredioController::class, 'store'])->name('store');
        Route::get('/{predio}/editar', [PredioController::class, 'edit'])->name('edit');
        Route::put('/{predio}', [PredioController::class, 'update'])->name('update');

        Route::get('/{predio}', [FichaPredioController::class, 'show'])->name('show');

        Route::post('/{predio}/galeria', [GaleriaPredioController::class, 'store'])->name('galeria.store');
        Route::delete('/{predio}/galeria/{galeria}', [GaleriaPredioController::class, 'destroy'])->name('galeria.destroy');

        Route::post('/{predio}/inventario', [EquipamientoPredioController::class, 'store'])->name('inventario.store');
        Route::put('/{predio}/tarifas/{tarifa}', [TarifaController::class, 'update'])->name('tarifas.update');
        Route::put('/{predio}/inventario/{equipamiento}', [EquipamientoPredioController::class, 'update'])->name('inventario.update');
        Route::delete('/{predio}/inventario/{equipamiento}', [EquipamientoPredioController::class, 'destroy'])->name('inventario.destroy');

        Route::post('/{predio}/tarifas', [TarifaController::class, 'store'])->name('tarifas.store');
    });
    Route::prefix('panel/solicitantes')->name('panel.solicitantes.')->group(function () {
        Route::get('/', [SolicitudController::class, 'index'])->name('index');
        Route::get('/crear', [SolicitudController::class, 'create'])->name('create');
        Route::post('/', [SolicitudController::class, 'store'])->name('store');

        // Deben ir antes de /{solicitud}, si no Laravel los toma como un número de solicitud
        Route::get('/disponibilidad', [SolicitudController::class, 'disponibilidad'])->name('disponibilidad');
        Route::get('/calendario', [CalendarioSolicitudController::class, 'index'])->name('calendario');

        Route::get('/{solicitud}', [SolicitudController::class, 'show'])->name('show');
        Route::post('/{solicitud}/respaldos', [SolicitudController::class, 'storeRespaldo'])->name('respaldos.store');
        Route::post('/{solicitud}/proveido', [SolicitudController::class, 'guardarProveido'])->name('proveido.store');
        Route::post('/{solicitud}/aprobar', [SolicitudController::class, 'aprobar'])->name('aprobar');
        Route::post('/{solicitud}/rechazar', [SolicitudController::class, 'rechazar'])->name('rechazar');
    });

       Route::prefix('panel/financiera')->name('panel.financiera.')->group(function () {
        Route::get('/', [LiberacionController::class, 'index'])->name('index');

        // Debe ir antes de /{solicitudPredio}, si no Laravel lo toma como un número de fecha
        Route::get('/historial', [LiberacionController::class, 'historial'])->name('historial');

        Route::get('/{solicitudPredio}', [LiberacionController::class, 'show'])->name('show');
        Route::post('/{solicitudPredio}/liberar', [LiberacionController::class, 'liberar'])->name('liberar');
        Route::post('/{solicitudPredio}/reprogramar', [LiberacionController::class, 'reprogramar'])->name('reprogramar');
    });
          Route::prefix('panel/pagos')->name('panel.pagos.')->group(function () {
        Route::get('/', [PagoController::class, 'index'])->name('index');

        // Debe ir antes de /{reserva}, si no Laravel lo toma como un número de reserva
        Route::get('/deudores', [DeudorController::class, 'index'])->name('deudores');

        Route::get('/{reserva}', [PagoController::class, 'show'])->name('show');
        Route::post('/{reserva}/pago', [PagoController::class, 'registrarPago'])->name('registrar');
        Route::post('/{reserva}/cobrar', [PagoController::class, 'cobrar'])->name('cobrar');
        Route::put('/{reserva}/plan', [PlanCuotasController::class, 'update'])->name('plan.update');
        // Cobro por QR
Route::post('/panel/pagos/{reserva}/plan-qr', [PagoQrController::class, 'crearPlan'])->name('panel.pagos.plan-qr');
Route::post('/panel/mis-pagos/{reserva}/plan-qr', [PagoQrController::class, 'crearPlan'])->name('panel.mis-pagos.plan-qr');
Route::post('/panel/cuotas/{cuota}/qr', [PagoQrController::class, 'generar'])->middleware('throttle:20,1')->name('panel.cuotas.qr.generar');
Route::get('/panel/cuotas/{cuota}/qr/estado', [PagoQrController::class, 'estado'])->middleware('throttle:60,1')->name('panel.cuotas.qr.estado');
    });
           Route::prefix('panel/entrega')->name('panel.entrega.')->group(function () {
        Route::get('/', [EntregaController::class, 'index'])->name('index');

        Route::post('/personal', [LimpiezaController::class, 'storePersonal'])->name('personal.store');
        Route::post('/personal/{personal}/activo', [LimpiezaController::class, 'cambiarActivo'])->name('personal.activo');
        Route::post('/sanciones/{sancion}/estado', [SancionController::class, 'cambiarEstado'])->name('sanciones.estado');

        Route::get('/{reserva}', [EntregaController::class, 'show'])->name('show');
        Route::post('/{reserva}/acta', [EntregaController::class, 'crearActa'])->name('acta.store');
        Route::post('/{reserva}/devolucion', [EntregaController::class, 'registrarDevolucion'])->name('devolucion.store');
        Route::post('/{reserva}/incidentes', [EntregaController::class, 'agregarIncidente'])->name('incidentes.store');
        Route::post('/{reserva}/limpieza', [LimpiezaController::class, 'asignar'])->name('limpieza.store');
        Route::post('/{reserva}/limpieza/realizada', [LimpiezaController::class, 'marcarRealizada'])->name('limpieza.realizada');
        Route::post('/{reserva}/sanciones', [SancionController::class, 'store'])->name('sanciones.store');
    });
        Route::prefix('panel/mantenimiento')->name('panel.mantenimiento.')->group(function () {
        Route::get('/', [MantenimientoController::class, 'index'])->name('index');
        Route::post('/', [MantenimientoController::class, 'store'])->name('store');
        Route::delete('/{mantenimiento}', [MantenimientoController::class, 'destroy'])->name('destroy');
    });

    Route::get('/panel/reportes', [ReporteController::class, 'index'])->name('panel.reportes.index');
    Route::get('/panel/reportes/imprimir', [ReporteController::class, 'imprimir'])->name('panel.reportes.imprimir');

        Route::prefix('panel/consulta')->name('panel.consulta.')->group(function () {
        Route::get('/predios', [ConsultaPredioController::class, 'index'])->name('predios.index');
        Route::get('/predios/{predio}', [ConsultaPredioController::class, 'show'])->name('predios.show');
        
    });

    Route::get('/panel/mis-pagos', [MisPagosController::class, 'index'])->name('panel.mis-pagos.index');
        
});
// Notificación de PagoFácil: pública. La validez se comprueba consultando a PagoFácil.
Route::post('/pagofacil/callback', PagoFacilCallbackController::class)
    ->middleware('throttle:60,1')
    ->name('pagofacil.callback');