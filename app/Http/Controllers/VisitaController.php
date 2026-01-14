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
     * Listado de visitas con soporte para delegados.
     */
    public function index(Request $request)
    {
        try {
            // CRÍTICO: Obtenemos el ID del representante dueño de la información
            $user = $request->user();
            $ownerId = method_exists($user, 'getEffectiveId') ? $user->getEffectiveId() : $user->id;

            // Filtramos por el ID del dueño, no por el ID del delegado
            $query = Visita::where('user_id', $ownerId);

            // Aplicamos los filtros de búsqueda (Scopes definidos en el modelo)
            $query->search($request->input('search'))
                  ->byDateRange($request->input('desde'), $request->input('hasta'))
                  ->byResultado($request->input('resultado'));

            if ($request->filled('estado_id')) {
                $query->where('estado_id', $request->estado_id);
            }

            $visitas = $query->with(['estado', 'cliente'])
                ->orderBy('fecha', 'desc')
                ->paginate(15);

            return response()->json($visitas);

        } catch (\Exception $e) {
            Log::error("Error al listar visitas para el usuario {$request->user()->id}: " . $e->getMessage());
            return response()->json(['message' => 'Error al cargar el historial de visitas.'], 500);
        }
    }

    /**
     * Registro de primera visita (Prospecto).
     * Asegura que el registro quede vinculado al Representante.
     */
    public function storePrimeraVisita(Request $request)
    {
        // Validación de datos según los nuevos requerimientos
        $validated = $request->validate([
            // Datos del Cliente (Plantel)
            'plantel.name'      => 'required|string|max:100',
            'plantel.rfc'       => 'required|string|max:50', // RFC Obligatorio
            'plantel.niveles'   => 'required|array|min:1',   // Selección múltiple
            'plantel.direccion' => 'nullable|string',
            'plantel.estado_id' => 'required|exists:estados,id',
            'plantel.telefono'  => 'required|string',
            'plantel.email'     => 'required|email',
            'plantel.director'  => 'required|string',
            'plantel.latitud'   => 'nullable|numeric',
            'plantel.longitud'  => 'nullable|numeric',
            
            // Datos de la Visita
            'visita.fecha'                => 'required|date',
            'visita.persona_entrevistada' => 'required|string',
            'visita.cargo'                => 'required|string',
            'visita.libros_interes'       => 'required|array|min:1', // Lista detallada con formato y promo
            'visita.comentarios'          => 'nullable|string',
            'visita.resultado_visita'     => 'required|in:seguimiento,compra,rechazo',
            'visita.proxima_visita'       => 'nullable|date',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                $user = $request->user();
                $ownerId = method_exists($user, 'getEffectiveId') ? $user->getEffectiveId() : $user->id;

                // 1. GESTIÓN DEL CLIENTE (Prospecto)
                $cliente = Cliente::updateOrCreate(
                    ['email' => $request->input('plantel.email')],
                    [
                        'user_id'         => $ownerId,
                        'tipo'            => 'PROSPECTO',
                        'name'            => $request->input('plantel.name'),
                        'rfc'             => strtoupper($request->input('plantel.rfc')),
                        'nivel_educativo' => implode(', ', $request->input('plantel.niveles')),
                        'contacto'        => $request->input('plantel.director'),
                        'telefono'        => $request->input('plantel.telefono'),
                        'direccion'       => $request->input('plantel.direccion'),
                        'estado_id'       => $request->input('plantel.estado_id'),
                        'latitud'         => $request->input('plantel.latitud'),
                        'longitud'        => $request->input('plantel.longitud'),
                        'status'          => 'activo'
                    ]
                );

                // 2. REGISTRO DE LA VISITA
                $librosDetalle = json_encode($request->input('visita.libros_interes'));

                $visita = Visita::create([
                    'user_id'                 => $ownerId,
                    'cliente_id'              => $cliente->id, // Vinculación real
                    'nombre_plantel'          => $cliente->name,
                    'nivel_educativo_plantel' => $cliente->nivel_educativo,
                    'direccion_plantel'       => $cliente->direccion,
                    'fecha'                   => $request->input('visita.fecha'),
                    'persona_entrevistada'    => $request->input('visita.persona_entrevistada'),
                    'cargo'                   => $request->input('visita.cargo'),
                    'libros_interes'          => $librosDetalle, // Guardamos el JSON completo
                    'comentarios'             => $request->input('visita.comentarios'),
                    'resultado_visita'        => $request->input('visita.resultado_visita'),
                    'proxima_visita_estimada' => $request->input('visita.proxima_visita'),
                    'es_primera_visita'       => true,
                ]);

                return response()->json([
                    'message' => 'Prospecto creado y visita registrada con éxito',
                    'cliente_id' => $cliente->id,
                    'visita_id'  => $visita->id
                ], 201);
            });
        } catch (\Exception $e) {
            Log::error("Error al registrar primera visita: " . $e->getMessage());
            return response()->json(['message' => 'Error en el servidor: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Muestra el detalle de una visita verificando propiedad efectiva.
     */
    public function show(Request $request, $id)
    {
        try {
            $user = $request->user();
            $ownerId = method_exists($user, 'getEffectiveId') ? $user->getEffectiveId() : $user->id;

            $visita = Visita::with(['estado', 'cliente'])->findOrFail($id);

            if ($visita->user_id !== $ownerId) {
                return response()->json(['message' => 'No tienes permiso para ver esta bitácora.'], 403);
            }

            return response()->json($visita);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Visita no encontrada.'], 404);
        }
    }
}