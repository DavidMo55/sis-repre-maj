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
    
    public function index(Request $request)
    {
        try {
            $pedidos = Pedido::where('user_id', Auth::id())
                            ->with(['cliente', 'detalles.libro']) 
                            ->orderBy('created_at', 'desc')
                            ->paginate(10);
            
            return response()->json($pedidos);

        } catch (\Exception $e) {
             \Log::error('Error al listar pedidos:', ['exception' => $e->getMessage()]);
             return response()->json([
                'message' => 'Error de Base de Datos/Modelo al listar pedidos. Verifica los logs.', 
                'error_detail' => $e->getMessage() 
             ], 500);
        }
    }
    
    
    public function show($id)
    {
        $pedido = Pedido::with(['cliente', 'detalles.libro'])
                        ->where('id', $id)
                        ->first();

        if (!$pedido) {
            return response()->json(['message' => 'Pedido no encontrado.'], 404);
        }
        
        if ($pedido->user_id !== Auth::id()) {
            return response()->json(['message' => 'Acceso denegado. Este pedido no te pertenece.'], 403);
        }

        return response()->json($pedido);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'client_name' => 'required|string', 
            'receiver_type' => 'required|in:cliente,nuevo',
            'delivery_option' => 'required|in:recoleccion,paqueteria,none',
            'comments' => 'nullable|string|max:1000',
            
            'receiver_data.nombre' => 'nullable|required_if:receiver_type,nuevo|string',
            'receiver_data.telefono' => 'nullable|required_if:receiver_type,nuevo|string',
            'receiver_data.correo' => 'nullable|required_if:receiver_type,nuevo|email',
            'receiver_data.direccion' => 'nullable|string',
            'receiver_data.rfc' => 'nullable|string|max:50',
            
            'items' => 'required|array|min:1',
            'items.*.bookName' => 'required|string',
            'items.*.type' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        $cliente = Cliente::where('name', $validatedData['client_name'])
                            ->whereIn('tipo', ['CLIENTE', 'DISTRIBUIDOR'])
                            ->first();

        if (!$cliente) {
             return response()->json(['message' => 'Cliente no encontrado o no tiene estatus de CLIENTE/DISTRIBUIDOR para realizar pedidos.'], 400);
        }

        try {
            DB::beginTransaction();

            $pedido = new Pedido();
            $pedido->user_id = Auth::id(); 
            $pedido->cliente_id = $cliente->id;
            $pedido->receiver_type = $validatedData['receiver_type'];
            $pedido->delivery_option = $validatedData['delivery_option'];
            $pedido->comments = $validatedData['comments'];

            if ($validatedData['receiver_type'] === 'nuevo') {
                $receiverData = $validatedData['receiver_data'];
                $pedido->receiver_nombre = $receiverData['nombre'];
                $pedido->receiver_rfc = $receiverData['rfc'] ?? null;
                $pedido->receiver_telefono = $receiverData['telefono'];
                $pedido->receiver_correo = $receiverData['correo'];
                $pedido->delivery_address = $receiverData['direccion'] ?? null;
            } else {
                 $pedido->delivery_address = $cliente->direccion;
            }

            $pedido->save();
            
            $date_format = Carbon::now()->format('dmy');
            $referencia = "PED{$date_format}{$pedido->id}"; 
            
            $pedido->numero_referencia = $referencia;
            $pedido->save(); 

            foreach ($validatedData['items'] as $item) {
                $libro = DB::table('libros')->where('titulo', $item['bookName'])->first();

                if (!$libro) {
                    throw new \Exception("El libro con título: '{$item['bookName']}' no se encontró en el catálogo.");
                }
                
                $costoTotal = $item['quantity'] * $item['price'];

                $detalle = new PedidoDetalle();
                $detalle->pedido_id = $pedido->id;
                $detalle->libro_id = $libro->id;
                $detalle->tipo_licencia = $item['type'];
                $detalle->cantidad = $item['quantity'];
                $detalle->precio_unitario = $item['price'];
                $detalle->costo_total = $costoTotal;
                $detalle->save();
            }

            DB::commit();

            return response()->json([
                'message' => 'Pedido ingresado exitosamente.',
                'order_id' => $referencia, 
                'pedido' => $pedido->load('detalles')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al procesar el pedido: ' . $e->getMessage()], 500);
        }
    }
}