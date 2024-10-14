<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductFavoritesModel extends Model
{
    use HasFactory;
    protected $table='product_favorites';
    protected $fillable=[
        'user_id',
        'product_id'
    ];
}
