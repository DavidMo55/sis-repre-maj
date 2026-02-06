<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\FormatsAttributes;
class PasswordTicket extends Model
{
    use HasFactory, FormatsAttributes;

    protected $fillable = [
        'user_id',
        'username_provided',
        'email_provided',
        'status',
    ];
}
