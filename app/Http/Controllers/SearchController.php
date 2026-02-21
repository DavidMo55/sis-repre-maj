<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cliente;
use App\Models\Libro;
use App\Models\Estado;
use App\Models\PedidoReceptor;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SearchController extends Controller
{
    /**
     * Buscador de Instituciones (Clientes, Distribuidores y Prospectos).
     * Se utiliza tanto en Pedidos como en el módulo de Visitas.
     */
    public function searchClientes(Request $request)
    {   
        $query = $request->input('query');
        // Permite incluir prospectos mediante un parámetro (útil para Visitas)
        $includeProspectos = $request->boolean('include_prospectos');

        if (empty($query) || strlen($query) < 3) {
            return response()->json([]);
        }

        try {
            $builder = Cliente::select(
                    'id', 'name', 'tipo', 'direccion', 'contacto', 'telefono', 'email',
                    'rfc', 'regimen_fiscal', 'cp', 'municipio', 'colonia', 'calle_num', 'estado_id',
                    'latitud', 'longitud', 'nivel_educativo'
                )
                ->where('name', 'like', "%{$query}%")
                ->where('status', 'activo');

            // Si no se especifica incluir prospectos, filtramos por tipos de venta
            if (!$includeProspectos) {
                $builder->whereIn('tipo', ['CLIENTE', 'DISTRIBUIDOR']);
            }

            return response()->json($builder->limit(10)->get());

        } catch (\Exception $e) {
            Log::error("Error en SearchController@searchClientes: " . $e->getMessage());
            return response()->json(['message' => 'Error al buscar instituciones'], 500);
        }
    }

    /**
     * Buscador de Materiales Educativos (Libros).
     * Permite filtrado dinámico por Serie y Estatus.
     */
    public function searchLibros(Request $request)
    {
        $query = $request->input('query');
        $serieId = $request->input('serie_id');

        if (empty($query) || strlen($query) < 3) {
            return response()->json([]);
        }

        try {
            $builder = Libro::select('id', 'titulo', 'ISBN', 'editorial', 'type', 'serie_id')
                ->where('titulo', 'like', "%{$query}%")
                ->where('estado', 'activo');

            // Filtrado opcional por serie
            if ($serieId && $serieId !== 'otro') {
                $builder->where('serie_id', $serieId);
            }

            return response()->json($builder->limit(15)->get());

        } catch (\Exception $e) {
            Log::error("Error en SearchController@searchLibros: " . $e->getMessage());
            return response()->json(['message' => 'Error al buscar libros'], 500);
        }
    }

    /**
     * Buscador de Agenda de Receptores con Aislamiento de Privacidad.
     * REGLA: Un representante solo puede ver y buscar sus propios receptores.
     */
    public function searchReceptores(Request $request)
    {
        $query = $request->input('query');
        $user = Auth::user();
        
        // Soporte para delegados: Identificamos quién es el dueño efectivo de la cuenta
        $ownerId = method_exists($user, 'getEffectiveId') ? $user->getEffectiveId() : $user->id;

        if (empty($query) || strlen($query) < 3) {
            return response()->json([]);
        }

        try {
            // Aislamiento: El filtro 'user_id' impide ver registros ajenos
            $receptores = PedidoReceptor::where('user_id', $ownerId)
                ->where(function($q) use ($query) {
                    $q->where('rfc', 'like', "%{$query}%")
                      ->orWhere('nombre', 'like', "%{$query}%")
                      ->orWhere('correo', 'like', "%{$query}%")
                      ->orWhere('telefono', 'like', "%{$query}%");
                })
                ->limit(10)
                ->get();

            return response()->json($receptores);
        } catch (\Exception $e) {
            Log::error("Error en SearchController@searchReceptores: " . $e->getMessage());
            return response()->json(['message' => 'Error en búsqueda de agenda'], 500);
        }
    }

    /**
     * Validación de Unicidad Global de Receptores (RFC, Correo, Teléfono, Nombre).
     * REGLA: Busca en toda la base de datos para prevenir duplicados globales y 
     * marca el registro como privado si pertenece a otro representante.
     */
    public function checkRfcUniqueness(Request $request)
    {
        $rfc = $request->query('rfc');
        $correo = $request->query('correo');
        $telefono = $request->query('telefono');
        $name = $request->query('nombre');
        
        $user = Auth::user();
        $ownerId = method_exists($user, 'getEffectiveId') ? $user->getEffectiveId() : $user->id;

        // Cambiamos el modelo a Cliente porque es lo que estamos validando en la Visita
        $query = Cliente::query();

        if ($rfc) $query->where('rfc', strtoupper($rfc));
        elseif ($correo) $query->where('email', strtolower($correo));
        elseif ($telefono) $query->where('telefono', $telefono);
        elseif ($name) $query->where('name', strtoupper($name));
        else return response()->json(['status' => 'error', 'message' => 'Sin datos'], 400);

        $existente = $query->first();

        if (!$existente) {
            return response()->json(['status' => 'success', 'available' => true]);
        }

        $isPrivate = ($existente->user_id !== $ownerId);

        return response()->json([
            'status' => 'conflict',
            'available' => false,
            'is_private' => $isPrivate,
            'id' => $existente->id,
            'nombre' => $existente->name,
            'message' => $isPrivate 
                ? 'ESTE DATO PERTENECE A OTRO REPRESENTANTE Y NO PUEDE SER DUPLICADO.' 
                : 'YA TIENES REGISTRADO ESTE PLANTEL EN TU CARTERA.'
        ]);
    }

    /**
     * Catálogos base para formularios (Niveles, Series y Estados).
     */
    public function getNiveles()
    {
        try {
            return response()->json(DB::table('niveles_educativos')->select('id', 'nombre')->get());
        } catch (\Exception $e) {
            return response()->json([], 500);
        }
    }

    public function getSeries()
    {
        try {
            return response()->json(DB::table('series')->select('id', 'serie as nombre', 'nivel_educativo_id')->get());
        } catch (\Exception $e) {
            return response()->json([], 500);
        }
    }

    public function getEstados()
    {
        try {
            return response()->json(Estado::orderBy('estado', 'asc')->get());
        } catch (\Exception $e) {
            return response()->json([], 500);
        }
    }
}