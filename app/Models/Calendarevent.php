<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalendarEvent extends Model
{
    protected $fillable = ['title', 'description', 'event_date', 'created_by'];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
        ];
    }
}