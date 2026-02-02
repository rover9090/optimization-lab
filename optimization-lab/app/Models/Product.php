<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    // If your table is named 'product_data'
    protected $table = 'product_data';
    protected $fillable = ['part_no', 'description'];
}
