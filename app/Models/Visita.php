<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Visita extends Model
{
    use HasFactory;

    protected $table = 'visitas';

    /**
     * Campos habilitados para asignación masiva.
     * Se eliminaron los campos de texto del plantel ya que ahora residen en 'clientes'.
     */
    protected $fillable = [
        'user_id',
        'cliente_id', 
        'fecha',
        'persona_entrevistada',
        'cargo',
        'libros_interes',
        'material_entregado',
        'material_cantidad',
        'comentarios',
        'proxima_visita_estimada',
        'proxima_accion',
        'es_primera_visita',
        'resultado_visita',
    ];

    protected $casts = [
        'fecha' => 'date',
        'proxima_visita_estimada' => 'date',
        'material_entregado' => 'boolean',
        'es_primera_visita' => 'boolean'
    ];

    /**
     * SCOPE: Búsqueda avanzada.
     * Busca el término en el nombre del cliente (tabla relacionada) o en el entrevistado.
     */
    public function scopeSearch(Builder $query, $term)
    {
        if ($term) {
            $query->where(function($q) use ($term) {
                // Busca en la tabla de clientes a través de la relación
                $q->whereHas('cliente', function($sub) use ($term) {
                    $sub->where('name', 'like', "%{$term}%");
                })
                // O busca en la persona entrevistada registrada en la visita
                ->orWhere('persona_entrevistada', 'like', "%{$term}%");
            });
        }
    }

    /**
     * SCOPE: Filtrar por rango de fechas.
     */
    public function scopeByDateRange(Builder $query, $from, $to)
    {
        if ($from) {
            $query->whereDate('fecha', '>=', $from);
        }
        if ($to) {
            $query->whereDate('fecha', '<=', $to);
        }
    }

    /**
     * SCOPE: Filtrar por resultado.
     */
    public function scopeByResultado(Builder $query, $resultado)
    {
        if ($resultado) {
            $query->where('resultado_visita', $resultado);
        }
    }

    /**
     * Relación con el Cliente.
     * Vincula la visita con la información centralizada del plantel/persona.
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    /**
     * Relación con el Representante (Usuario).
     */
    public function representative()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}