<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'username_provided',
        'email_provided',
        'status',
    ];
}
