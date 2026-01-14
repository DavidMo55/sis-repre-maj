<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute; 
use Illuminate\Support\Facades\Storage;

class Pedido extends Model
{
    use HasFactory;

    protected $table = 'pedidos'; 

    protected $fillable = [
        'numero_referencia',
        'user_id',
        'cliente_id',
        'tipo_pedido',
        'prioridad',
        'receptor_id',      // Enlace a pedido_receptores
        'receiver_type',    // 'cliente' o 'nuevo'
        'delivery_option',  // 'recoleccion', 'paqueteria', 'none'
        'paqueteria_nombre',
        'delivery_address',
        'comments',
        'status',
    ];
    
    protected $appends = ['display_id', 'total_unidades', 'total_costo']; 

    protected function displayId(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => $attributes['numero_referencia'] ?? 'PED-'.$attributes['id'],
        );
    }

    protected function totalUnidades(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->detalles()->sum('cantidad'),
        );
    }

    protected function totalCosto(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->detalles()->sum('costo_total'),
        );
    }

    public function detalles()
    {
        return $this->hasMany(PedidoDetalle::class, 'pedido_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function receptor()
    {
        return $this->belongsTo(PedidoReceptor::class, 'receptor_id');
    }

    public function representative()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}