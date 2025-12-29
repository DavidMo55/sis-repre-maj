<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Pedido; 
use App\Models\Libro;  

class PedidoDetalle extends Model
{
    use HasFactory;

    protected $table = 'pedido_detalles';

    protected $fillable = [
        'pedido_id',
        'libro_id',
        'tipo_licencia',
        'cantidad',
        'precio_unitario', 
        'costo_total',     
    ];

   
    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
    }

    
    public function libro()
    {
        return $this->belongsTo(Libro::class); 
    }
}