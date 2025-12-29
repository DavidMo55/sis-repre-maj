<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Visita extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'cliente_id',
        'fecha',
        'persona_entrevistada',
        'cargo',
        'libros_interes',
        'material_entregado',
        'comentarios',
        'proxima_visita_estimada',
        'es_primera_visita'
    ];

    protected $casts = [
        'fecha' => 'date',
        'proxima_visita_estimada' => 'date',
        'material_entregado' => 'boolean',
        'es_primera_visita' => 'boolean'
    ];

    public function representative()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
}