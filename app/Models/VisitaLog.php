<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitaLog extends Model
{
    use HasFactory;

    // Nombre de la tabla explícito
    protected $table = 'visita_logs';

    /**
     * Los atributos que son asignables en masa.
     * * @var array
     */
    protected $fillable = [
        'visita_id',
        'user_id',
        'snapshot_anterior',
        'motivo_cambio',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     * * snapshot_anterior se maneja como array para facilitar la manipulación
     * del JSON almacenado en la base de datos.
     * * @var array
     */
    protected $casts = [
        'snapshot_anterior' => 'array',
    ];

    /**
     * Obtiene la visita a la que pertenece este log.
     */
    public function visita()
    {
        return $this->belongsTo(Visita::class);
    }

    /**
     * Obtiene el usuario que realizó la modificación.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}