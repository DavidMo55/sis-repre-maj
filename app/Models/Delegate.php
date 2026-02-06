<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\FormatsAttributes;
class Delegate extends Model
{
    use HasFactory, FormatsAttributes;

    protected $table = 'delegates';

    protected $fillable = [
        'user_id',
        'delegate_user_id', // Enlace a la tabla users del delegado
        'name',
        'email',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function account()
    {
        return $this->belongsTo(User::class, 'delegate_user_id');
    }
}