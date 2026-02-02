<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnLine extends Model
{
    
    protected $fillable = ['return_id', 'product_id', 'qty'];

    public function returns() {
        return $this->belongsTo(Returns::class);
    }

    public function product() {
        return $this->belongsTo(Product::class);
    }
}
