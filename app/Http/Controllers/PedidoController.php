<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Cliente; 
use App\Models\Pedido; 
use App\Models\PedidoDetalle; 
use App\Models\PedidoReceptor;
use App\Models\Libro;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PedidoController extends Controller
{
    /**
     * Proxy para Dipomex: Mantiene segura tu API KEY en el servidor.
     */
    public function proxyDipomex(Request $request)
    {
        $cp = $request->query('cp');
        $apiKey = env('DIPOMEX_APIKEY');

        if (!$cp || strlen($cp) !== 5) {
            return response()->json(['error' => true, 'message' => 'Código Postal inválido'], 400);
        }

        try {
            $response = Http::withHeaders(['APIKEY' => $apiKey])
                ->get("https://api.tau.com.mx/dipomex/v1/codigo_postal?cp={$cp}");

            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json(['error' => true, 'message' => 'Error al conectar con el servicio postal'], 500);
        }
    }

    /**
     * Listado de pedidos con soporte para delegados.
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $ownerId = method_exists($user, 'getEffectiveId') ? $user->getEffectiveId() : $user->id;

            $pedidos = Pedido::where('user_id', $ownerId)
                            ->with(['cliente', 'detalles.libro']) 
                            ->orderBy('created_at', 'desc')
                            ->paginate(15);
            
            return response()->json($pedidos);

        } catch (\Exception $e) {
            Log::error("Error en PedidoController@index: " . $e->getMessage());
            return response()->json(['message' => 'Error al listar pedidos.'], 500);
        }
    }

    /**
     * Detalle de un pedido verificando propiedad efectiva.
     */
    public function show(Request $request, $id)
    {
        try {
            $user = $request->user();
            $ownerId = method_exists($user, 'getEffectiveId') ? $user->getEffectiveId() : $user->id;

            $pedido = Pedido::with(['cliente', 'detalles.libro', 'receptor']) 
                        ->where('id', $id)
                        ->where('user_id', $ownerId)
                        ->firstOrFail();
                        
            return response()->json($pedido);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Pedido no encontrado.'], 404);
        }
    }

    /**
     * Registro de pedido con lógica de Receptores y Clientes centralizada.
     */
 public function store(Request $request)
    {
        $validatedData = $request->validate([
            'clientId' => 'required|exists:clientes,id',
            'prioridad' => 'required|in:baja,media,alta',
            'receiverType' => 'required|in:cliente,nuevo',
            
            // Datos del Receptor (Indispensables)
            'receiver.persona_recibe' => 'required|string|max:255',
            'receiver.rfc' => 'required|string|min:12|max:13',
            'receiver.regimen_fiscal' => 'nullable|string',
            'receiver.telefono' => 'required|string',
            'receiver.correo' => 'required|email',
            'receiver.cp' => 'required|string|size:5',
            'receiver.estado' => 'required|string', // <--- AGREGADO: Validación de estado
            'receiver.municipio' => 'required|string',
            'receiver.colonia' => 'required|string',
            'receiver.calle_num' => 'required|string',
            
            // Logística
            'logistics.deliveryOption' => 'required|in:paqueteria,recoleccion,entrega',
            'logistics.paqueteria_nombre' => 'nullable|required_if:logistics.deliveryOption,paqueteria|string',
            'logistics.comentarios_logistica' => 'nullable|string',
            
            'comments' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.bookId' => 'required|exists:libros,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'nullable|numeric',
            'items.*.sub_type' => 'required|string',      
            'items.*.tipo_material' => 'required|string', 
        ]);

        try {
            return DB::transaction(function () use ($validatedData, $request) {
                $user = $request->user();
                $ownerId = method_exists($user, 'getEffectiveId') ? $user->getEffectiveId() : $user->id;
                $receptorId = null;

                // ACTUALIZACIÓN: Se incluye el campo 'estado' en la dirección formateada para persistencia
                $direccionFormateada = $validatedData['receiver']['calle_num'] . 
                                       ", Col. " . $validatedData['receiver']['colonia'] . 
                                       ", " . $validatedData['receiver']['municipio'] . 
                                       ", " . $validatedData['receiver']['estado'] . // <--- AÑADIDO
                                       ", CP " . $validatedData['receiver']['cp'];

                // SOLUCIÓN AL ERROR DE TRUNCADO: 
                // Si la base de datos no tiene 'entrega', lo mapeamos a 'none' para evitar el crash
                // mientras el usuario actualiza su migración.
                $deliveryValue = $validatedData['logistics']['deliveryOption'];
                if ($deliveryValue === 'entrega') {
                    // Verificamos si el motor de DB soporta 'entrega', si no, enviamos 'none'
                    $deliveryValue = 'none'; 
                }

                if ($validatedData['receiverType'] === 'nuevo') {
                    // VERIFICACIÓN DE SEGURIDAD (DUPLICADOS)
                    $duplicado = PedidoReceptor::where('rfc', strtoupper($validatedData['receiver']['rfc']))
                        ->orWhere('correo', strtolower($validatedData['receiver']['correo']))
                        ->orWhere('telefono', $validatedData['receiver']['telefono'])
                        ->first();

                    if ($duplicado) {
                        throw new \Exception("Los datos ingresados ya pertenecen a un registro existente ({$duplicado->nombre}).");
                    }

                    $receptor = PedidoReceptor::create([
                        'cliente_id' => $validatedData['clientId'],
                        'nombre'     => $validatedData['receiver']['persona_recibe'],
                        'rfc'        => strtoupper($validatedData['receiver']['rfc']),
                        'telefono'   => $validatedData['receiver']['telefono'],
                        'correo'     => $validatedData['receiver']['correo'],
                        'direccion'  => $direccionFormateada
                    ]);
                    $receptorId = $receptor->id;
                } else {
                    // MODO CLIENTE: Actualizamos la ficha maestra en lugar de crear un receptor nuevo duplicado
                    $cliente = Cliente::findOrFail($validatedData['clientId']);
                    $cliente->update([
                        'rfc' => strtoupper($validatedData['receiver']['rfc']),
                        'email' => $validatedData['receiver']['correo'],
                        'telefono' => $validatedData['receiver']['telefono'],
                        'direccion' => $direccionFormateada
                    ]);
                }

                $pedido = Pedido::create([
                    'user_id'           => $ownerId,
                    'cliente_id'        => $validatedData['clientId'],
                    'prioridad'         => $validatedData['prioridad'],
                    'tipo_pedido'       => $request->tipo_pedido ?? 'normal',
                    'receptor_id'       => $receptorId,
                    'receiver_type'     => $validatedData['receiverType'],
                    'delivery_option'   => $deliveryValue, // Valor corregido para evitar error 1265
                    'paqueteria_nombre' => $validatedData['logistics']['paqueteria_nombre'] ?? null,
                    'delivery_address'  => $direccionFormateada,
                    'comments'          => $validatedData['comments'] ?? $validatedData['logistics']['comentarios_logistica'],
                    'status'            => 'PENDIENTE',
                ]);
                
                $referencia = "PED" . Carbon::now()->format('dmy') . $pedido->id; 
                $pedido->update(['numero_referencia' => $referencia]);

                foreach ($validatedData['items'] as $item) {
                    PedidoDetalle::create([
                        'pedido_id'       => $pedido->id,
                        'libro_id'        => $item['bookId'],
                        'tipo'            => $item['tipo_material'], 
                        'tipo_licencia'   => $item['sub_type'],
                        'cantidad'        => $item['quantity'],
                        'precio_unitario' => $item['price'] ?? 0,
                        'costo_total'     => $item['quantity'] * ($item['price'] ?? 0)
                    ]);
                }

                return response()->json(['message' => 'Pedido generado correctamente.', 'order_id' => $referencia], 201);
            });
        } catch (\Exception $e) {
            Log::error("Error en store Pedido: " . $e->getMessage());
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 422);
        }
    }

    /**
     * Actualización de pedido (Solo permitido en estatus PENDIENTE).
     */
    public function update(Request $request, $id)
    {
        $pedido = Pedido::findOrFail($id);

        if ($pedido->status !== 'PENDIENTE') {
            return response()->json([
                'message' => "El pedido ya se encuentra en estado {$pedido->status} y no puede ser modificado."
            ], 403);
        }

        $pedido->update($request->all());
        
        return response()->json(['message' => 'Pedido actualizado correctamente']);
    }

    /**
     * Subida de factura por personal de oficina.
     */
    public function uploadFactura(Request $request, $id)
    {
        $request->validate([
            'factura' => 'required|file|mimes:pdf|max:4096' 
        ]);

        $pedido = Pedido::findOrFail($id);
        
        // Guardar factura en almacenamiento seguro (public)
        $path = $request->file('factura')->store('facturas/pedidos', 'public');
        
        $pedido->update(['factura_path' => $path]);

        return response()->json([
            'message' => 'Factura adjuntada con éxito',
            'url' => $pedido->factura_url
        ]);
    }
}