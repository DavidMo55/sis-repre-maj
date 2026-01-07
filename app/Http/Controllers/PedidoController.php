<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Cliente; 
use App\Models\Pedido; 
use App\Models\PedidoDetalle; 
use App\Models\Libro;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PedidoController extends Controller
{
    /**
     * Listado de pedidos del usuario autenticado (Fix para el error Index).
     */
    public function index(Request $request)
    {
        try {
            $pedidos = Pedido::where('user_id', Auth::id())
                            ->with(['cliente', 'detalles.libro']) 
                            ->orderBy('created_at', 'desc')
                            ->paginate(10);
            
            return response()->json($pedidos);

        } catch (\Exception $e) {
             return response()->json([
                'message' => 'Error al listar pedidos.', 
                'error_detail' => $e->getMessage() 
             ], 500);
        }
    }

    /**
     * Búsqueda de libros con lógica de filtrado por tipo de pedido.
     */
    public function searchLibros(Request $request)
    {
        $query = $request->input('query');
        $tipoPedido = $request->input('tipo_pedido'); // 'normal' o 'promocion'

        if (empty($query) || strlen($query) < 3) {
            return response()->json([]);
        }

        $search = Libro::where('estado', 'activo')
                        ->where('titulo', 'like', "%{$query}%");

        // Regla de negocio para tipos de material
        if ($tipoPedido === 'promocion') {
            $search->whereIn('type', ['promocion', 'digital']);
        } else {
            $search->whereIn('type', ['venta', 'digital']);
        }

        return response()->json($search->limit(10)->get());
    }

    /**
     * Detalle de un pedido específico.
     */
    public function show($id)
    {
        $pedido = Pedido::with(['cliente', 'detalles.libro'])
                        ->where('id', $id)
                        ->first();

        if (!$pedido) {
            return response()->json(['message' => 'Pedido no encontrado.'], 404);
        }
        
        if ($pedido->user_id !== Auth::id()) {
            return response()->json(['message' => 'Acceso denegado.'], 403);
        }

        return response()->json($pedido);
    }

    /**
     * Almacenamiento del pedido con todos los nuevos campos.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'client_name' => 'required|string', 
            'tipo_pedido' => 'required|in:normal,promocion',
            'prioridad' => 'required|in:baja,media,alta',
            'receiver_type' => 'required|in:cliente,nuevo',
            'delivery_option' => 'required|in:recoleccion,paqueteria,none',
            'paqueteria_nombre' => 'nullable|required_if:delivery_option,paqueteria|string',
            'comments' => 'nullable|string|max:1000',
            
            'receiver_data.nombre' => 'nullable|required_if:receiver_type,nuevo|string',
            'receiver_data.telefono' => 'nullable|required_if:receiver_type,nuevo|string',
            'receiver_data.correo' => 'nullable|required_if:receiver_type,nuevo|email',
            'receiver_data.direccion' => 'nullable|string',
            
            'items' => 'required|array|min:1',
            'items.*.libro_id' => 'required|exists:libros,id',
            'items.*.bookName' => 'required|string',
            'items.*.type' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        $cliente = Cliente::where('name', $validatedData['client_name'])->first();

        if (!$cliente) {
             return response()->json(['message' => 'El cliente seleccionado no existe.'], 400);
        }

        try {
            DB::beginTransaction();

            $pedido = new Pedido();
            $pedido->user_id = Auth::id(); 
            $pedido->cliente_id = $cliente->id;
            
            // Asignación de campos de negocio
            $pedido->tipo_pedido = $validatedData['tipo_pedido'];
            $pedido->prioridad = $validatedData['prioridad'];
            $pedido->paqueteria_nombre = $validatedData['paqueteria_nombre'] ?? null;
            $pedido->receiver_type = $validatedData['receiver_type'];
            $pedido->delivery_option = $validatedData['delivery_option'];
            $pedido->comments = $validatedData['comments'];

            if ($validatedData['receiver_type'] === 'nuevo') {
                $rd = $validatedData['receiver_data'];
                $pedido->receiver_nombre = $rd['nombre'];
                $pedido->receiver_telefono = $rd['telefono'];
                $pedido->receiver_correo = $rd['correo'];
                $pedido->delivery_address = $rd['direccion'] ?? null;
            } else {
                 $pedido->delivery_address = $cliente->direccion;
            }

            $pedido->save();
            
            // Generación de referencia PED + FECHA + ID
            $date_format = Carbon::now()->format('dmy');
            $referencia = "PED{$date_format}{$pedido->id}"; 
            $pedido->numero_referencia = $referencia;
            $pedido->save(); 

            foreach ($validatedData['items'] as $item) {
                PedidoDetalle::create([
                    'pedido_id' => $pedido->id,
                    'libro_id' => $item['libro_id'],
                    'tipo_licencia' => $item['type'],
                    'cantidad' => $item['quantity'],
                    'precio_unitario' => $item['price'],
                    'costo_total' => $item['quantity'] * $item['price']
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Pedido generado correctamente.',
                'order_id' => $referencia
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al guardar el pedido: ' . $e->getMessage()], 500);
        }
    }
}