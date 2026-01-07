<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\PedidoController; 
use App\Http\Controllers\SearchController;
use App\Http\Controllers\GastoController;
use App\Http\Controllers\VisitaController;
use App\Http\Controllers\DashboardController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/password/request-ticket', [TicketController::class, 'storeRecoveryTicket']);

Route::middleware('auth:sanctum')->group(function () {
    
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/dashboard/stats', [DashboardController::class, 'getStats']);
    
    Route::get('/pedidos', [PedidoController::class, 'index']); 
    Route::post('/pedidos', [PedidoController::class, 'store']);
    Route::get('/pedidos/{id}', [PedidoController::class, 'show']); 

    Route::get('/gastos', [GastoController::class, 'index']); 
    Route::post('/gastos/comprobante', [GastoController::class, 'storeComprobante']); 
    Route::get('/gastos/{id}', [GastoController::class, 'show']); 
    
    Route::get('/search/clientes', [SearchController::class, 'searchClientes']);
    Route::get('/search/libros', [SearchController::class, 'searchLibros']);
    Route::get('/estados', [SearchController::class, 'getEstados']);

    Route::get('/visitas', [VisitaController::class, 'index']);
    Route::post('/visitas/primera', [VisitaController::class, 'storePrimeraVisita']);
    Route::get('/visitas/{id}', [VisitaController::class, 'show']);
    Route::post('/visitas/seguimiento', [VisitaController::class, 'storeSeguimiento']); 
    Route::get('/search/prospectos', [SearchController::class, 'searchProspectos']);

});