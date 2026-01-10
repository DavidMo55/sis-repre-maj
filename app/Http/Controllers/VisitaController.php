<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Visita;
use App\Models\Cliente;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class VisitaController extends Controller
{
    /**
     * Listado general de visitas con filtros dinámicos.
     */
    public function index(Request $request)
    {
        try {
            $query = Visita::with('cliente')->where('user_id', Auth::id());

            $query->search($request->query('search'))
                  ->byDateRange($request->query('desde'), $request->query('hasta'))
                  ->byResultado($request->query('resultado'));

            $visitas = $query->orderBy('fecha', 'desc')->paginate(15);

            return response()->json($visitas);
        } catch (\Exception $e) {
            Log::error("Error en VisitaController@index: " . $e->getMessage());
            return response()->json(['message' => 'Error al obtener el historial.'], 500);
        }
    }

    /**
     * Detalle de una visita para precarga o consulta.
     * Carga cliente y estado para el "Header" y tarjetas del Front.
     */
    public function show($id)
    {
        try {
            $visita = Visita::with(['cliente.estado'])
                ->where('user_id', Auth::id())
                ->findOrFail($id);

            return response()->json($visita);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Visita no encontrada.'], 404);
        }
    }

    /**
     * Registro de primera visita (Crea Cliente + Visita).
     */
    public function storePrimeraVisita(Request $request)
    {
        $validated = $request->validate([
            'plantel.name' => 'required|string|max:100',
            'plantel.nivel_educativo' => 'required|string',
            'plantel.direccion' => 'required|string',
            'plantel.estado_id' => 'required|exists:estados,id',
            'plantel.telefono' => 'required|string',
            'plantel.email' => 'required|email|unique:clientes,email',
            'plantel.director' => 'required|string',
            'plantel.latitud' => 'nullable|numeric',
            'plantel.longitud' => 'nullable|numeric',
            'visita.fecha' => 'required|date',
            'visita.persona_entrevistada' => 'required|string',
            'visita.cargo' => 'required|string',
            'visita.material_entregado' => 'required|boolean',
            'visita.material_cantidad' => 'required_if:visita.material_entregado,true|nullable|integer',
            'visita.resultado_visita' => 'required|in:seguimiento,compra,rechazo',
            'visita.proxima_visita' => 'nullable|date',
            'visita.comentarios' => 'nullable|string',
        ]);

        try {
            $visitaId = DB::transaction(function () use ($validated, $request) {
                $cliente = Cliente::create([
                    'tipo' => $validated['visita']['resultado_visita'] === 'compra' ? 'CLIENTE' : 'PROSPECTO',
                    'nivel_educativo' => $validated['plantel']['nivel_educativo'],
                    'name' => $validated['plantel']['name'],
                    'contacto' => $validated['plantel']['director'],
                    'email' => $validated['plantel']['email'],
                    'telefono' => $validated['plantel']['telefono'],
                    'direccion' => $validated['plantel']['direccion'],
                    'latitud' => $validated['plantel']['latitud'],
                    'longitud' => $validated['plantel']['longitud'],
                    'estado_id' => $validated['plantel']['estado_id'],
                    'user_id' => Auth::id(),
                    'moneda_id' => 1,
                    'status' => 'activo'
                ]);

                $visita = Visita::create([
                    'user_id' => Auth::id(),
                    'cliente_id' => $cliente->id,
                    'fecha' => $validated['visita']['fecha'],
                    'persona_entrevistada' => $validated['visita']['persona_entrevistada'],
                    'cargo' => $validated['visita']['cargo'],
                    'libros_interes' => $request->input('visita.libros_interes'),
                    'material_entregado' => $validated['visita']['material_entregado'],
                    'material_cantidad' => $validated['visita']['material_cantidad'],
                    'resultado_visita' => $validated['visita']['resultado_visita'],
                    'proxima_visita_estimada' => $validated['visita']['proxima_visita'],
                    'proxima_accion' => $request->input('visita.proxima_accion', 'visita'),
                    'comentarios' => $validated['visita']['comentarios'],
                    'es_primera_visita' => true
                ]);

                return $visita->id;
            });

            return response()->json(['message' => 'Registro exitoso', 'visita_id' => $visitaId], 201);
        } catch (\Exception $e) {
            Log::error("Error en storePrimeraVisita: " . $e->getMessage());
            return response()->json(['message' => 'Error al guardar', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Historial de visitas por cliente.
     * Protegido por Auth::id() para seguridad de datos.
     */
    public function historialPorCliente($cliente_id)
    {
        $historial = Visita::where('cliente_id', $cliente_id)
            ->where('user_id', Auth::id())
            ->orderBy('fecha', 'desc')
            ->get();

        return response()->json($historial);
    }

    /**
     * Registro de Visita de Seguimiento.
     */
    public function storeSeguimiento(Request $request)
    {
        $validated = $request->validate([
            // Ahora aceptamos cliente_id directo para mayor flexibilidad desde el Front
            'cliente_id' => 'required|exists:clientes,id',
            'fecha' => 'required|date',
            'persona_entrevistada' => 'required|string',
            'cargo' => 'required|string',
            'material_entregado' => 'required|boolean',
            'material_cantidad' => 'required_if:material_entregado,true|nullable|integer',
            'resultado_visita' => 'required|in:seguimiento,compra,rechazo',
            'proxima_visita' => 'nullable|date',
            'comentarios' => 'nullable|string',
            'libros_interes' => 'nullable|string'
        ]);

        try {
            $nuevaVisita = DB::transaction(function () use ($validated, $request) {
                
                // Si el resultado es compra, convertimos el PROSPECTO en CLIENTE
                if ($validated['resultado_visita'] === 'compra') {
                    Cliente::where('id', $validated['cliente_id'])->update(['tipo' => 'CLIENTE']);
                }

                return Visita::create([
                    'user_id' => Auth::id(),
                    'cliente_id' => $validated['cliente_id'],
                    'fecha' => $validated['fecha'],
                    'persona_entrevistada' => $validated['persona_entrevistada'],
                    'cargo' => $validated['cargo'],
                    'libros_interes' => $validated['libros_interes'],
                    'material_entregado' => $validated['material_entregado'],
                    'material_cantidad' => $validated['material_cantidad'],
                    'resultado_visita' => $validated['resultado_visita'],
                    'proxima_visita_estimada' => $validated['proxima_visita'],
                    'proxima_accion' => $request->input('proxima_accion', 'visita'),
                    'comentarios' => $validated['comentarios'],
                    'es_primera_visita' => false
                ]);
            });

            return response()->json(['message' => 'Seguimiento registrado', 'visita_id' => $nuevaVisita->id], 201);
        } catch (\Exception $e) {
            Log::error("Error en storeSeguimiento: " . $e->getMessage());
            return response()->json(['message' => 'Error al procesar seguimiento'], 500);
        }
    }
}