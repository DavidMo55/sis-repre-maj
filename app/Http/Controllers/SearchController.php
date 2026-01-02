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
 * Obtiene la lista de estados ordenados alfabéticamente.
 */
        public function getEstados()
        {
            return response()->json(\App\Models\Estado::orderBy('estado', 'asc')->get());
        }
}