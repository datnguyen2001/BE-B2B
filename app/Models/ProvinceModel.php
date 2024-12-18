<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProvinceModel extends Model
{
    use HasFactory;
    protected $table='province';
    protected $fillable=[
        'province_id',
        'name'
    ];

    public function province()
    {
        return $this->belongsTo(ProvinceModel::class, 'province_id');
    }
}
