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
            Log::error("Error Dipomex: " . $e->getMessage());
            return response()->json(['error' => true, 'message' => 'Error al conectar con el servicio postal'], 500);
        }
    }

    /**
     * Valida la unicidad global de un receptor en tiempo real.
     * REGLA: Permite al frontend alertar duplicados mientras el usuario escribe.
     */
    public function checkReceiverIntegrity(Request $request)
    {
        $rfc = $request->query('rfc');
        $correo = $request->query('correo');
        $telefono = $request->query('telefono');
        $nombre = $request->query('nombre');

        $user = Auth::user();
        $ownerId = method_exists($user, 'getEffectiveId') ? $user->getEffectiveId() : $user->id;

        $query = PedidoReceptor::query();

        // Aplicamos el filtro basado en el parámetro recibido
        if ($rfc) {
            $query->where('rfc', strtoupper(trim($rfc)));
        } elseif ($correo) {
            $query->where('correo', strtolower(trim($correo)));
        } elseif ($telefono) {
            $query->where('telefono', trim($telefono));
        } elseif ($nombre) {
            $query->where('nombre', strtoupper(trim($nombre)));
        } else {
            return response()->json(['message' => 'Faltan criterios de búsqueda'], 400);
        }

        // Buscamos de forma global para detectar colisiones con otros representantes
        $existente = $query->first();

        if (!$existente) {
            return response()->json(['status' => 'success', 'available' => true]);
        }

        // Lógica de Privacidad
        $isPrivate = ($existente->user_id !== $ownerId);

        return response()->json([
            'status' => 'conflict',
            'available' => false,
            'is_private' => $isPrivate,
            'nombre' => $existente->nombre,
            'message' => $isPrivate 
                ? 'ESTE DATO YA PERTENECE A OTRO REPRESENTANTE Y NO ES ACCESIBLE PARA TI.' 
                : 'YA TIENES ESTE REGISTRO EN TU AGENDA DE MIS RECEPTORES.'
        ]);
    }

    /**
     * Listado de pedidos con soporte para delegados y representantes.
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
            Log::error("Error index pedidos: " . $e->getMessage());
            return response()->json(['message' => 'Error al listar pedidos.'], 500);
        }
    }

    /**
     * Detalle técnico de un pedido con Auditoría y Receptor.
     */
    public function show(Request $request, $id)
    {
        try {
            $user = $request->user();
            $ownerId = method_exists($user, 'getEffectiveId') ? $user->getEffectiveId() : $user->id;

            $pedido = Pedido::with(['cliente', 'detalles.libro', 'receptor', 'logs.user']) 
                        ->where('id', $id)
                        ->where('user_id', $ownerId)
                        ->firstOrFail();
                        
            return response()->json($pedido);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Pedido no localizado.'], 404);
        } catch (\Exception $e) {
            Log::error("Error show pedido: " . $e->getMessage());
            return response()->json(['message' => 'Error interno.'], 500);
        }
    }

    /**
     * Registro de pedido con Doble Escritura y Validación de Unicidad Cuádruple.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'clientId' => 'required|exists:clientes,id',
            'prioridad' => 'required|in:baja,media,alta',
            'receiverType' => 'required|in:cliente,nuevo,existente',
            'receptor_id'  => 'nullable|required_if:receiverType,existente|exists:pedido_receptores,id',
            
            // Datos del Receptor
            'receiver.persona_recibe' => 'required_if:receiverType,nuevo|string|max:255',
            'receiver.rfc' => 'required_if:receiverType,nuevo|string|min:12|max:13',
            'receiver.regimen_fiscal' => 'required_if:receiverType,nuevo|string', 
            'receiver.telefono' => 'required_if:receiverType,nuevo|string',
            'receiver.correo' => 'required_if:receiverType,nuevo|email',
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

            // Materiales
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
                
                // --- VALIDACIÓN DE INTEGRIDAD GLOBAL (BLOQUEO DURO AL GUARDAR) ---
                if ($validatedData['receiverType'] === 'nuevo') {
                    $r = $validatedData['receiver'];
                    $rfcNorm      = strtoupper(trim($r['rfc']));
                    $nombreNorm   = strtoupper(trim($r['persona_recibe']));
                    $correoNorm   = strtolower(trim($r['correo']));
                    $telefonoNorm = trim($r['telefono']);

                    $duplicado = PedidoReceptor::where('rfc', $rfcNorm)
                        ->orWhere('nombre', $nombreNorm)
                        ->orWhere('correo', $correoNorm)
                        ->orWhere('telefono', $telefonoNorm)
                        ->first();

                    if ($duplicado) {
                        $campo = 'dato';
                        if ($duplicado->rfc === $rfcNorm) $campo = 'RFC';
                        elseif ($duplicado->nombre === $nombreNorm) $campo = 'NOMBRE';
                        elseif ($duplicado->correo === $correoNorm) $campo = 'CORREO';
                        elseif ($duplicado->telefono === $telefonoNorm) $campo = 'TELÉFONO';

                        $esPropio = ($duplicado->user_id === $ownerId);
                        $msjExtra = $esPropio ? "Ya tienes este registro en tu agenda." : "Este dato pertenece a otro representante.";

                        throw new \Exception("INTEGRIDAD: El {$campo} ingresado ya existe en el sistema. {$msjExtra}");
                    }
                }

                // 1. Cálculos de Totales
                $totalQuantity = collect($validatedData['items'])->sum('quantity');
                $totalAmount = collect($validatedData['items'])->sum(function($item) {
                    return $item['quantity'] * ($item['price'] ?? 0);
                });

                // 2. Lógica de Receptor y Dirección
                $regimenFull = $validatedData['receiver']['regimen_fiscal'] ?? '601 - GENERAL DE LEY PERSONAS MORALES';
                $receptorId = $validatedData['receptor_id'] ?? null;
                $direccionFormateada = '';

                if ($validatedData['receiverType'] === 'nuevo') {
                    $r = $validatedData['receiver'];
                    $direccionFormateada = "{$r['calle_num']}, COL. {$r['colonia']}, {$r['municipio']}, {$r['estado']}, CP {$r['cp']}";
                    
                    $receptor = PedidoReceptor::create([
                        'user_id'                 => $ownerId,
                        'cliente_id'              => $validatedData['clientId'],
                        'nombre'                  => strtoupper($r['persona_recibe']),
                        'rfc'                     => strtoupper($r['rfc']),
                        'receiver_regimen_fiscal' => strtoupper($regimenFull), 
                        'telefono'                => $r['telefono'],
                        'correo'                  => strtolower($r['correo']),
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

                // 3. Escritura en DB Local (Tabla pedidos)
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
                    'total_quantity'          => $totalQuantity,
                    'total'                   => $totalAmount,
                    'estado'                  => 'proceso',
                    'actualizado_por'         => strtoupper($user->name),
                ]);
                
                $pedido->update(['numero_referencia' => 'PED-' . Carbon::now()->format('ymd') . '-' . str_pad($pedido->id, 4, '0', STR_PAD_LEFT)]);

                // 4. Escritura en DB Externa (mysql_inventario)
                try {
                    $dbInventario = DB::connection('mysql_inventario');
                    $idInventario = $dbInventario->table('pedidos')->insertGetId([
                        'user_id'         => $ownerId,
                        'cliente_id'      => $validatedData['clientId'],
                        'total_quantity'  => $totalQuantity,
                        'total'           => $totalAmount,
                        'total_solicitar' => 0,
                        'estado'          => 'proceso',
                        'comentarios'     => strtoupper($validatedData['comments'] ?? 'SINERGIA WEB'),
                        'actualizado_por' => strtoupper($user->name),
                        'created_at'      => now(),
                        'updated_at'      => now()
                    ]);
                } catch (\Exception $eEx) {
                    Log::error("Error insert cabecera inventario: " . $eEx->getMessage());
                    throw new \Exception("Fallo en sincronización de inventario.");
                }

                // 5. Registro de ítems
                foreach ($validatedData['items'] as $item) {
                    $tipoPeticion = ($item['tipo_material'] === 'promocion') 
                        ? (str_contains(strtolower($item['sub_type']), 'demo') ? 'demo' : 'profesor') 
                        : 'alumno';

                    $dbInventario->table('peticiones')->insert([
                        'pedido_id'  => $idInventario,
                        'pack_id'    => null, 
                        'libro_id'   => $item['bookId'],
                        'tipo'       => $tipoPeticion,
                        'quantity'   => $item['quantity'],
                        'price'      => $item['price'] ?? 0,
                        'total'      => $item['quantity'] * ($item['price'] ?? 0),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);

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

                return response()->json(['message' => 'Pedido registrado con éxito.', 'order_id' => $pedido->numero_referencia], 201);
            });
        } catch (\Exception $e) {
            Log::error("Fallo general store pedido: " . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Actualización de pedido con Sincronización y Validación de Integridad Global.
     */
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
            'items' => 'required|array|min:1',
            'motivo_cambio' => 'required|string|min:10'
        ]);

        try {
            return DB::transaction(function () use ($pedido, $request, $validatedData) {
                $user = Auth::user();
                $ownerId = method_exists($user, 'getEffectiveId') ? $user->getEffectiveId() : $user->id;

                // --- VALIDACIÓN DE INTEGRIDAD GLOBAL EN UPDATE ---
                if ($validatedData['receiverType'] === 'nuevo') {
                    $r = $request->input('receiver');
                    $rfcNorm      = strtoupper(trim($r['rfc']));
                    $nombreNorm   = strtoupper(trim($r['persona_recibe']));
                    $correoNorm   = strtolower(trim($r['correo']));
                    $telefonoNorm = trim($r['telefono']);

                    $duplicado = PedidoReceptor::where(function($q) use ($rfcNorm, $nombreNorm, $correoNorm, $telefonoNorm) {
                            $q->where('rfc', $rfcNorm)
                              ->orWhere('nombre', $nombreNorm)
                              ->orWhere('correo', $correoNorm)
                              ->orWhere('telefono', $telefonoNorm);
                        })
                        ->where('id', '!=', $pedido->receptor_id)
                        ->first();

                    if ($duplicado) {
                        throw new \Exception("INTEGRIDAD: Uno de los datos ingresados ya pertenece a otro registro en el sistema.");
                    }
                }

                // 1. Log de Auditoría
                PedidoLog::create([
                    'pedido_id' => $pedido->id,
                    'user_id' => $user->id,
                    'snapshot_anterior' => [
                        'cabecera' => $pedido->makeHidden(['detalles'])->toArray(),
                        'detalles' => $pedido->detalles->toArray()
                    ],
                    'motivo_cambio' => strtoupper($request->motivo_cambio)
                ]);

                // 2. Cálculos de Totales
                $totalQuantity = collect($validatedData['items'])->sum('quantity');
                $totalAmount = collect($validatedData['items'])->sum(function($i){ return $i['quantity'] * ($i['price'] ?? 0); });

                // 3. Gestión de Receptor
                $receptorId = $request->receptor_id;
                $direccionFormateada = $pedido->delivery_address;

                if ($validatedData['receiverType'] === 'nuevo') {
                    $r = $request->input('receiver');
                    $direccionFormateada = "{$r['calle_num']}, COL. {$r['colonia']}, {$r['municipio']}, {$r['estado']}, CP {$r['cp']}";
                    $receptor = PedidoReceptor::updateOrCreate(
                        ['rfc' => strtoupper($r['rfc'])],
                        [
                            'user_id' => $ownerId,
                            'cliente_id' => $validatedData['clientId'],
                            'nombre' => strtoupper($r['persona_recibe']),
                            'receiver_regimen_fiscal' => strtoupper($r['regimen_fiscal'] ?? $pedido->receiver_regimen_fiscal),
                            'telefono' => $r['telefono'],
                            'correo' => strtolower($r['correo']),
                            'direccion' => strtoupper($direccionFormateada)
                        ]
                    );
                    $receptorId = $receptor->id;
                }

                // 4. Actualización Local
                $pedido->update([
                    'cliente_id'              => $validatedData['clientId'],
                    'prioridad'               => $validatedData['prioridad'],
                    'receptor_id'             => $receptorId,
                    'receiver_regimen_fiscal' => strtoupper($request->input('receiver.regimen_fiscal') ?? $pedido->receiver_regimen_fiscal),
                    'delivery_address'        => strtoupper($direccionFormateada),
                    'delivery_option'         => $request->input('logistics.deliveryOption') === 'entrega' ? 'none' : $request->input('logistics.deliveryOption'),
                    'paqueteria_nombre'       => strtoupper($request->input('logistics.paqueteria_nombre') ?? ''),
                    'total_quantity'          => $totalQuantity,
                    'total'                   => $totalAmount,
                    'actualizado_por'         => strtoupper($user->name),
                ]);

                // 5. Sincronización Inventario
                try {
                    $dbInv = DB::connection('mysql_inventario');
                    $pedidoExterno = $dbInv->table('pedidos')
                        ->where('cliente_id', $pedido->cliente_id)
                        ->where('estado', 'proceso')
                        ->orderBy('id', 'desc')
                        ->first();

                    if ($pedidoExterno) {
                        $dbInv->table('pedidos')->where('id', $pedidoExterno->id)->update([
                            'total' => $totalAmount,
                            'total_quantity' => $totalQuantity,
                            'updated_at' => now()
                        ]);

                        $dbInv->table('peticiones')->where('pedido_id', $pedidoExterno->id)->delete();

                        foreach ($request->items as $item) {
                            $tipoPeticion = ($item['tipo_material'] === 'promocion') 
                                ? (str_contains(strtolower($item['sub_type']), 'demo') ? 'demo' : 'profesor') 
                                : 'alumno';

                            $dbInv->table('peticiones')->insert([
                                'pedido_id'  => $pedidoExterno->id,
                                'pack_id'    => null,
                                'libro_id'   => $item['bookId'],
                                'tipo'       => $tipoPeticion,
                                'quantity'   => $item['quantity'],
                                'price'      => $item['price'] ?? 0,
                                'total'      => $item['quantity'] * ($item['price'] ?? 0),
                                'created_at' => now(),
                                'updated_at' => now()
                            ]);
                        }
                    }
                } catch (\Exception $eInv) { Log::warning("Fallo Sync Inventario: " . $eInv->getMessage()); }

                // 6. Refrescar detalles locales
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

                return response()->json(['message' => 'Expediente actualizado correctamente.'], 200);
            });
        } catch (\Exception $e) {
            return response()->json(['message' => 'Fallo al procesar: ' . $e->getMessage()], 422);
        }
    }

    /**
     * Subida de factura por personal de oficina.
     */
    public function uploadFactura(Request $request, $id)
    {
        $request->validate(['factura' => 'required|file|mimes:pdf|max:4096']);
        $pedido = Pedido::findOrFail($id);
        $path = $request->file('factura')->store('facturas/pedidos', 'public');
        $pedido->update(['factura_path' => $path]);
        return response()->json(['message' => 'Factura adjuntada con éxito', 'url' => $pedido->factura_url]);
    }
}