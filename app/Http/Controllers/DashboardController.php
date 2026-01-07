<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\Visita;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function getStats()
    {
        $userId = Auth::id();

        /**
         * 1. Conteo de Prospectos
         * Contamos los registros únicos por 'nombre_plantel' en la tabla visitas 
         * que aún no tienen un cliente_id asociado y están en seguimiento.
         */
        $prospectosCount = Visita::where('user_id', $userId)
            ->whereNull('cliente_id')
            ->where('resultado_visita', 'seguimiento')
            ->distinct('nombre_plantel')
            ->count();

        /**
         * 2. Conteo de Clientes Oficiales
         * Contamos los registros en la tabla clientes asignados a este usuario.
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
         * Obtenemos la fecha más cercana a partir de hoy.
         * IMPORTANTE: Usamos 'nombre_plantel' directamente de la tabla visitas
         * para evitar errores de relación nula con clientes.
         */
        $proximaVisita = Visita::where('user_id', $userId)
            ->where('proxima_visita_estimada', '>=', Carbon::today())
            ->orderBy('proxima_visita_estimada', 'asc')
            ->first();

        return response()->json([
            'prospectos' => $prospectosCount,
            'clientes' => $clientesCount,
            'pedidos_pendientes' => $pedidosPendientes,
            'proxima_visita' => $proximaVisita ? [
                'plantel' => $proximaVisita->nombre_plantel, 
                'fecha' => Carbon::parse($proximaVisita->proxima_visita_estimada)->format('d M'),
            ] : null
        ]);
    }
}