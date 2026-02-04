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
            
            // Datos del Receptor
            'receiver.persona_recibe' => 'required|string|max:255',
            'receiver.rfc' => 'required|string|min:12|max:13',
            'receiver.regimen_fiscal' => 'required|string|max:255', 
            'receiver.telefono' => 'required|string',
            'receiver.correo' => 'required|email',
            'receiver.cp' => 'required|string|size:5',
            'receiver.estado' => 'required|string', 
            'receiver.municipio' => 'required|string',
            'receiver.colonia' => 'required|string',
            'receiver.calle_num' => 'required|string',
            
            // Logística
            'logistics.deliveryOption' => 'required|in:paqueteria,recoleccion,entrega',
            'logistics.paqueteria_nombre' => 'nullable|required_if:logistics.deliveryOption,paqueteria|string',
            'logistics.comentarios_logistica' => 'nullable|string|max:255',

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

                $direccionFormateada = $validatedData['receiver']['calle_num'] . 
                                       ", Col. " . $validatedData['receiver']['colonia'] . 
                                       ", " . $validatedData['receiver']['municipio'] . 
                                       ", " . $validatedData['receiver']['estado'] . 
                                       ", CP " . $validatedData['receiver']['cp'];

                $deliveryValue = $validatedData['logistics']['deliveryOption'];
                if ($deliveryValue === 'entrega') { $deliveryValue = 'none'; }

                if ($validatedData['receiverType'] === 'nuevo') {
                    $duplicado = PedidoReceptor::where('rfc', strtoupper($validatedData['receiver']['rfc']))
                        ->orWhere('nombre', $validatedData['receiver']['persona_recibe'])
                        ->first();

                    if ($duplicado) {
                        throw new \Exception("Los datos ya pertenecen a un receptor existente ({$duplicado->nombre}).");
                    }

                    // CORRECCIÓN: Se usa 'receiver_regimen_fiscal' para coincidir con la DB
                    $receptor = PedidoReceptor::create([
                        'cliente_id'              => $validatedData['clientId'],
                        'nombre'                  => $validatedData['receiver']['persona_recibe'],
                        'rfc'                     => strtoupper($validatedData['receiver']['rfc']),
                        'receiver_regimen_fiscal' => $validatedData['receiver']['regimen_fiscal'], 
                        'telefono'                => $validatedData['receiver']['telefono'],
                        'correo'                  => $validatedData['receiver']['correo'],
                        'direccion'               => $direccionFormateada
                    ]);
                    $receptorId = $receptor->id;
                } else {
                    $cliente = Cliente::findOrFail($validatedData['clientId']);
                    $cliente->update([
                        'contacto'       => $validatedData['receiver']['persona_recibe'],
                        'rfc'            => strtoupper($validatedData['receiver']['rfc']),
                        'regimen_fiscal' => $validatedData['receiver']['regimen_fiscal'],
                        'direccion'      => $direccionFormateada
                    ]);
                }

                $pedido = Pedido::create([
                    'user_id'                 => $ownerId,
                    'cliente_id'              => $validatedData['clientId'],
                    'prioridad'               => $validatedData['prioridad'],
                    'tipo_pedido'             => $request->tipo_pedido ?? 'normal',
                    'receptor_id'             => $receptorId,
                    'receiver_type'           => $validatedData['receiverType'],
                    'receiver_regimen_fiscal' => $validatedData['receiver']['regimen_fiscal'],
                    'delivery_option'         => $deliveryValue,
                    'paqueteria_nombre'       => $validatedData['logistics']['paqueteria_nombre'] ?? null,
                    'commentary_delivery_option' => $request->commentary_delivery_option ?? 'Sin notas específicas',
                    'delivery_address'        => $direccionFormateada,
                    'comments'                => $validatedData['comments'], 
                    'status'                  => 'PENDIENTE',
                ]);
                
                $idFormateado = str_pad($pedido->id, 4, '0', STR_PAD_LEFT);
                $referencia = 'PED-' . Carbon::now()->format('ymd') . '-' . $idFormateado;
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
            return response()->json(['message' => 'Fallo de Servidor: ' . $e->getMessage()], 422);
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