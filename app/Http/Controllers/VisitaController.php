<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Visita;
use App\Models\Cliente;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class VisitaController extends Controller
{
    /**
     * Listado general de visitas.
     */
    public function index(Request $request)
    {
        $visitas = Visita::where('user_id', Auth::id())
            ->with('cliente')
            ->orderBy('fecha', 'desc')
            ->paginate(15);

        return response()->json($visitas);
    }

    /**
     * Mostrar detalles de una visita específica.
     * Actualizado para diagnóstico profundo.
     */
    public function show($id)
    {
        try {
            // DEBUG: Registramos en el log qué estamos buscando
            Log::info("Buscando Visita ID: {$id} para Usuario: " . Auth::id());

            // PASO 1: Intentamos buscarla sin el filtro de usuario para ver si existe
            $visitaExistente = Visita::find($id);

            if (!$visitaExistente) {
                return response()->json([
                    'message' => "Error: El registro con ID #{$id} no existe en la tabla 'visitas'."
                ], 404);
            }

            // PASO 2: Buscamos con todas las relaciones y el filtro de seguridad
            $visita = Visita::with(['cliente.estado', 'representative'])
                ->where('user_id', Auth::id())
                ->findOrFail($id);

            return response()->json($visita);

        } catch (ModelNotFoundException $e) {
            // Si el PASO 1 funcionó pero el PASO 2 no, es un problema de pertenencia
            return response()->json([
                'message' => "Acceso Denegado: La visita existe pero no fue registrada por tu cuenta.",
                'debug_info' => [
                    'visita_owner' => $visitaExistente->user_id,
                    'current_user' => Auth::id()
                ]
            ], 403);
        } catch (\Exception $e) {
            Log::error("Error en VisitaController@show: " . $e->getMessage());
            return response()->json(['message' => 'Error interno: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Registro de primera visita.
     */
    public function storePrimeraVisita(Request $request)
    {
        $request->validate([
            'plantel.name' => 'required|string|unique:clientes,name',
            'plantel.estado_id' => 'required|exists:estados,id',
            'plantel.email' => 'required|email|unique:clientes,email',
            'visita.fecha' => 'required|date',
            'visita.persona_entrevistada' => 'required|string',
            'visita.material_entregado' => 'required|boolean',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                // Crear Plantel
                $cliente = Cliente::create([
                    'name'        => $request->plantel['name'],
                    'tipo'        => 'PROSPECTO',
                    'contacto'    => $request->plantel['director'],
                    'email'       => $request->plantel['email'],
                    'telefono'    => $request->plantel['telefono'],
                    'tel_oficina' => $request->plantel['tel_fijo'],
                    'direccion'   => $request->plantel['direccion'],
                    'estado_id'   => $request->plantel['estado_id'],
                    'user_id'     => Auth::id(),
                    'moneda_id'   => 1, 
                    'status'      => 'activo'
                ]);

                // Crear Visita
                $visita = Visita::create([
                    'user_id'                 => Auth::id(),
                    'cliente_id'              => $cliente->id,
                    'fecha'                   => $request->visita['fecha'],
                    'persona_entrevistada'    => $request->visita['persona_entrevistada'],
                    'cargo'                   => $request->visita['cargo'],
                    'libros_interes'          => $request->visita['libros_interes'],
                    'material_entregado'      => $request->visita['material_entregado'],
                    'comentarios'             => $request->visita['comentarios'],
                    'proxima_visita_estimada' => $request->visita['proxima_visita'],
                    'es_primera_visita'       => true
                ]);

                return response()->json([
                    'message' => 'Registro completado con éxito.',
                    'visita_id' => $visita->id
                ], 201);
            });
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al guardar: ' . $e->getMessage()], 500);
        }
    }


   public function storeSeguimiento(Request $request)
    {
        $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'fecha' => 'required|date',
            'persona_entrevistada' => 'required|string',
            'cargo' => 'required|string',
            'decision_final' => 'required|in:seguimiento,compra,rechazo'
        ]);

        try {
            return DB::transaction(function () use ($request) {
                // 1. Crear el registro de la visita
                // Asegúrate de que el nombre del campo coincida con tu migración (resultado_visita)
                $visita = Visita::create([
                    'user_id' => Auth::id(),
                    'cliente_id' => $request->cliente_id,
                    'fecha' => $request->fecha,
                    'persona_entrevistada' => $request->persona_entrevistada,
                    'cargo' => $request->cargo,
                    'libros_interes' => $request->libros_interes,
                    'material_entregado' => $request->material_entregado,
                    'comentarios' => $request->comentarios,
                    'proxima_visita_estimada' => $request->proxima_visita,
                    'es_primera_visita' => false,
                    'resultado_visita' => $request->decision_final 
                ]);

                $cliente = Cliente::findOrFail($request->cliente_id);
                
                if ($request->decision_final === 'compra') {
                    $cliente->update([
                        'tipo' => 'CLIENTE',
                        'status' => 'activo'
                    ]);
                } elseif ($request->decision_final === 'rechazo') {
                    $cliente->update([
                        'status' => 'inactivo'
                    ]);
                } else {
                    $cliente->update([
                        'status' => 'activo'
                    ]);
                }

                return response()->json([
                    'message' => 'Seguimiento registrado y estatus actualizado.',
                    'visita' => $visita,
                    'cliente_tipo' => $cliente->tipo,
                    'cliente_status' => $cliente->status
                ], 201);
            });
        } catch (\Exception $e) {
            Log::error("Error en storeSeguimiento: " . $e->getMessage());
            return response()->json(['message' => 'Error al procesar: ' . $e->getMessage()], 500);
        }
    }
}