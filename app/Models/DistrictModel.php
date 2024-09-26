<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DistrictModel extends Model
{
    use HasFactory;
    protected $table='district';
    protected $fillable=[
        'district_id',
        'province_id',
        'name'
    ];

    public function district()
    {
        return $this->belongsTo(DistrictModel::class, 'district_id');
    }
}
