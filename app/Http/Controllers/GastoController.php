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
use Carbon\Carbon;
use Spatie\Dropbox\Client as DropboxClient;

class GastoController extends Controller
{
    private $allowedMimes = ['jpg', 'jpeg', 'png', 'pdf'];
    private $maxFileSize = 3072; // 3MB

    /**
     * Listado de gastos del representante con carga de comprobantes.
     */
    public function index(Request $request)
    {
        try {
            $userId = Auth::id();
            $query = Gasto::where('user_id', $userId)->with('comprobantes');

            if ($request->filled('fecha_desde') && $request->filled('fecha_hasta')) {
                $query->whereBetween('fecha', [$request->fecha_desde, $request->fecha_hasta]);
            }
            
            // Prioriza en el orden los que no tienen comprobante y luego por fecha descendente
            $query->orderByRaw('EXISTS (SELECT 1 FROM comprobantes WHERE comprobantes.gasto_id = gastos.id) ASC')
                  ->orderBy('fecha', 'desc');

            return response()->json($query->paginate(10));
        } catch (\Exception $e) {
            Log::error('Error al listar gastos:', ['exception' => $e->getMessage()]);
            return response()->json(['message' => 'Error al listar gastos.'], 500);
        }
    }

    /**
     * Registro inicial de un gasto (Paso 1).
     * Sincronizado con la columna 'facturado' de la base de datos.
     */
    public function store(Request $request)
{
    $request->validate([
        'fecha'         => 'required|date',
        'concepto_base' => 'required|string',
        'estado_nombre' => 'required|string',
        'monto'         => 'required|numeric|min:0',
        'es_facturado'  => 'required|boolean', 
    ]);

    try {
        $conceptoFinal = $request->concepto_base . " visita " . $request->estado_nombre;

        // Se crea el registro usando solo las columnas existentes en tu BD
        $gasto = Gasto::create([
            'user_id'   => Auth::id(),
            'fecha'     => $request->fecha,
            'concepto'  => $conceptoFinal,
            'monto'     => $request->monto,
            'facturado' => $request->es_facturado, // Esto se insertará en la columna 'facturado'
        ]);

        return response()->json([
            'message' => 'Gasto registrado correctamente.',
            'gasto'   => $gasto
        ], 201);

    } catch (\Exception $e) {
        // Log detallado para ver la consulta si falla
        Log::error('Error Crítico al crear gasto:', ['error' => $e->getMessage()]);
        return response()->json(['message' => 'Error interno: ' . $e->getMessage()], 500);
    }
}
    /**
     * Sube comprobantes a Dropbox y los vincula al gasto (Paso 2).
     */
    public function storeComprobante(Request $request)
    {

        Log::info('Archivos recibidos:', ['files' => $request->file('files')]);
        
        $request->validate([
        'gasto_id' => 'required|exists:gastos,id',
        'files'    => 'required|array',
        'files.*'  => 'file|mimes:jpg,jpeg,png,pdf|max:3072', // Valida cada elemento del arreglo
    ]);
        
        $gasto = Gasto::findOrFail($request->gasto_id);

        if ($gasto->user_id !== Auth::id()) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        try {
            // Renovación de Token de Dropbox
            $response = Http::asForm()->post('https://api.dropbox.com/oauth2/token', [
                'grant_type'    => 'refresh_token',
                'refresh_token' => env('DROPBOX_REFRESH_TOKEN'),
                'client_id'     => env('DROPBOX_APP_KEY'),
                'client_secret' => env('DROPBOX_APP_SECRET'),
            ]);

            if (!$response->successful()) {
                Log::error('Error Dropbox Auth:', ['response' => $response->body()]);
                return response()->json(['message' => 'Error de conexión con almacén de archivos.'], 500);
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
                
                // Subida del archivo físico
                $dropboxClient->upload($path, file_get_contents($file->getRealPath()), 'add');

                // Generación de link para visualización directa
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

                // Registro en base de datos
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
            return response()->json([
                'message'      => 'Archivos subidos exitosamente.',
                'comprobantes' => $comprobantesGuardados
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error Crítico en Subida:', ['msg' => $e->getMessage()]);
            return response()->json(['message' => 'Error al procesar la subida.'], 500);
        }
    }

    /**
     * Muestra el detalle de un gasto específico.
     */
    public function show($id)
    {
        try {
            $gasto = Gasto::with('comprobantes')->find($id);

            if (!$gasto) {
                return response()->json(['message' => 'Gasto no encontrado.'], 404);
            }
            
            if ($gasto->user_id !== Auth::id()) {
                return response()->json(['message' => 'Acceso denegado.'], 403);
            }

            return response()->json($gasto);
        } catch (\Exception $e) {
            Log::error('Error en GastoController@show:', ['msg' => $e->getMessage()]);
            return response()->json(['message' => 'Error interno al cargar el detalle.'], 500);
        }
    }
}