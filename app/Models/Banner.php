<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $fillable = ['path', 'alt', 'sort_order', 'uploaded_by'];
}