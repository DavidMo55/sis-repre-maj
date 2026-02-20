<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\FormatsAttributes;
class PedidoReceptor extends Model
{
    use HasFactory, FormatsAttributes;

    protected $table = 'pedido_receptores';

    protected $fillable = [
        'cliente_id',
        'user_id',
        'nombre',
        'rfc',
        'receiver_regimen_fiscal',
        'telefono',
        'correo',
        'direccion',
    ];

  
    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
    
   
    public function pedidos()
    {
        return $this->hasMany(Pedido::class, 'receptor_id');
    }
}