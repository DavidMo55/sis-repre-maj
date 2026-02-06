<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\FormatsAttributes;
class Gasto extends Model
{
    use HasFactory, FormatsAttributes;

    protected $table = 'gastos';

    protected $fillable = [
        'user_id',
        'fecha',
        'estado_nombre',
        'concepto',
        'monto',
        'facturado',
        'detalles', 
    ];

    protected $casts = [
        'fecha'     => 'date',
        'facturado' => 'boolean',
        'detalles'  => 'array', 
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