<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = ['order_number', 'locale', 'country_code', 'language_name', 'order_date'];

    public function lines() {
        return $this->hasMany(OrderLine::class);
    }

    public function returns() {
        return $this->hasMany(OrderReturn::class); // Named 'OrderReturn' to avoid PHP keyword issues
    }
}
