<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute; 

class Pedido extends Model
{
    use HasFactory;

    protected $table = 'pedidos'; 

    protected $fillable = [
        'user_id',
        'cliente_id',
        'prioridad',
        'receiver_type',
        'receiver_nombre',
        'receiver_telefono',
        'receiver_correo',
        'delivery_address',
        'delivery_option',
        'paqueteria_nombre',
        'status',
        'comments', 
        'numero_referencia', 
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

    public function representative()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}