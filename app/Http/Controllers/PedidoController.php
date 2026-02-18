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
use App\Models\PedidoLog;
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
            'receiverType' => 'required|in:cliente,nuevo,existente',
            'receptor_id'  => 'nullable|required_if:receiverType,existente|exists:pedido_receptores,id',
            
            // Datos del Receptor (Solo obligatorios si el tipo es nuevo)
            'receiver.persona_recibe' => 'required_if:receiverType,nuevo|string|max:255',
            'receiver.rfc' => 'required_if:receiverType,nuevo|string|min:12|max:13',
            'receiver.regimen_fiscal' => 'required_if:receiverType,nuevo|string', 
            'receiver.telefono' => 'required_if:receiverType,nuevo|string',
            'receiver.correo' => 'required_if:receiverType,nuevo|email',
            
            // Dirección desglosada
            'receiver.cp' => 'required_if:receiverType,nuevo|string|size:5',
            'receiver.estado' => 'required_if:receiverType,nuevo|string', 
            'receiver.municipio' => 'required_if:receiverType,nuevo|string',
            'receiver.colonia' => 'required_if:receiverType,nuevo|string',
            'receiver.calle_num' => 'required_if:receiverType,nuevo|string',
            
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
                
                $regimenFull = $validatedData['receiver']['regimen_fiscal'] ?? '601 - GENERAL DE LEY PERSONAS MORALES';
                $receptorId = $validatedData['receptor_id'] ?? null;
                $direccionFormateada = '';

                if ($validatedData['receiverType'] === 'nuevo') {
                    $direccionFormateada = "{$validatedData['receiver']['calle_num']}, COL. {$validatedData['receiver']['colonia']}, {$validatedData['receiver']['municipio']}, {$validatedData['receiver']['estado']}, CP {$validatedData['receiver']['cp']}";
                    $receptor = PedidoReceptor::create([
                        'cliente_id'              => $validatedData['clientId'],
                        'nombre'                  => strtoupper($validatedData['receiver']['persona_recibe']),
                        'rfc'                     => strtoupper($validatedData['receiver']['rfc']),
                        'receiver_regimen_fiscal' => strtoupper($regimenFull), 
                        'telefono'                => $validatedData['receiver']['telefono'],
                        'correo'                  => $validatedData['receiver']['correo'],
                        'direccion'               => strtoupper($direccionFormateada)
                    ]);
                    $receptorId = $receptor->id;
                } elseif ($validatedData['receiverType'] === 'existente') {
                    $receptor = PedidoReceptor::findOrFail($receptorId);
                    $direccionFormateada = $receptor->direccion;
                } else {
                    $cliente = Cliente::findOrFail($validatedData['clientId']);
                    $direccionFormateada = $cliente->direccion;
                }

                $dbReceiverType = ($validatedData['receiverType'] === 'existente') ? 'nuevo' : $validatedData['receiverType'];

                $pedido = Pedido::create([
                    'user_id'                 => $ownerId,
                    'cliente_id'              => $validatedData['clientId'],
                    'prioridad'               => $validatedData['prioridad'],
                    'tipo_pedido'             => $request->tipo_pedido ?? 'normal',
                    'receptor_id'             => $receptorId,
                    'receiver_type'           => $dbReceiverType,
                    'receiver_regimen_fiscal' => strtoupper($regimenFull),
                    'delivery_option'         => $validatedData['logistics']['deliveryOption'] === 'entrega' ? 'none' : $validatedData['logistics']['deliveryOption'],
                    'paqueteria_nombre'       => strtoupper($request->input('logistics.paqueteria_nombre') ?? ''),
                    'commentary_delivery_option' => strtoupper($request->input('logistics.comentarios_logistica') ?? ''),
                    'delivery_address'        => strtoupper($direccionFormateada),
                    'comments'                => strtoupper($validatedData['comments'] ?? 'ORDEN PROCESADA'), 
                    'status'                  => 'PENDIENTE',
                ]);
                
                $pedido->update(['numero_referencia' => 'PED-' . Carbon::now()->format('ymd') . '-' . str_pad($pedido->id, 4, '0', STR_PAD_LEFT)]);

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

                return response()->json(['message' => 'Pedido generado.', 'order_id' => $pedido->numero_referencia], 201);
            });
        } catch (\Exception $e) {
            Log::error("Error en store Pedido: " . $e->getMessage());
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 422);
        }
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


  public function update(Request $request, $id)
    {
        $pedido = Pedido::with('detalles.libro')->findOrFail($id);

        if ($pedido->status !== 'PENDIENTE') {
            return response()->json(['message' => "No se puede modificar un pedido en estado {$pedido->status}"], 403);
        }

        $validatedData = $request->validate([
            'clientId' => 'required|exists:clientes,id',
            'prioridad' => 'required|in:baja,media,alta',
            'receiverType' => 'required|in:cliente,nuevo,existente',
            'receptor_id'  => 'nullable|required_if:receiverType,existente|exists:pedido_receptores,id',
            
            // Datos del Receptor (Opcionales si no es tipo nuevo para evitar bloqueos por campos vacíos en el frontend)
            'receiver.persona_recibe' => 'nullable|required_if:receiverType,nuevo|string|max:255',
            'receiver.rfc' => 'nullable|required_if:receiverType,nuevo|string|min:12|max:13',
            'receiver.regimen_fiscal' => 'nullable|required_if:receiverType,nuevo|string', 
            'receiver.telefono' => 'nullable|required_if:receiverType,nuevo|string',
            'receiver.correo' => 'nullable|required_if:receiverType,nuevo|email',
            'receiver.cp' => 'nullable|required_if:receiverType,nuevo|string',
            'receiver.estado' => 'nullable|required_if:receiverType,nuevo|string', 
            'receiver.municipio' => 'nullable|required_if:receiverType,nuevo|string',
            'receiver.colonia' => 'nullable|required_if:receiverType,nuevo|string',
            'receiver.calle_num' => 'nullable|required_if:receiverType,nuevo|string',
            
            'logistics.deliveryOption' => 'required',
            'items' => 'required|array|min:1',
            'motivo_cambio' => 'required|string|min:10'
        ]);

        try {
            return DB::transaction(function () use ($pedido, $request, $validatedData) {
                // 1. Log de Auditoría (Snapshot de integridad)
                PedidoLog::create([
                    'pedido_id' => $pedido->id,
                    'user_id' => Auth::id(),
                    'snapshot_anterior' => [
                        'cabecera' => $pedido->makeHidden(['detalles'])->toArray(),
                        'detalles' => $pedido->detalles->toArray()
                    ],
                    'motivo_cambio' => strtoupper($request->motivo_cambio)
                ]);

                // 2. Lógica de Receptor y Dirección
                $receptorId = null;
                $direccionFormateada = '';
                
                // Mapeo de Régimen Fiscal (Usamos el que viene en la validación o el que ya tiene el pedido)
                $regimenFull = $validatedData['receiver']['regimen_fiscal'] ?? $pedido->receiver_regimen_fiscal;

                if ($validatedData['receiverType'] === 'nuevo') {
                    $receiverData = $validatedData['receiver'];
                    $direccionFormateada = "{$receiverData['calle_num']}, COL. {$receiverData['colonia']}, {$receiverData['municipio']}, {$receiverData['estado']}, CP {$receiverData['cp']}";
                    
                    $receptor = PedidoReceptor::updateOrCreate(
                        ['rfc' => strtoupper($receiverData['rfc'])],
                        [
                            'cliente_id'              => $validatedData['clientId'],
                            'nombre'                  => strtoupper($receiverData['persona_recibe']),
                            'receiver_regimen_fiscal' => strtoupper($regimenFull), 
                            'telefono'                => $receiverData['telefono'],
                            'correo'                  => $receiverData['correo'],
                            'direccion'               => strtoupper($direccionFormateada)
                        ]
                    );
                    $receptorId = $receptor->id;
                } elseif ($validatedData['receiverType'] === 'existente') {
                    $receptor = PedidoReceptor::findOrFail($validatedData['receptor_id']);
                    $receptorId = $receptor->id;
                    $direccionFormateada = $receptor->direccion;
                } else {
                    $cliente = Cliente::findOrFail($validatedData['clientId']);
                    $direccionFormateada = $cliente->direccion;
                }

                $dbReceiverType = ($validatedData['receiverType'] === 'existente') ? 'nuevo' : $validatedData['receiverType'];

                // 3. Actualización de Cabecera
                $pedido->update([
                    'cliente_id'              => $validatedData['clientId'],
                    'prioridad'               => $validatedData['prioridad'],
                    'receptor_id'             => $receptorId,
                    'receiver_type'           => $dbReceiverType,
                    'receiver_regimen_fiscal' => strtoupper($regimenFull),
                    'delivery_address'        => strtoupper($direccionFormateada),
                    'delivery_option'         => $request->input('logistics.deliveryOption') === 'entrega' ? 'none' : $request->input('logistics.deliveryOption'),
                    'paqueteria_nombre'       => strtoupper($request->input('logistics.paqueteria_nombre') ?? ''),
                    'commentary_delivery_option' => strtoupper($request->input('logistics.comentarios_logistica') ?? ''),
                    'comments'                => strtoupper($request->comments ?? ''),
                ]);

                // 4. Sincronización de Materiales (Borrado y Re-creación)
                $pedido->detalles()->delete();
                foreach ($request->items as $item) {
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

                return response()->json(['message' => 'Pedido actualizado correctamente.'], 200);
            });
        } catch (\Exception $e) {
            Log::error("Error en update Pedido: " . $e->getMessage());
            return response()->json(['message' => 'Error al procesar la actualización: ' . $e->getMessage()], 422);
        }
    }
}