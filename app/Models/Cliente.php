<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'clientes'; 
    protected $fillable = [
        'tipo', 'name', 'contacto', 'email', 'telefono', 'tel_oficina', 
        'direccion', 'estado_id', 'moneda_id', 'condiciones_pago', 'rfc', 
        'fiscal', 'user_id', 'status'
    ];

   
    public function pedidos()
    {
        return $this->hasMany(Pedido::class);
    }
}