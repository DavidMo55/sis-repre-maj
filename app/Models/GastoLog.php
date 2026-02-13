<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GastoLog extends Model
{
    protected $table = 'gasto_logs';

    protected $fillable = [
        'gasto_id',
        'user_id',
        'snapshot_anterior',
        'motivo_cambio'
    ];

    protected $casts = [
        'snapshot_anterior' => 'array', 
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function gasto()
    {
        return $this->belongsTo(Gasto::class);
    }
}