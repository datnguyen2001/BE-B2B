<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductDiscountsModel extends Model
{
    use HasFactory;
    protected $table='product_discounts';
    protected $fillable=[
        'product_id',
        'date_start',
        'date_end',
        'number',
        'discount',
        'display'
    ];
}
