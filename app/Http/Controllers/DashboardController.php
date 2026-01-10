<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\Visita;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function getStats()
    {
        try {
            $userId = Auth::id();

            /**
             * 1. Conteo de Prospectos en Seguimiento
             * Contamos clientes únicos de tipo 'PROSPECTO' que tienen visitas 
             * registradas con resultado 'seguimiento'.
             */
            $prospectosCount = Visita::where('user_id', $userId)
                ->where('resultado_visita', 'seguimiento')
                ->whereHas('cliente', function($query) {
                    $query->where('tipo', 'PROSPECTO');
                })
                ->distinct('cliente_id')
                ->count('cliente_id');

            /**
             * 2. Conteo de Clientes Oficiales
             * Contamos los registros en la tabla clientes asignados a este usuario
             * que ya son CLIENTE o DISTRIBUIDOR.
             */
            $clientesCount = Cliente::where('user_id', $userId)
                ->whereIn('tipo', ['CLIENTE', 'DISTRIBUIDOR'])
                ->count();

            /**
             * 3. Pedidos Pendientes
             */
            $pedidosPendientes = Pedido::where('user_id', $userId)
                ->where('status', 'PENDIENTE')
                ->count();

            /**
             * 4. Próxima Visita Programada
             * Obtenemos la visita más cercana a partir de hoy, cargando la relación
             * del cliente para obtener el nombre del plantel.
             */
            $proximaVisita = Visita::with('cliente')
                ->where('user_id', $userId)
                ->where('proxima_visita_estimada', '>=', Carbon::today())
                ->orderBy('proxima_visita_estimada', 'asc')
                ->first();

            return response()->json([
                'prospectos' => $prospectosCount,
                'clientes' => $clientesCount,
                'pedidos_pendientes' => $pedidosPendientes,
                'proxima_visita' => $proximaVisita && $proximaVisita->cliente ? [
                    'plantel' => $proximaVisita->cliente->name, 
                    'fecha' => Carbon::parse($proximaVisita->proxima_visita_estimada)->format('d M'),
                ] : null
            ]);

        } catch (\Exception $e) {
            Log::error("Error en DashboardController@getStats: " . $e->getMessage());
            return response()->json([
                'message' => 'Error al cargar las estadísticas del tablero.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}