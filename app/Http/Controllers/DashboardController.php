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

        // 1. Conteo de Prospectos y Clientes (usamos la tabla clientes)
        $prospectosCount = Cliente::where('user_id', $userId)
            ->where('tipo', 'PROSPECTO')
            ->count();

        $clientesCount = Cliente::where('user_id', $userId)
            ->whereIn('tipo', ['CLIENTE', 'DISTRIBUIDOR'])
            ->count();

        // 2. Pedidos Pendientes
        $pedidosPendientes = Pedido::where('user_id', $userId)
            ->where('status', 'PENDIENTE')
            ->count();

        // 3. Próxima Visita programada (la más cercana a partir de hoy)
        $proximaVisita = Visita::where('user_id', $userId)
            ->with('cliente')
            ->where('proxima_visita_estimada', '>=', Carbon::today())
            ->orderBy('proxima_visita_estimada', 'asc')
            ->first();

        return response()->json([
            'prospectos' => $prospectosCount,
            'clientes' => $clientesCount,
            'pedidos_pendientes' => $pedidosPendientes,
            'proxima_visita' => $proximaVisita ? [
                'plantel' => $proximaVisita->cliente->name,
                'fecha' => $proximaVisita->proxima_visita_estimada->format('d M'),
            ] : null
        ]);
    }
}