<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FollowShopsModel extends Model
{
    use HasFactory;
    protected $table='follow_shops';
    protected $fillable=[
        'user_id',
        'shop_id'
    ];
}
