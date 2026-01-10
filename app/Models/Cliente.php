<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'clientes'; 

    /**
     * Campos habilitados para asignación masiva.
     * Incluye los campos necesarios para la transición de Prospecto a Cliente.
     */
    protected $fillable = [
        'tipo',             // enum('CLIENTE', 'DISTRIBUIDOR', 'PROSPECTO')
        'nivel_educativo',  // Nivel del plantel
        'name',             // Nombre del plantel o cliente
        'contacto',         // Nombre del Director / Coordinador
        'email', 
        'telefono', 
        'tel_oficina', 
        'direccion', 
        'latitud',          // Coordenada GPS
        'longitud',         // Coordenada GPS
        'estado_id', 
        'moneda_id', 
        'condiciones_pago', 
        'rfc', 
        'fiscal', 
        'user_id',          // Representante asignado
        'status'            // activo/inactivo
    ];

    /**
     * Un cliente tiene muchas visitas.
     * Esto permite hacer $cliente->visitas para ver todo el historial de seguimiento.
     */
    public function visitas()
    {
        return $this->hasMany(Visita::class, 'cliente_id');
    }

    /**
     * Relación con el Estado (Geográfico).
     */
    public function estado()
    {
        return $this->belongsTo(Estado::class, 'estado_id');
    }

    /**
     * Relación con el Usuario (Representante que lo registró).
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relación con los Pedidos.
     */
    public function pedidos()
    {
        return $this->hasMany(Pedido::class);
    }

    /**
     * Scope para filtrar por tipo de cliente.
     */
    public function scopeProspectos($query)
    {
        return $query->where('tipo', 'PROSPECTO');
    }

    public function scopeClientesActivos($query)
    {
        return $query->where('tipo', 'CLIENTE')->where('status', 'activo');
    }
}