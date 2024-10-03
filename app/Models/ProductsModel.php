<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductsModel extends Model
{
    use HasFactory;
    protected $table='products';
    protected $fillable=[
        'name',
        'name_en',
        'slug',
        'sku',
        'describe',
        'category_id',
        'unit',
        'en_unit',
        'contact_info',
        'minimum_quantity',
        'src',
        'quantity',
        'shop_id',
        'display',
        'status',
    ];

    public function category() {
        return $this->belongsTo(CategoryModel::class, 'category_id');
    }
}
