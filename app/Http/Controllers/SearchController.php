<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cliente;
use App\Models\Libro;

class SearchController extends Controller
{


    
    /**
     * Busca clientes (planteles) por nombre que sean CLIENTE o DISTRIBUIDOR.
     */
    public function searchClientes(Request $request)
    {
        $query = $request->input('query');
        $includeProspectos = $request->boolean('include_prospectos', false);

        if (empty($query)) {
            return response()->json([]);
        }

        $search = Cliente::select('id', 'name', 'tipo', 'direccion', 'contacto')
            ->where('name', 'like', '%' . $query . '%');

        if ($includeProspectos) {
            $search->whereIn('tipo', ['CLIENTE', 'DISTRIBUIDOR', 'PROSPECTO']);
        } else {
            $search->whereIn('tipo', ['CLIENTE', 'DISTRIBUIDOR']);
        }

        $clientes = $search->limit(10)->get();

        return response()->json($clientes);
    }

    /**
     * Busca libros por título para el detalle del pedido.
     */
    public function searchLibros(Request $request)
    {
        $query = $request->input('query');

        if (empty($query)) {
            return response()->json([]);
        }

        $libros = Libro::select('id', 'titulo', 'ISBN', 'editorial')
            ->where('titulo', 'like', '%' . $query . '%')
            ->where('estado', 'activo')
            ->limit(10) // Limitar resultados para autocompletado
            ->get();

        return response()->json($libros);
    }

    /**
     * Busca específicamente en la tabla de visitas para identificar prospectos.
     */
    public function searchProspectos(Request $request)
    {
        $query = $request->input('query');

        if (empty($query) || strlen($query) < 2) {
            return response()->json([]);
        }

        // Buscamos en la tabla de visitas
        // Nota: Si quieres que un representante vea prospectos de otros, quita el filtro de user_id
        $prospectos = \App\Models\Visita::where('nombre_plantel', 'like', '%' . $query . '%')
            ->where('user_id', \Illuminate\Support\Facades\Auth::id()) 
            ->select([
                'id', 
                'nombre_plantel', 
                'direccion_plantel', 
                'director_plantel', 
                'cliente_id', 
                'es_primera_visita', 
                'fecha', 
                'persona_entrevistada',
                'cargo'
            ])
            ->orderBy('fecha', 'desc')
            ->get()
            // Agrupamos por nombre para no mostrar la misma escuela 20 veces si tiene mucho historial
            ->unique('nombre_plantel')
            ->values();

        return response()->json($prospectos);
    }

/**
 * Obtiene la lista de estados ordenados alfabéticamente.
 */
        public function getEstados()
        {
            return response()->json(\App\Models\Estado::orderBy('estado', 'asc')->get());
        }
}