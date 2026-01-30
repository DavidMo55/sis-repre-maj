<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\Gasto;
use App\Models\Comprobante;
use App\Models\User;
use Carbon\Carbon;
use Spatie\Dropbox\Client as DropboxClient;

class GastoController extends Controller
{
    private $allowedMimes = ['jpg', 'jpeg', 'png', 'pdf'];
    private $maxFileSize = 3072; // 3MB

    /**
     * Listado de gastos.
     * REGLA: Ordenamos por ID descendente para que el registro recién creado 
     * aparezca siempre al principio, incluso si tiene una fecha de gasto antigua.
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $ownerId = method_exists($user, 'getEffectiveId') ? $user->getEffectiveId() : $user->id;
            
            $query = Gasto::where('user_id', $ownerId)->with('comprobantes');

            if ($request->filled('fecha_desde') && $request->filled('fecha_hasta')) {
                $query->whereBetween('fecha', [$request->fecha_desde, $request->fecha_hasta]);
            }
            
            // CORRECCIÓN: Cambiado de 'fecha' a 'id' para empujar nuevos registros arriba
            return response()->json($query->orderBy('id', 'desc')->paginate(15));
        } catch (\Exception $e) {
            Log::error("Error en index de gastos: " . $e->getMessage());
            return response()->json(['message' => 'Error al obtener gastos'], 500);
        }
    }

    /**
     * Registro inicial de un paquete de gastos.
     */
    public function store(Request $request)
    {
        $request->validate([
            'fecha'         => 'required|date',
            'estado_nombre' => 'required|string',
            'monto_total'   => 'required|numeric|min:0',
            'conceptos'     => 'required|array|min:1',
            'conceptos.*.concepto'     => 'required|string',
            'conceptos.*.monto'        => 'required|numeric|min:0',
            'conceptos.*.es_facturado' => 'required|boolean',
        ]);

        try {
            $user = $request->user();
            $ownerId = method_exists($user, 'getEffectiveId') ? $user->getEffectiveId() : $user->id;

            $tieneFactura = collect($request->conceptos)->contains('es_facturado', true);

            $gasto = Gasto::create([
                'user_id'       => $ownerId,
                'fecha'         => $request->fecha,
                'estado_nombre' => $request->estado_nombre,
                'concepto'      => "Paquete de gastos: " . $request->estado_nombre,
                'monto'         => $request->monto_total,
                'facturado'     => $tieneFactura,
                'detalles'      => $request->conceptos, 
            ]);

            return response()->json([
                'message' => 'Paquete de gastos registrado correctamente.',
                'gasto'   => $gasto
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error al crear paquete de gastos:', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Error interno: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Sube comprobantes a Dropbox y los vincula al gasto.
     */
    public function storeComprobante(Request $request)
    {
        $request->validate([
            'gasto_id' => 'required|exists:gastos,id',
            'files'    => 'required|array',
            'files.*'  => 'file|mimes:jpg,jpeg,png,pdf|max:3072',
        ]);
        
        $gasto = Gasto::findOrFail($request->gasto_id);
        $user = $request->user();
        $ownerId = method_exists($user, 'getEffectiveId') ? $user->getEffectiveId() : $user->id;

        if ($gasto->user_id !== $ownerId) {
            return response()->json(['message' => 'No autorizado para este registro.'], 403);
        }

        try {
            $response = Http::asForm()->post('https://api.dropbox.com/oauth2/token', [
                'grant_type'    => 'refresh_token',
                'refresh_token' => env('DROPBOX_REFRESH_TOKEN'),
                'client_id'     => env('DROPBOX_APP_KEY'),
                'client_secret' => env('DROPBOX_APP_SECRET'),
            ]);

            if (!$response->successful()) {
                Log::error('Error Dropbox Auth:', ['response' => $response->body()]);
                return response()->json(['message' => 'Error de conexión con Dropbox.'], 500);
            }

            $accessToken = $response->json()['access_token'];
            $dropboxClient = new DropboxClient($accessToken);

            DB::beginTransaction();
            $comprobantesGuardados = [];

            foreach ($request->file('files') as $file) {
                $timestamp = Carbon::now()->format('ymd-His');
                $extension = $file->getClientOriginalExtension();
                
                $fileName = "{$timestamp}_U" . Auth::id() . "-G{$gasto->id}.{$extension}";
                $path = "/comprobantes/{$gasto->id}/{$fileName}";
                
                $dropboxClient->upload($path, file_get_contents($file->getRealPath()), 'add');

                $publicUrl = null;
                try {
                    $sharedResponse = $dropboxClient->createSharedLinkWithSettings($path, ["requested_visibility" => "public"]);
                    $publicUrl = $sharedResponse['url'];
                } catch (\Exception $e) {
                    $links = $dropboxClient->listSharedLinks($path);
                    $publicUrl = !empty($links) ? $links[0]['url'] : null;
                }

                if ($publicUrl) {
                    $publicUrl = str_replace('dl=0', 'dl=1', $publicUrl);
                }

                $comprobante = Comprobante::create([
                    'gasto_id'  => $gasto->id,
                    'name'      => $fileName,
                    'size'      => round($file->getSize() / 1024),
                    'extension' => $extension,
                    'public_url'=> $publicUrl,
                ]);

                $comprobantesGuardados[] = $comprobante;
            }

            DB::commit();
            return response()->json(['message' => 'Archivos subidos exitosamente.', 'comprobantes' => $comprobantesGuardados], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error Crítico en Subida:', ['msg' => $e->getMessage()]);
            return response()->json(['message' => 'Error al procesar la subida.'], 500);
        }
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        $ownerId = method_exists($user, 'getEffectiveId') ? $user->getEffectiveId() : $user->id;
        
        $gasto = Gasto::with('comprobantes')->findOrFail($id);

        if ($gasto->user_id !== $ownerId) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        return response()->json($gasto);
    }
}