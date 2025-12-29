<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Visita;
use App\Models\Cliente;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VisitaController extends Controller
{
    /**
     * Listado general de visitas realizadas por el representante.
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
     * Registro de primera visita: Crea el plantel (prospecto) y la visita.
     */
    public function storePrimeraVisita(Request $request)
    {
        $validatedData = $request->validate([
            'plantel.name' => 'required|string|unique:clientes,name',
            'plantel.tipo' => 'required|in:PROSPECTO',
            'plantel.nivel_educativo' => 'required|string',
            'plantel.direccion' => 'required|string',
            'plantel.estado_id' => 'required|exists:estados,id',
            'plantel.telefono' => 'required|string',
            'plantel.tel_fijo' => 'nullable|string',
            'plantel.email' => 'required|email|unique:clientes,email',
            'plantel.web' => 'nullable|url',
            'plantel.director' => 'required|string',
            'plantel.horario_contacto' => 'nullable|string',

            'visita.fecha' => 'required|date',
            'visita.persona_entrevistada' => 'required|string',
            'visita.cargo' => 'required|string',
            'visita.libros_interes' => 'nullable|string',
            'visita.material_entregado' => 'required|boolean',
            'visita.comentarios' => 'nullable|string',
            'visita.proxima_visita' => 'nullable|date|after:visita.fecha',
        ]);

        try {
            DB::beginTransaction();

            // 1. Crear el Plantel como Prospecto
            $cliente = Cliente::create([
                'name' => $request->plantel['name'],
                'tipo' => 'PROSPECTO',
                'contacto' => $request->plantel['director'],
                'email' => $request->plantel['email'],
                'telefono' => $request->plantel['telefono'],
                'tel_oficina' => $request->plantel['tel_fijo'],
                'direccion' => $request->plantel['direccion'],
                'estado_id' => $request->plantel['estado_id'],
                'user_id' => Auth::id(),
                'moneda_id' => 1, 
                'status' => 'activo'
            ]);

            $visita = Visita::create([
                'user_id' => Auth::id(),
                'cliente_id' => $cliente->id,
                'fecha' => $request->visita['fecha'],
                'persona_entrevistada' => $request->visita['persona_entrevistada'],
                'cargo' => $request->visita['cargo'],
                'libros_interes' => $request->visita['libros_interes'],
                'material_entregado' => $request->visita['material_entregado'],
                'comentarios' => $request->visita['comentarios'],
                'proxima_visita_estimada' => $request->visita['proxima_visita'],
                'es_primera_visita' => true
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Plantel registrado y primera visita guardada correctamente.',
                'cliente_id' => $cliente->id
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al registrar la visita: ' . $e->getMessage()], 500);
        }
    }
}