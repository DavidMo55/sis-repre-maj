<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Visita;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class VisitaController extends Controller
{
    /**
     * Listado general de visitas con filtros dinámicos.
     */
    public function index(Request $request)
    {
        try {
            // Iniciamos la consulta filtrando por el usuario autenticado
            $query = Visita::where('user_id', Auth::id());

            // Aplicamos los Scopes definidos en el Modelo Visita.php
            // Solo se ejecutan si el parámetro está presente en la request
            $query->search($request->query('search'))
                  ->byDateRange($request->query('desde'), $request->query('hasta'))
                  ->byResultado($request->query('resultado'));

            // Ordenamos por fecha más reciente y paginamos
            $visitas = $query->orderBy('fecha', 'desc')
                             ->paginate(15);

            // Importante: Retornamos el objeto de paginación completo
            return response()->json($visitas);

        } catch (\Exception $e) {
            Log::error("Error en VisitaController@index: " . $e->getMessage());
            return response()->json([
                'message' => 'Error al obtener el historial de visitas.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar detalles de una visita específica.
     */
    public function show($id)
    {
        try {
            $visita = Visita::with('estado')
                ->where('user_id', Auth::id())
                ->findOrFail($id);

            return response()->json($visita);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => "La visita con ID #{$id} no existe o no tienes permisos para verla."
            ], 404);
        } catch (\Exception $e) {
            Log::error("Error en VisitaController@show: " . $e->getMessage());
            return response()->json([
                'message' => 'Error interno al recuperar el detalle.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Registro de primera visita (Prospecto).
     */
    public function storePrimeraVisita(Request $request)
    {
        $validated = $request->validate([
            'plantel.name' => 'required|string',
            'plantel.nivel_educativo' => 'required|string',
            'plantel.direccion' => 'required|string',
            'plantel.estado_id' => 'nullable|exists:estados,id',
            'plantel.telefono' => 'required|string',
            'plantel.email' => 'required|email',
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
            $visita = Visita::create([
                'user_id' => Auth::id(),
                'nombre_plantel' => $validated['plantel']['name'],
                'nivel_educativo_plantel' => $validated['plantel']['nivel_educativo'],
                'direccion_plantel' => $validated['plantel']['direccion'],
                'estado_id' => $validated['plantel']['estado_id'] ?? null,
                'latitud' => $validated['plantel']['latitud'],
                'longitud' => $validated['plantel']['longitud'],
                'telefono_plantel' => $validated['plantel']['telefono'],
                'email_plantel' => $validated['plantel']['email'],
                'director_plantel' => $validated['plantel']['director'],
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

            return response()->json([
                'message' => 'Bitácora registrada correctamente.',
                'visita_id' => $visita->id
            ], 201);

        } catch (\Exception $e) {
            Log::error("Error en storePrimeraVisita: " . $e->getMessage());
            return response()->json(['message' => 'Error al guardar.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Registro de Visita de Seguimiento.
     */
    public function storeSeguimiento(Request $request)
    {
        $validated = $request->validate([
            'original_visita_id' => 'required|exists:visitas,id',
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
            $original = Visita::where('user_id', Auth::id())->findOrFail($validated['original_visita_id']);

            $nuevaVisita = Visita::create([
                'user_id' => Auth::id(),
                'cliente_id' => $original->cliente_id,
                'nombre_plantel' => $original->nombre_plantel,
                'nivel_educativo_plantel' => $original->nivel_educativo_plantel,
                'direccion_plantel' => $original->direccion_plantel,
                'estado_id' => $original->estado_id,
                'latitud' => $original->latitud,
                'longitud' => $original->longitud,
                'telefono_plantel' => $original->telefono_plantel,
                'email_plantel' => $original->email_plantel,
                'director_plantel' => $original->director_plantel,
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

            return response()->json(['message' => 'Seguimiento registrado.', 'visita_id' => $nuevaVisita->id], 201);

        } catch (\Exception $e) {
            Log::error("Error en storeSeguimiento: " . $e->getMessage());
            return response()->json(['message' => 'Error al procesar seguimiento.', 'error' => $e->getMessage()], 500);
        }
    }
}