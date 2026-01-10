<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute; 

class Pedido extends Model
{
    use HasFactory;

    protected $table = 'pedidos'; 

    /**
     * Campos habilitados para asignación masiva.
     * Se eliminó 'tipo_pedido' de aquí porque ahora se define por cada material.
     */
    protected $fillable = [
        'user_id',
        'cliente_id',
        'prioridad',
        'receiver_type',
        'receiver_nombre',   // Datos para "Ingresar Datos Nuevos"
        'receiver_telefono',
        'receiver_correo',
        'delivery_address',
        'delivery_option',
        'paqueteria_nombre',
        'status',
        'comments', 
        'numero_referencia', 
    ];
    
    /**
     * Atributos personalizados que se añaden al JSON.
     */
    protected $appends = ['display_id', 'total_unidades', 'total_costo']; 

    /**
     * Accessor para mostrar el Folio de referencia o un estado pendiente.
     */
    protected function displayId(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => $attributes['numero_referencia'] ?? 'PENDIENTE ID',
        );
    }

    /**
     * Accessor para calcular el total de unidades del pedido.
     */
    protected function totalUnidades(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->detalles()->sum('cantidad'),
        );
    }

    /**
     * Accessor para calcular el costo total (suma de todos los detalles).
     */
    protected function totalCosto(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->detalles()->sum('costo_total'),
        );
    }

    // --- RELACIONES ---

    /**
     * Relación con los materiales individuales (donde se guarda el tipo PROMO/VENTA).
     */
    public function detalles()
    {
        return $this->hasMany(PedidoDetalle::class, 'pedido_id');
    }

    /**
     * Relación con el Cliente (Plantel o Distribuidor).
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    /**
     * Relación con el Usuario (Representante que generó el pedido).
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}