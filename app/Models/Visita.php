<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

// Importaciones necesarias para las relaciones
use App\Models\User;
use App\Models\Cliente;
use App\Models\Estado;

class Visita extends Model
{
    use HasFactory;

    protected $table = 'visitas';

    /**
     * Atributos habilitados para asignación masiva.
     * * El 'user_id' siempre almacenará el ID del Representante (dueño de la cuenta),
     * permitiendo que tanto él como sus delegados compartan la misma información.
     */
    protected $fillable = [
        'user_id',
        'cliente_id', 
        'estado_id', // Añadido para vinculación geográfica directa
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

    /**
     * Conversión de tipos automática.
     */
    protected $casts = [
        'fecha' => 'date',
        'proxima_visita_estimada' => 'date',
        'material_entregado' => 'boolean',
        'es_primera_visita' => 'boolean'
    ];

    /**
     * SCOPE: Búsqueda avanzada.
     * Permite al representante o delegado buscar visitas por nombre de plantel o contacto.
     */
    public function scopeSearch(Builder $query, $term)
    {
        if ($term) {
            $query->where(function($q) use ($term) {
                // Buscamos en el nombre del cliente (plantel) vinculado
                $q->whereHas('cliente', function($sub) use ($term) {
                    $sub->where('name', 'like', "%{$term}%");
                })
                // O buscamos en el contacto directo registrado en la visita
                ->orWhere('persona_entrevistada', 'like', "%{$term}%");
            });
        }
    }

    /**
     * SCOPE: Filtrado por periodo de tiempo.
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
     * SCOPE: Filtrado por resultado.
     */
    public function scopeByResultado(Builder $query, $resultado)
    {
        if ($resultado) {
            $query->where('resultado_visita', $resultado);
        }
    }

    /**
     * RELACIÓN: Vínculo con el Cliente (Plantel).
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    /**
     * RELACIÓN: Vínculo con el Representante / Dueño (User).
     */
    public function representative()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * RELACIÓN: Vínculo con el Estado geográfico.
     */
    public function estado()
    {
        return $this->belongsTo(Estado::class, 'estado_id');
    }
}