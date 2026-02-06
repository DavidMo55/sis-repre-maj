<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\FormatsAttributes;

class Comprobante extends Model
{
    use HasFactory, FormatsAttributes;

    protected $fillable = [
        'gasto_id',
        'name',
        'size',
        'extension',
        'public_url',
    ];

  
    public function gasto()
    {
        return $this->belongsTo(Gasto::class);
    }
}