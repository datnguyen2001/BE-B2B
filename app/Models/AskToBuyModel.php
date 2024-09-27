<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AskToBuyModel extends Model
{
    use HasFactory;
    protected $table='ask_to_buy';
    protected $fillable=[
        'user_id',
        'shop_id',
        'product_id',
        'quantity',
        'content'
    ];
}
