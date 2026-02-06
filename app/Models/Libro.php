<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\FormatsAttributes;
class Libro extends Model
{
    use HasFactory, FormatsAttributes;
    
    protected $table = 'libros'; 

    protected $guarded = []; 

    public function detallesPedido()
    {
        return $this->hasMany(PedidoDetalle::class);
    }
}