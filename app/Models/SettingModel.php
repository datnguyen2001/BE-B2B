<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SettingModel extends Model
{
    use HasFactory;
    protected $table='setting';
    protected $fillable=[
        'logo',
        'hotline',
        'customer_support_email',
        'technical_support_email',
        'email',
        'facebook',
        'twitter',
        'zalo',
    ];
}
