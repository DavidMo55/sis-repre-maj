<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cliente;
use App\Models\Libro;
use App\Models\Estado;
use Illuminate\Support\Facades\Log;

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
                    'email'
                )
                ->where('name', 'like', "%{$query}%")
                ->whereIn('tipo', ['CLIENTE', 'DISTRIBUIDOR']) // Excluye PROSPECTOS para pedidos de venta
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
     * Buscador de Libros.
     * Devuelve el campo 'type' (digital/fisico) para validar licencias en el Front.
     */
    public function searchLibros(Request $request)
    {
        $query = $request->input('query');

        if (empty($query) || strlen($query) < 3) {
            return response()->json([]);
        }

        try {
            $libros = Libro::select('id', 'titulo', 'ISBN', 'editorial', 'type')
                ->where('titulo', 'like', "%{$query}%")
                ->where('estado', 'activo')
                ->limit(15)
                ->get();

            return response()->json($libros);

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
}