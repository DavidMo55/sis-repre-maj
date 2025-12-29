<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gasto extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'fecha',
        'concepto',
        'monto',
        'facturado',
    ];

    protected $casts = [
        'fecha' => 'date',
        'facturado' => 'boolean',
    ];

    /**
     * Relación: Un gasto pertenece a un representante (User).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación: Un gasto puede tener muchos comprobantes.
     */
    public function comprobantes()
    {
        return $this->hasMany(Comprobante::class, 'gasto_id');
    }
}