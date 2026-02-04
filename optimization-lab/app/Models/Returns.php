<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Returns extends Model
{
    protected $table = 'returns'; // Mapping to your 'returns' table

    public function order() {
        return $this->belongsTo(Order::class);
    }

    public function returnLine() {
        return $this->hasMany(ReturnLine::class, 'return_id');
    }
}
