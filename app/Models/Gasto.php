<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gasto extends Model
{
    use HasFactory;

    protected $table = 'gastos';

    protected $fillable = [
        'user_id',
        'fecha',
        'concepto',
        'monto',
        'facturado', // Usamos el nombre exacto de tu BD
    ];

    protected $casts = [
        'fecha' => 'date',
        'facturado' => 'boolean', // Mapea el tinyint(1) de tu BD
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comprobantes()
    {
        return $this->hasMany(Comprobante::class, 'gasto_id');
    }
}