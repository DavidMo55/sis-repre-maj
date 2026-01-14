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
use Illuminate\Support\Facades\Log;

class PedidoController extends Controller
{
    /**
     * Listado de pedidos con soporte para delegados.
     */
    public function index(Request $request)
    {
        try {
            // Obtenemos el ID del representante (dueño de los datos)
            $user = $request->user();
            $ownerId = method_exists($user, 'getEffectiveId') ? $user->getEffectiveId() : $user->id;

            $pedidos = Pedido::where('user_id', $ownerId)
                            ->with(['cliente', 'detalles.libro']) 
                            ->orderBy('created_at', 'desc')
                            ->paginate(10);
            
            return response()->json($pedidos);

        } catch (\Exception $e) {
            Log::error("Error en PedidoController@index: " . $e->getMessage());
            return response()->json([
                'message' => 'Error al listar pedidos.', 
                'error_detail' => $e->getMessage() 
            ], 500);
        }
    }

    /**
     * Búsqueda de libros general.
     */
    public function searchLibros(Request $request)
    {
        $query = $request->input('query');

        if (empty($query) || strlen($query) < 3) {
            return response()->json([]);
        }

        $libros = Libro::where('estado', 'activo')
                        ->where('titulo', 'like', "%{$query}%")
                        ->select('id', 'titulo', 'type', 'ISBN', 'editorial')
                        ->limit(10)
                        ->get();

        return response()->json($libros);
    }

    /**
     * Detalle de un pedido verificando propiedad efectiva.
     */
    public function show(Request $request, $id)
    {
        try {
            $user = $request->user();
            $ownerId = method_exists($user, 'getEffectiveId') ? $user->getEffectiveId() : $user->id;

            $pedido = Pedido::with(['cliente', 'detalles.libro'])
                            ->where('id', $id)
                            ->where('user_id', $ownerId)
                            ->firstOrFail();

            return response()->json($pedido);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Pedido no encontrado o acceso denegado.'], 404);
        }
    }

    /**
     * Almacenamiento del pedido con lógica colaborativa (Delegados).
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'client_id' => 'required|exists:clientes,id',
            'prioridad' => 'required|in:baja,media,alta',
            'receiverType' => 'required|in:cliente,nuevo',
            'logistics.deliveryOption' => 'required|in:recoleccion,paqueteria,none',
            'logistics.paqueteria_nombre' => 'nullable|required_if:logistics.deliveryOption,paqueteria|string',
            'comments' => 'nullable|string|max:1000',
            
            'receiver.nombre' => 'nullable|required_if:receiverType,nuevo|string',
            'receiver.telefono' => 'nullable|required_if:receiverType,nuevo|string',
            'receiver.correo' => 'nullable|required_if:receiverType,nuevo|email',
            'receiver.direccion' => 'nullable|string',
            
            'items' => 'required|array|min:1',
            'items.*.libro_id' => 'required|exists:libros,id',
            'items.*.tipo_material' => 'required|in:promocion,venta',
            'items.*.sub_type' => 'required|string', 
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        try {
            return DB::transaction(function () use ($validatedData, $request) {
                
                $user = $request->user();
                $ownerId = method_exists($user, 'getEffectiveId') ? $user->getEffectiveId() : $user->id;

                $pedido = new Pedido();
                $pedido->user_id = $ownerId; // El pedido pertenece al Representante
                $pedido->cliente_id = $validatedData['client_id'];
                $pedido->prioridad = $validatedData['prioridad'];
                $pedido->delivery_option = $validatedData['logistics']['deliveryOption'];
                $pedido->paqueteria_nombre = $validatedData['logistics']['paqueteria_nombre'] ?? null;
                $pedido->receiver_type = $validatedData['receiverType'];
                $pedido->comments = $validatedData['comments'];
                $pedido->status = 'PENDIENTE';

                if ($validatedData['receiverType'] === 'nuevo') {
                    $rd = $validatedData['receiver'];
                    $pedido->receiver_nombre = $rd['nombre'];
                    $pedido->receiver_telefono = $rd['telefono'];
                    $pedido->receiver_correo = $rd['correo'];
                    $pedido->delivery_address = $rd['direccion'] ?? null;
                } else {
                    $cliente = Cliente::find($validatedData['client_id']);
                    $pedido->delivery_address = $cliente->direccion;
                }

                $pedido->save();
                
                $referencia = "PED" . Carbon::now()->format('dmy') . $pedido->id; 
                $pedido->numero_referencia = $referencia;
                $pedido->save(); 

                foreach ($validatedData['items'] as $item) {
                    $precioFinal = ($item['tipo_material'] === 'promocion') ? 0 : $item['price'];

                    PedidoDetalle::create([
                        'pedido_id' => $pedido->id,
                        'libro_id' => $item['libro_id'],
                        'tipo' => $item['tipo_material'], 
                        'tipo_licencia' => $item['sub_type'],
                        'cantidad' => $item['quantity'],
                        'precio_unitario' => $precioFinal,
                        'costo_total' => $item['quantity'] * $precioFinal
                    ]);
                }

                return response()->json([
                    'message' => 'Pedido generado correctamente.',
                    'order_id' => $referencia
                ], 201);
            });

        } catch (\Exception $e) {
            Log::error("Error al guardar pedido: " . $e->getMessage());
            return response()->json(['message' => 'Error al guardar el pedido: ' . $e->getMessage()], 500);
        }
    }
}