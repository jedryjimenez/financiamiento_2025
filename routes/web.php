<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProductorController;
use App\Http\Controllers\InsumoController;
use App\Http\Controllers\CreditoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\RecepcionInsumoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CuotaController;
use App\Http\Controllers\CajaController;
use App\Http\Controllers\PosventaController;
use App\Http\Controllers\RecepcionProductoController;
use App\Http\Controllers\ReporteController;

Route::get('/', fn() => view('login'));

Route::middleware(['auth', 'verified'])->group(function () {

    /** Dashboard **/
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    /** Perfil de Usuario **/
    Route::get('/profile',  [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /** Productores **/
    Route::resource('productores', ProductorController::class);
    Route::get('/productor/{productor}/saldo', [ProductorController::class, 'saldo'])->name('productor.saldo');

    /** Insumos **/
    Route::get('insumos/list',                [InsumoController::class, 'list'])->name('insumos.list');
    Route::get('insumos/export',              [InsumoController::class, 'export'])->name('insumos.export');
    Route::get('insumos/stock-minimo',        [InsumoController::class, 'stockMinimo'])->name('insumos.stock_minimo');
    Route::resource('insumos',                InsumoController::class)->except(['show']);

    /** Créditos y Cuotas **/
    Route::resource('creditos', CreditoController::class)->except(['show', 'edit', 'update']);
    Route::post('creditos/{credito}/abonar', [CreditoController::class, 'abonar'])->name('creditos.abonar');
    Route::get('creditos/{credito}/pdf',     [CreditoController::class, 'pdf'])->name('creditos.pdf');
    Route::get('creditos/{credito}/recibo',  [CreditoController::class, 'recibo'])->name('creditos.recibo');

    Route::post('cuotas/{cuota}/pagar', [CuotaController::class, 'pagar'])->name('cuotas.pagar');

    /** Proveedores y Recepción de Insumos **/
    Route::resource('proveedores', ProveedorController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::prefix('recepcion')->group(function () {
        Route::get('/',                         [RecepcionInsumoController::class, 'index'])->name('recepcion.index');
        Route::post('/',                        [RecepcionInsumoController::class, 'store'])->name('recepcion.store');
        Route::delete('/{recepcion}',           [RecepcionInsumoController::class, 'destroy'])->name('recepcion.destroy');
        Route::post('/{recepcion}/abonar',      [RecepcionInsumoController::class, 'abonar'])->name('recepcion.abonar');
        Route::get('/{recepcion}/pdf',          [RecepcionInsumoController::class, 'pdf'])->name('recepcion.pdf');
        Route::get('/export',                   [RecepcionInsumoController::class, 'export'])->name('recepcion.export');
        Route::get('/{recepcion}/recibo-print', [RecepcionInsumoController::class, 'reciboPrint'])->name('recepciones.recibo');
    });

    /** Recepción de productos como pago de crédito **/
    Route::prefix('recepciones')->name('recepciones.')->group(function () {
        Route::get('/',                       [RecepcionProductoController::class, 'index'])->name('index');
        Route::get('/nueva',                  [RecepcionProductoController::class, 'create'])->name('create');
        Route::post('/',                      [RecepcionProductoController::class, 'store'])->name('store');
        Route::get('/export/excel',           [RecepcionProductoController::class, 'exportExcel'])->name('export.excel');
        Route::get('/export/pdf',             [RecepcionProductoController::class, 'exportPDF'])->name('export.pdf');
        Route::get('/{recepcion}/recibo',     [RecepcionProductoController::class, 'recibo'])->name('recibo');
        Route::get('/{recepcion}/recibo/pdf', [RecepcionProductoController::class, 'reciboPdf'])->name('recibo.pdf');
    });

    /** Caja **/
    Route::prefix('caja')->name('caja.')->group(function () {
        Route::get('/',              [CajaController::class, 'index'])->name('index');
        Route::post('abrir',         [CajaController::class, 'abrir'])->name('abrir');
        Route::post('movimientos',   [CajaController::class, 'movimientosStore'])->name('movimientos.store');
        Route::get('{caja}/cierre',  [CajaController::class, 'cierreForm'])->name('cierre.form');
        Route::post('{caja}/cerrar', [CajaController::class, 'cerrar'])->name('cerrar');
        Route::get('historial',      [CajaController::class, 'historial'])->name('historial');
    });

    /** Punto de Venta **/
    Route::prefix('posventa')->name('posventa.')->group(function () {
        Route::get('/',                 [PosventaController::class, 'create'])->name('create');
        Route::post('/',                [PosventaController::class, 'store'])->name('store');
        Route::get('/buscar-insumos',   [PosventaController::class, 'buscarInsumos'])->name('buscar');
        Route::get('/listar',           [PosventaController::class, 'listar'])->name('index');
        Route::post('/{venta}/anular',  [PosventaController::class, 'anular'])->name('anular');
    });

    /** Reportes: Estado de Cuenta General **/
    Route::prefix('estado-cuenta-general')->group(function () {
        Route::get('/',        [ReporteController::class, 'estadoCuentaGeneral'])->name('reportes.estado_cuenta_general');
        Route::get('/pdf',     [ReporteController::class, 'estadoCuentaGeneralPDF'])->name('reportes.estado_cuenta_general.pdf');
        Route::get('/estado-cuenta-general/export-excel', [ReporteController::class, 'exportarEstadoCuentaGeneralExcel'])->name('reportes.estado_cuenta_general.excel');
    });

    /** Reportes: Ficha del Productor **/
    Route::get('/ficha-productor',                  [ReporteController::class, 'fichaProductor'])->name('reportes.ficha_productor');
    Route::get('/ficha-productor/pdf',              [ReporteController::class, 'fichaProductorPDF'])->name('reportes.ficha_productor.pdf');
    Route::get('/ficha-productor/pdf/{productor_id}', [ReporteController::class, 'fichaProductorPDF'])->name('reportes.ficha_productor_pdf');

    /** Reportes: Productores con Créditos Activos **/
    Route::get('reportes/productores-creditos-activos',         [ReporteController::class, 'productoresConCreditosActivos'])->name('reportes.productores_creditos_activos');
    Route::get('reportes/productores-creditos-activos/pdf',     [ReporteController::class, 'exportarPDF'])->name('reportes.productores_creditos_activos.pdf');
    Route::get('reportes/productores-creditos-activos/excel',   [ReporteController::class, 'exportarExcel'])->name('reportes.productores_creditos_activos.excel');
});

require __DIR__ . '/auth.php';
