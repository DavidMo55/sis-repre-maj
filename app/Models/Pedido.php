<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute; 
use Carbon\Carbon; 

class Pedido extends Model
{
    use HasFactory;

    protected $table = 'pedidos'; 

    protected $fillable = [
        'user_id',
        'cliente_id',
        'receptor_id', 
        'receiver_type',
        'delivery_address',
        'delivery_option',
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
    
    public function receptor()
    {
        return $this->belongsTo(PedidoReceptor::class, 'receptor_id');
    }
}