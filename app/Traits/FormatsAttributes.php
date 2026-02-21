<?php

namespace App\Traits;

trait FormatsAttributes
{
  
    protected static function bootFormatsAttributes()
    {
        static::saving(function ($model) {
            $exclude = [
                'correo',
                'password',
                'remember_token',
                'token',
                'slug',
                'url',
                'path',
                'external_id',
                'profile_photo_path',
                'status',
                'prioridad',
                'tipo_pedido',
                'delivery_option',
                'receiver_type',
                'proxima_accion',
                'resultado_visita',
                'tipo_licencia',
                'tipo',
                'public_url',
                'detalles',
                'nivel_educativo_planten',
                'libros_interes',
                'detalles',
                'email',
                'email_plantel'
            ];

            foreach ($model->getAttributes() as $key => $value) {
                if (
                    is_string($value) && 
                    !in_array($key, $exclude) && 
                    !str_ends_with($key, '_id') &&
                    !empty($value)
                ) {
                    $model->setAttribute($key, mb_strtoupper($value, 'UTF-8'));
                }
            }
        });
    }
}