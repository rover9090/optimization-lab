<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsiteConfig extends Model
{
    protected $connection = 'middleware';
    protected $table = 'website_config';
}
