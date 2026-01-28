<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cliente;
use App\Models\Libro;
use App\Models\Estado;
use App\Models\PedidoReceptor;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    /**
     * Buscador de Clientes y Distribuidores para Pedidos.
     * Filtra estrictamente por tipos autorizados para venta y estatus activo.
     */
    public function searchClientes(Request $request)
{
    $query = $request->input('query');

    if (empty($query) || strlen($query) < 3) {
        return response()->json([]);
    }

    try {
        $clientes = Cliente::select(
                'id', 
                'name', 
                'tipo', 
                'direccion', 
                'contacto', 
                'telefono', 
                'email',
                'rfc',            // <--- AGREGADO
                'regimen_fiscal', // <--- AGREGADO
                'cp',             // <--- AGREGADO
                'municipio',      // <--- AGREGADO
                'colonia',        // <--- AGREGADO
                'calle_num'       // <--- AGREGADO
            )
            ->where('name', 'like', "%{$query}%")
            ->whereIn('tipo', ['CLIENTE', 'DISTRIBUIDOR'])
            ->where('status', 'activo')
            ->limit(10)
            ->get();

        return response()->json($clientes);

    } catch (\Exception $e) {
        Log::error("Error en SearchController@searchClientes: " . $e->getMessage());
        return response()->json(['message' => 'Error al buscar clientes'], 500);
    }
}

    /**
     * Buscador de Prospectos para el módulo de Visitas.
     * Permite encontrar planteles que aún no son clientes formales.
     */
    public function searchProspectos(Request $request)
    {
        $query = $request->input('query');

        if (empty($query) || strlen($query) < 3) {
            return response()->json([]);
        }

        $prospectos = Cliente::select('id', 'name', 'direccion', 'contacto', 'tipo')
            ->where('name', 'like', "%{$query}%")
            ->limit(10)
            ->get();

        return response()->json($prospectos);
    }

     /**
     * Obtener listado de Niveles Educativos desde la nueva tabla.
     */
    public function getNiveles()
    {
        try {
            // Estructura: id, nombre
            return response()->json(DB::table('niveles_educativos')->select('id', 'nombre')->get());
        } catch (\Exception $e) {
            return response()->json([], 500);
        }
    }

    /**
     * Obtener listado de Series vinculadas a niveles educativos.
     */
    public function getSeries()
    {
        try {
            // Estructura: id, serie (nombre), nivel_educativo_id
            return response()->json(DB::table('series')->select('id', 'serie as nombre', 'nivel_educativo_id')->get());
        } catch (\Exception $e) {
            return response()->json([], 500);
        }
    }

    /**
     * Buscador de Libros filtrado por Serie.
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

            // Filtrado opcional por serie si no es "otro"
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
     * Obtener listado de Estados para formularios de direcciones.
     */
    public function getEstados()
    {
        try {
            return response()->json(Estado::orderBy('estado', 'asc')->get());
        } catch (\Exception $e) {
            return response()->json([], 500);
        }
    }


/**
 * Verifica si un RFC, Correo o Teléfono ya existe en la tabla pedido_receptores.
 * Se usa para prevenir duplicados exactos.
 */
public function searchReceptorByRFC(Request $request)
{
    // Obtenemos los posibles parámetros
    $rfc = $request->query('rfc');
    $correo = $request->query('correo');
    $telefono = $request->query('telefono');

    $query = PedidoReceptor::query();

    // Regla: Si se envía uno, buscamos por ese específicamente
    if ($rfc) {
        $query->where('rfc', strtoupper($rfc));
    } elseif ($correo) {
        $query->where('correo', strtolower($correo));
    } elseif ($telefono) {
        $query->where('telefono', $telefono);
    } else {
        return response()->json([]);
    }

    // Retornamos el primero que encuentre (orden reciente)
    $receptor = $query->orderBy('created_at', 'desc')->first();

    if (!$receptor) {
        return response()->json(['message' => 'Disponible'], 404);
    }

    return response()->json($receptor);
}


}