<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Gasto;
use App\Models\Comprobante;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Spatie\Dropbox\Client as DropboxClient; 

class GastoController extends Controller
{
    private $allowedMimes = ['jpg', 'jpeg', 'png', 'pdf'];
    private $maxFileSize = 3072; // 3MB

    /**
     * Listado de gastos del representante.
     */
    public function index(Request $request)
    {
        try {
            $userId = Auth::id();
            $query = Gasto::where('user_id', $userId)->with('comprobantes');

            if ($request->filled('fecha_desde') && $request->filled('fecha_hasta')) {
                $query->whereBetween('fecha', [$request->fecha_desde, $request->fecha_hasta]);
            }
            
            $query->orderByRaw('EXISTS (SELECT 1 FROM comprobantes WHERE comprobantes.gasto_id = gastos.id) ASC')
                  ->orderBy('fecha', 'desc');

            return response()->json($query->paginate(10));
        } catch (\Exception $e) {
            Log::error('Error al listar gastos:', ['exception' => $e->getMessage()]);
            return response()->json(['message' => 'Error al listar gastos.'], 500);
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
                return response()->json(['message' => 'Acceso denegado. Este registro no te pertenece.'], 403);
            }

            return response()->json($gasto);

        } catch (\Exception $e) {
            Log::error('Error en GastoController@show:', ['msg' => $e->getMessage()]);
            return response()->json(['message' => 'Error interno al cargar el detalle.'], 500);
        }
    }

    /**
     * Sube comprobantes a Dropbox y los registra.
     */
    public function storeComprobante(Request $request)
    {
        $request->validate([
            'gasto_id' => 'required|exists:gastos,id',
            'files' => 'required|array',
            'files.*' => 'file|mimes:' . implode(',', $this->allowedMimes) . '|max:' . $this->maxFileSize,
        ]);
        
        $gasto = Gasto::findOrFail($request->gasto_id);

        if ($gasto->user_id !== Auth::id()) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $token = config('filesystems.disks.dropbox.accessToken');

        try {
            DB::beginTransaction();
            $dropboxClient = new DropboxClient($token);

            foreach ($request->file('files') as $file) {
                $timestamp = Carbon::now()->format('ymd-His'); 
                $extension = $file->getClientOriginalExtension();
                $fileName = "{$timestamp}_" . Auth::id() . "-{$gasto->id}.{$extension}";
                
                $path = "/comprobantes/{$gasto->id}/{$fileName}";
                
                Storage::disk('dropbox')->put($path, file_get_contents($file));

                $publicUrl = null;
                try {
                    $response = $dropboxClient->createSharedLinkWithSettings($path, ["requested_visibility" => "public"]);
                    $publicUrl = $response['url'];
                } catch (\Exception $e) {
                    $links = $dropboxClient->listSharedLinks($path);
                    $publicUrl = !empty($links) ? $links[0]['url'] : null;
                }

                if ($publicUrl) {
                    $publicUrl = str_replace('dl=0', 'dl=1', $publicUrl);
                }

                $comprobante = Comprobante::create([
                    'gasto_id' => $gasto->id,
                    'name' => $fileName,
                    'size' => round($file->getSize() / 1024),
                    'extension' => $extension,
                    'public_url' => $publicUrl,
                ]);

                $comprobantesGuardados[] = $comprobante;
            }

            DB::commit();
            return response()->json(['message' => 'Archivos subidos exitosamente.', 'comprobantes' => $comprobantesGuardados], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error Dropbox:', ['msg' => $e->getMessage()]);
            return response()->json(['message' => 'Error al subir a Dropbox.'], 500);
        }
    }
}