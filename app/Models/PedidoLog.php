<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PedidoLog extends Model
{
    protected $table = 'pedido_logs';

    protected $fillable = [
        'pedido_id',
        'user_id',
        'snapshot_anterior',
        'motivo_cambio'
    ];

    protected $casts = [
        'snapshot_anterior' => 'array', // Laravel lo convierte automáticamente a arreglo de PHP
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
    }
}