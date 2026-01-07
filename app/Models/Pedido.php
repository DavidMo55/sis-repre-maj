<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute; 

class Pedido extends Model
{
    use HasFactory;

    protected $table = 'pedidos'; 

    // IMPORTANTE: Se deben añadir los nuevos campos aquí para permitir el guardado
    protected $fillable = [
        'user_id',
        'cliente_id',
        'receptor_id', 
        'receiver_type',
        'tipo_pedido',      // <--- NUEVO
        'prioridad',        // <--- NUEVO
        'delivery_address',
        'delivery_option',
        'paqueteria_nombre', // <--- NUEVO
        'status',
        'comments', 
        'numero_referencia', 
    ];
    
    protected $appends = ['display_id']; 

    protected function displayId(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => $attributes['numero_referencia'] ?? 'PENDIENTE ID',
        );
    }

    public function detalles()
    {
        return $this->hasMany(PedidoDetalle::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
}