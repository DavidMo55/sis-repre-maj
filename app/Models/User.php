<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

// IMPORTACIONES CRÍTICAS PARA EVITAR ERROR 500
use App\Models\Delegate;
use App\Models\Estado;
use App\Models\Cliente;
use App\Models\Visita;
use App\Models\Gasto;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Buscar usuario por nombre en arreglos de credenciales.
     * Mapea 'username' (desde el frontend) a 'name' (en la base de datos).
     */
    public function findForArrays(array $credentials)
    {
        if (isset($credentials['username'])) {
            return $this->where('name', $credentials['username'])->first();
        }
        return parent::findForArrays($credentials);
    }
    
    /**
     * Atributos habilitados para asignación masiva.
     */
    protected $fillable = [
        'name',
        'full_name',
        'email',
        'password',
        // --- Perfil e Identidad ---
        'rfc',
        'phone',
        'personal_phone',
        'position',
        'employee_id',
        // --- Ubicación Geográfica ---
        'state_id',
        'city',
        'address',
        // --- Herramientas de Trabajo (Activos) ---
        'car_plates',
        'tag_number',
        'insurance_policy',
        'phone_model',
        'tablet_model',
        'computer_model',
        'business_card',
    ];

    /**
     * Atributos que deben permanecer ocultos al serializar el modelo a JSON.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Atributos que deben ser convertidos a tipos de datos específicos.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'state_id' => 'integer',
    ];
    
    /**
     * Helper para obtener el ID del dueño de la información.
     * AJUSTADO: Como tu tabla 'delegates' no tiene delegate_user_id, 
     * usamos el 'email' para encontrar al representante (user_id).
     */
    public function getEffectiveId()
    {
        if ($this->position === 'Delegado Autorizado') {
            // Buscamos en la tabla de delegados quién registró este email
            $delegation = Delegate::where('email', $this->email)->first();
            
            // Si existe la delegación, devolvemos el ID del representante (user_id)
            // De lo contrario, devolvemos su propio ID por seguridad
            return $delegation ? $delegation->user_id : $this->id;
        }
        
        return $this->id;
    }

    /**
     * RELACIÓN: Un representante puede autorizar a muchos delegados.
     */
    public function delegates()
    {
        return $this->hasMany(Delegate::class, 'user_id');
    }

    /**
     * RELACIÓN: Un usuario pertenece a un Estado geográfico.
     */
    public function estado()
    {
        return $this->belongsTo(Estado::class, 'state_id');
    }

    /**
     * RELACIÓN: Un representante gestiona múltiples clientes (planteles oficiales).
     */
    public function clientes()
    {
        return $this->hasMany(Cliente::class, 'user_id');
    }

    /**
     * RELACIÓN: Un representante registra sus actividades en la bitácora de visitas.
     */
    public function visitas()
    {
        return $this->hasMany(Visita::class, 'user_id');
    }

    /**
     * RELACIÓN: Un representante registra sus gastos operativos.
     */
    public function gastos()
    {
        return $this->hasMany(Gasto::class, 'user_id');
    }
}