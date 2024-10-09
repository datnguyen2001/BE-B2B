<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuotesModel extends Model
{
    use HasFactory;
    protected $table='quotes';
    protected $fillable=[
        'request_supplier_id',
        'user_id',
        'name',
        'phone',
        'price',
        'address',
        'content',
        'content_en'
    ];
}
