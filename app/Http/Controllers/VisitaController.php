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
     * Listado de todas las visitas vinculadas al representante (dueño).
     * Soporta filtros por búsqueda, rango de fechas y resultado.
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $ownerId = method_exists($user, 'getEffectiveId') ? $user->getEffectiveId() : $user->id;

            $query = Visita::where('user_id', $ownerId);

            // REGLA: Si NO se pide el historial completo, filtrar solo por primeras visitas (1)
            // Si se envía el parámetro 'full_history', mostrará tanto 1 como 0.
            if (!$request->has('full_history')) {
                $query->where('es_primera_visita', 1);
            }

            $query->search($request->input('search'))
                  ->byDateRange($request->input('desde'), $request->input('hasta'))
                  ->byResultado($request->input('resultado'));

            if ($request->filled('estado_id')) {
                $query->where('estado_id', $request->estado_id);
            }

            $visitas = $query->with(['estado', 'cliente'])
                ->orderBy('id', 'desc') 
                ->paginate(15);

            return response()->json($visitas);

        } catch (\Exception $e) {
            Log::error("Error en VisitaController@index: " . $e->getMessage());
            return response()->json(['message' => 'Error al cargar el historial.'], 500);
        }
    }

    /**
     * Registro de una Primera Visita.
     * Crea un Prospecto (o Cliente directo) y registra la bitácora inicial.
     */
        public function storePrimeraVisita(Request $request)
    {
        // 1. VALIDACIÓN INICIAL DE CAMPOS
        $request->validate([
            'plantel.name'      => 'required|string|max:100',
            'plantel.rfc'       => 'required|string|max:50',
            'plantel.niveles'   => 'required|array|min:1',
            'plantel.direccion' => 'required|string',
            'plantel.estado_id' => 'required|exists:estados,id',
            'plantel.telefono'  => 'required|string',
            'plantel.email'     => 'required|email',
            'plantel.director'  => 'required|string',
            
            'visita.fecha'                => 'required|date',
            'visita.persona_entrevistada' => 'required|string',
            'visita.cargo'                => 'required|string',
            'visita.libros_interes'       => 'required|array',
            'visita.resultado_visita'     => 'required|in:seguimiento,compra,rechazo',
        ]);

        try {
            $rfc = strtoupper($request->input('plantel.rfc'));
            $name = $request->input('plantel.name');
            $email = $request->input('plantel.email');
            $phone = $request->input('plantel.telefono');

            // 2. VERIFICACIÓN DE DUPLICADOS (REGLA DE NEGOCIO CRÍTICA)
            // Buscamos cualquier cliente/prospecto que coincida en alguno de estos 4 campos
            $duplicado = Cliente::where('rfc', $rfc)
                ->orWhere('name', $name)
                ->orWhere('email', $email)
                ->orWhere('telefono', $phone)
                ->first();

            if ($duplicado) {
                $campoTrigger = '';
                if($duplicado->rfc === $rfc) $campoTrigger = "RFC ($rfc)";
                elseif($duplicado->name === $name) $campoTrigger = "Nombre ($name)";
                elseif($duplicado->email === $email) $campoTrigger = "Email";
                else $campoTrigger = "Teléfono";

                return response()->json([
                    'message' => "ACCIÓN BLOQUEADA: El plantel ya existe en el sistema bajo el registro de '{$duplicado->name}'. Coincidencia detectada en: $campoTrigger."
                ], 422);
            }

            return DB::transaction(function () use ($request, $rfc, $name) {
                $user = $request->user();
                $ownerId = method_exists($user, 'getEffectiveId') ? $user->getEffectiveId() : $user->id;

                $resultado = $request->input('visita.resultado_visita');
                $tipoFinal = ($resultado === 'compra') ? 'CLIENTE' : 'PROSPECTO';

                // 3. CREACIÓN DEL REGISTRO MAESTRO
                $cliente = Cliente::create([
                    'user_id'         => $ownerId,
                    'tipo'            => $tipoFinal,
                    'name'            => $name,
                    'rfc'             => $rfc,
                    'nivel_educativo' => implode(', ', $request->input('plantel.niveles')),
                    'contacto'        => $request->input('plantel.director'),
                    'telefono'        => $request->input('plantel.telefono'),
                    'email'           => $request->input('plantel.email'),
                    'direccion'       => $request->input('plantel.direccion'),
                    'estado_id'       => $request->input('plantel.estado_id'),
                    'latitud'         => $request->input('plantel.latitud'),
                    'longitud'        => $request->input('plantel.longitud'),
                    'status'          => 'activo'
                ]);

                // 4. REGISTRO DE BITÁCORA
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
                    'resultado_visita'        => $resultado,
                    'proxima_visita_estimada' => $request->input('visita.proxima_visita'),
                    'proxima_accion'          => $request->input('visita.proxima_accion') ?? 'visita',
                    'es_primera_visita'       => true,
                ]);

                return response()->json([
                    'message'   => "Registro de $tipoFinal exitoso.",
                    'visita_id' => $visita->id
                ], 201);
            });
        } catch (\Exception $e) {
            Log::error("Fallo en storePrimeraVisita: " . $e->getMessage());
            return response()->json(['message' => 'Error técnico al procesar el alta.'], 500);
        }
    }

    /**
     * Registro de una Visita Subsecuente (Seguimiento).
     * Actualiza el estatus del cliente (a CLIENTE o Inactivo) según el resultado.
     */
    public function storeSeguimiento(Request $request)
    {
        $validated = $request->validate([
            'cliente_id'           => 'required|exists:clientes,id',
            'fecha'                => 'required|date',
            'persona_entrevistada' => 'required|string',
            'cargo'                => 'required|string',
            'libros_interes'       => 'required', // Se recibe el objeto dual de materiales
            'comentarios'          => 'nullable|string',
            'resultado_visita'     => 'required|in:seguimiento,compra,rechazo',
            'proxima_visita'       => 'nullable|date',
            'proxima_accion'       => 'nullable|string'
        ]);

        try {
            return DB::transaction(function () use ($request, $validated) {
                $user = $request->user();
                $ownerId = method_exists($user, 'getEffectiveId') ? $user->getEffectiveId() : $user->id;

                $cliente = Cliente::findOrFail($validated['cliente_id']);
                $resultado = $validated['resultado_visita'];

                // Procesamos el JSON de libros si llega como cadena
                $libros = is_string($request->libros_interes) 
                          ? json_decode($request->libros_interes, true) 
                          : $request->libros_interes;

                // 1. CREACIÓN DEL SEGUIMIENTO EN BITÁCORA
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
                    'resultado_visita'        => $resultado,
                    'proxima_visita_estimada' => $validated['proxima_visita'],
                    'proxima_accion'          => $request->input('proxima_accion') ?? 'visita',
                    'es_primera_visita'       => false,
                ]);

                // 2. ACTUALIZACIÓN AUTOMÁTICA DEL REGISTRO MAESTRO
                if ($resultado === 'compra') {
                    // Conversión oficial de Prospecto a CLIENTE
                    $cliente->update(['tipo' => 'CLIENTE']);
                } elseif ($resultado === 'rechazo') {
                    // Marcamos como inactivo para sacarlo de las listas de seguimiento activo
                    $cliente->update(['status' => 'inactivo']);
                }

                return response()->json([
                    'message' => 'Seguimiento registrado y estatus de cuenta actualizado con éxito.',
                    'visita_id' => $visita->id
                ], 201);
            });
        } catch (\Exception $e) {
            Log::error("Fallo en storeSeguimiento: " . $e->getMessage());
            return response()->json(['message' => 'Error técnico al procesar el seguimiento.'], 500);
        }
    }

    /**
     * Consulta del detalle técnico de una bitácora.
     */
    public function show(Request $request, $id)
    {
        try {
            $user = $request->user();
            $ownerId = method_exists($user, 'getEffectiveId') ? $user->getEffectiveId() : $user->id;

            // Cargamos relaciones para el detalle visual
            $visita = Visita::with(['estado', 'cliente'])->findOrFail($id);

            // Verificación de seguridad de propiedad de datos
            if ($visita->user_id !== $ownerId) {
                return response()->json(['message' => 'No tiene autorización para ver esta bitácora.'], 403);
            }

            return response()->json($visita);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Bitácora no localizada.'], 404);
        }
    }

    /**
     * Edición de una visita de seguimiento (NO PRIMERA VISITA).
     * REGLA: Solo se permite una modificación por visita de seguimiento para mantener la integridad del historial. Las primeras visitas no se pueden editar, deben ser anuladas y recreadas si
     */

        public function update(Request $request, $id)
    {
        $visita = Visita::findOrFail($id);

        // REGLA: Las subsecuentes solo se editan una vez
        if (!$visita->es_primera_visita && $visita->modificaciones_realizadas >= 1) {
            return response()->json([
                'message' => 'Esta visita de seguimiento ya fue modificada una vez y se encuentra bloqueada.'
            ], 403);
        }

        $request->validate([
            'persona_entrevistada' => 'required|string',
            'cargo'                => 'required|string',
            'libros_interes'       => 'required',
            'comentarios'          => 'required|string|min:20',
            'motivo_cambio'        => 'required_if:es_primera_visita,1|string|min:10',
            'resultado_visita'     => 'required|in:seguimiento,compra,rechazo',
        ]);

        try {
            return DB::transaction(function () use ($visita, $request) {
                // 1. Guardar log de auditoría
                VisitaLog::create([
                    'visita_id' => $visita->id,
                    'user_id'   => Auth::id(),
                    'snapshot_anterior' => $visita->toArray(),
                    'motivo_cambio'     => strtoupper($request->motivo_cambio ?? 'AJUSTE ÚNICO DE SEGUIMIENTO')
                ]);

                // 2. Actualizar visita e incrementar contador
                $visita->update([
                    'persona_entrevistada'    => $request->persona_entrevistada,
                    'cargo'                   => $request->cargo,
                    'libros_interes'          => $request->libros_interes,
                    'comentarios'             => $request->comentarios,
                    'resultado_visita'        => $request->resultado_visita,
                    'proxima_visita_estimada' => $request->proxima_visita,
                    'modificaciones_realizadas' => $visita->modificaciones_realizadas + 1
                ]);

                return response()->json(['message' => 'Expediente actualizado y log generado.']);
            });
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al actualizar: ' . $e->getMessage()], 500);
        }
    }
}