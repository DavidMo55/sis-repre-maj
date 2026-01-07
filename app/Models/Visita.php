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
        'nombre_plantel',
        'direccion_plantel',
        'latitud',
        'longitud',
        'telefono_plantel',
        'email_plantel',
        'director_plantel',
        'nivel_educativo_plantel',
        'estado_id'
    ];

    protected $casts = [
        'fecha' => 'date',
        'proxima_visita_estimada' => 'date',
        'material_entregado' => 'boolean',
        'es_primera_visita' => 'boolean'
    ];

    /**
     * SCOPE: Filtrar por término de búsqueda (Nombre del plantel o entrevistado).
     * Corresponde al parámetro 'search' enviado desde el frontend.
     */
    public function scopeSearch(Builder $query, $term)
    {
        if ($term) {
            $query->where(function($q) use ($term) {
                $q->where('nombre_plantel', 'like', "%{$term}%")
                  ->orWhere('persona_entrevistada', 'like', "%{$term}%");
            });
        }
    }

    /**
     * SCOPE: Filtrar por rango de fechas de la visita.
     * Corresponde a los parámetros 'desde' y 'hasta'.
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
     * SCOPE: Filtrar por resultado (seguimiento, compra, rechazo).
     * Corresponde al parámetro 'resultado'.
     */
    public function scopeByResultado(Builder $query, $resultado)
    {
        if ($resultado) {
            $query->where('resultado_visita', $resultado);
        }
    }

    /**
     * Relación con el Cliente (Opcional, para registros que ya son clientes oficiales).
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

    /**
     * Relación con el Estado Geográfico (Heredada para visualización).
     */
    public function estado()
    {
        return $this->belongsTo(Estado::class, 'estado_id');
    }
}