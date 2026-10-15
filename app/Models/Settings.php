<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'webtitle',
        'webdescription',
        'webkeys',
        'logo',
        'brand_font_primary',
        'brand_font_secondary',
        'favicon',
        'map'
    ];
}
