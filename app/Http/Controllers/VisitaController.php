<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Visita;
use App\Models\Cliente;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class VisitaController extends Controller
{
    /**
     * Listado de todas las visitas del representante o sus delegados.
     * Soporta filtros de búsqueda por nombre, fechas y resultado.
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $ownerId = method_exists($user, 'getEffectiveId') ? $user->getEffectiveId() : $user->id;

            $query = Visita::where('user_id', $ownerId);

            // Aplicar Scopes de búsqueda definidos en el Modelo
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
            Log::error("Error en VisitaController@index: " . $e->getMessage());
            return response()->json(['message' => 'Error al cargar el historial.'], 500);
        }
    }

    /**
     * Almacena una "Primera Visita". 
     * Crea un registro en la tabla 'clientes' (como PROSPECTO) y la bitácora en 'visitas'.
     */
    public function storePrimeraVisita(Request $request)
    {
        $validated = $request->validate([
            // Datos del Plantel
            'plantel.name'      => 'required|string|max:100',
            'plantel.rfc'       => 'required|string|max:50',
            'plantel.niveles'   => 'required|array|min:1',
            'plantel.direccion' => 'required|string',
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
            'visita.libros_interes'       => 'required|array', // Estructura { interes: [], entregado: [] }
            'visita.comentarios'          => 'nullable|string',
            'visita.resultado_visita'     => 'required|in:seguimiento,compra,rechazo',
            'visita.proxima_visita'       => 'nullable|date',
            'visita.proxima_accion'       => 'nullable|string'
        ]);

        try {
            return DB::transaction(function () use ($request) {
                $user = $request->user();
                $ownerId = method_exists($user, 'getEffectiveId') ? $user->getEffectiveId() : $user->id;

                // 1. Crear o Actualizar el Prospecto en la tabla maestra de Clientes
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

                // 2. Registrar la Bitácora de la Visita
                $visita = Visita::create([
                    'user_id'                 => $ownerId,
                    'cliente_id'              => $cliente->id,
                    'nombre_plantel'          => $cliente->name,
                    'rfc_plantel'             => $cliente->rfc,
                    'nivel_educativo_plantel' => $cliente->nivel_educativo,
                    'direccion_plantel'       => $cliente->direccion,
                    'estado_id'               => $cliente->estado_id,
                    'latitud'                 => $cliente->latitud,
                    'longitud'                => $cliente->longitud,
                    'telefono_plantel'        => $cliente->telefono,
                    'email_plantel'           => $cliente->email,
                    'director_plantel'        => $cliente->contacto,
                    'fecha'                   => $request->input('visita.fecha'),
                    'persona_entrevistada'    => $request->input('visita.persona_entrevistada'),
                    'cargo'                   => $request->input('visita.cargo'),
                    'libros_interes'          => $request->input('visita.libros_interes'),
                    'comentarios'             => $request->input('visita.comentarios'),
                    'resultado_visita'        => $request->input('visita.resultado_visita'),
                    'proxima_visita_estimada' => $request->input('visita.proxima_visita'),
                    'proxima_accion'          => $request->input('visita.proxima_accion') ?? 'visita',
                    'es_primera_visita'       => true,
                ]);

                return response()->json([
                    'message'   => 'Alta de prospecto y visita exitosa.',
                    'visita_id' => $visita->id
                ], 201);
            });
        } catch (\Exception $e) {
            Log::error("Error en storePrimeraVisita: " . $e->getMessage());
            return response()->json(['message' => 'Error al registrar: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Almacena una "Visita Subsecuente" (Seguimiento).
     * Se vincula a un cliente/prospecto ya existente.
     */
    public function storeSeguimiento(Request $request)
    {
        $validated = $request->validate([
            'cliente_id'           => 'required|exists:clientes,id',
            'fecha'                => 'required|date',
            'persona_entrevistada' => 'required|string',
            'cargo'                => 'required|string',
            'libros_interes'       => 'required', // Puede venir como string JSON o array según el front
            'comentarios'          => 'nullable|string',
            'resultado_visita'     => 'required|in:seguimiento,compra,rechazo',
            'proxima_visita'       => 'nullable|date',
        ]);

        try {
            return DB::transaction(function () use ($request, $validated) {
                $user = $request->user();
                $ownerId = method_exists($user, 'getEffectiveId') ? $user->getEffectiveId() : $user->id;

                $cliente = Cliente::findOrFail($validated['cliente_id']);

                // Decodificar libros si vienen como string desde el front
                $libros = is_string($request->libros_interes) 
                          ? json_decode($request->libros_interes, true) 
                          : $request->libros_interes;

                // 1. Crear el registro de seguimiento
                $visita = Visita::create([
                    'user_id'                 => $ownerId,
                    'cliente_id'              => $cliente->id,
                    'nombre_plantel'          => $cliente->name,
                    'rfc_plantel'             => $cliente->rfc,
                    'nivel_educativo_plantel' => $cliente->nivel_educativo,
                    'direccion_plantel'       => $cliente->direccion,
                    'estado_id'               => $cliente->estado_id,
                    'fecha'                   => $validated['fecha'],
                    'persona_entrevistada'    => $validated['persona_entrevistada'],
                    'cargo'                   => $validated['cargo'],
                    'libros_interes'          => $libros,
                    'comentarios'             => $validated['comentarios'],
                    'resultado_visita'        => $validated['resultado_visita'],
                    'proxima_visita_estimada' => $validated['proxima_visita'],
                    'es_primera_visita'       => false,
                ]);

                // 2. Opcional: Actualizar el estatus del cliente si la resolución fue 'rechazo' o 'compra'
                if ($validated['resultado_visita'] === 'rechazo') {
                    $cliente->update(['status' => 'inactivo']);
                }

                return response()->json([
                    'message' => 'Seguimiento registrado correctamente.',
                    'id'      => $visita->id
                ], 201);
            });
        } catch (\Exception $e) {
            Log::error("Error en storeSeguimiento: " . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor.'], 500);
        }
    }

    /**
     * Muestra el detalle de una visita específica.
     */
    public function show(Request $request, $id)
    {
        try {
            $user = $request->user();
            $ownerId = method_exists($user, 'getEffectiveId') ? $user->getEffectiveId() : $user->id;

            // Cargamos relaciones para el detalle técnico
            $visita = Visita::with(['estado', 'cliente'])->findOrFail($id);

            // Verificación de seguridad: solo el dueño o sus delegados ven la bitácora
            if ($visita->user_id !== $ownerId) {
                return response()->json(['message' => 'Acceso denegado a esta bitácora.'], 403);
            }

            return response()->json($visita);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Bitácora no encontrada.'], 404);
        }
    }
}