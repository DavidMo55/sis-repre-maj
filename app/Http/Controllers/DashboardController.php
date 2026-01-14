<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\Visita;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    /**
     * Obtiene las estadísticas detalladas para el tablero principal.
     * Corregido para evitar errores de columnas faltantes mediante Joins.
     */
    public function getStats(Request $request)
    {
        try {
            $user = $request->user();
            $ownerId = method_exists($user, 'getEffectiveId') ? $user->getEffectiveId() : $user->id;

            $inicioMes = Carbon::now()->startOfMonth();
            $finMes = Carbon::now()->endOfMonth();

            // 1. KPIs BÁSICOS (Estado Actual)
            $prospectosTotal = Cliente::where('user_id', $ownerId)->where('tipo', 'PROSPECTO')->count();
            $clientesTotal = Cliente::where('user_id', $ownerId)->whereIn('tipo', ['CLIENTE', 'DISTRIBUIDOR'])->count();
            $pedidosPendientes = Pedido::where('user_id', $ownerId)->where('status', 'PENDIENTE')->count();

            // Próxima Visita (Usando relación para obtener el nombre del cliente de forma segura)
            $proximaVisitaObj = Visita::with('cliente')
                ->where('user_id', $ownerId)
                ->where('proxima_visita_estimada', '>=', Carbon::today())
                ->orderBy('proxima_visita_estimada', 'asc')
                ->first();

            // 2. RESUMEN MENSUAL
            $visitasTotalesMes = Visita::where('user_id', $ownerId)
                ->whereBetween('fecha', [$inicioMes, $finMes])
                ->count();

            $prospectosNuevosMes = Visita::where('user_id', $ownerId)
                ->whereBetween('fecha', [$inicioMes, $finMes])
                ->where('es_primera_visita', true)
                ->count();

            // 3. GRÁFICO: Visitas por Semana (Agrupación robusta)
            $visitasPorSemana = Visita::where('user_id', $ownerId)
                ->whereBetween('fecha', [$inicioMes, $finMes])
                ->select(
                    DB::raw("FLOOR((DAY(fecha) - 1) / 7) + 1 as semana_num"), 
                    DB::raw("COUNT(*) as total")
                )
                ->groupBy(DB::raw("FLOOR((DAY(fecha) - 1) / 7) + 1"))
                ->get();

            $labels = []; $values = [];
            for ($i = 1; $i <= 5; $i++) {
                $labels[] = "Sem $i";
                $found = $visitasPorSemana->firstWhere('semana_num', $i);
                $values[] = $found ? $found->total : 0;
            }

            // 4. TABLA: Detalle por escuela (CORRECCIÓN DE ERROR SQL: Usando Join con Clientes)
            // Esto asegura obtener el nombre del plantel aunque la columna no esté en la tabla visitas
            $reporteTabla = Visita::join('clientes', 'visitas.cliente_id', '=', 'clientes.id')
                ->where('visitas.user_id', $ownerId)
                ->whereBetween('visitas.fecha', [$inicioMes, $finMes])
                ->select(
                    'clientes.name as nombre', 
                    DB::raw("COUNT(visitas.id) as visitas"), 
                    DB::raw("SUM(CASE WHEN visitas.es_primera_visita = 1 THEN 1 ELSE 0 END) as prospectos")
                )
                ->groupBy('clientes.name')
                ->orderBy('visitas', 'desc')
                ->limit(8)
                ->get();

            return response()->json([
                'prospectos'         => $prospectosTotal,
                'clientes'           => $clientesTotal,
                'pedidos_pendientes' => $pedidosPendientes,
                'proxima_visita'     => $proximaVisitaObj ? [
                    'plantel' => $proximaVisitaObj->cliente ? $proximaVisitaObj->cliente->name : ($proximaVisitaObj->nombre_plantel ?? 'Plantel Desconocido'),
                    'fecha'   => Carbon::parse($proximaVisitaObj->proxima_visita_estimada)->format('d M')
                ] : null,
                'visitas_totales'    => $visitasTotalesMes,
                'prospectos_nuevos'  => $prospectosNuevosMes,
                'top_escuelas'       => $reporteTabla->map(fn($item) => [
                    'nombre' => $item->nombre, 
                    'conteo' => $item->visitas
                ]),
                'reporte_tabla'      => $reporteTabla,
                'grafico_visitas'    => [
                    'labels' => $labels, 
                    'values' => $values
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("Dashboard Error: " . $e->getMessage());
            return response()->json([
                'message' => 'Error de base de datos al generar estadísticas.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}