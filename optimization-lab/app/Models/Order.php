<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;
    protected $fillable = ['order_number', 'locale', 'country_code', 'language_name', 'order_date'];

    public function orderLine() {
        return $this->hasMany(OrderLine::class);
    }

    public function returns() {
        return $this->hasMany(Returns::class);
    }
}
