<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardTask extends Model
{
    protected $fillable = ['user_id', 'task_date', 'title'];

    protected function casts(): array
    {
        return [
            'task_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function toCard(): array
    {
        return [
            'id'    => $this->id,
            'title' => $this->title,
            'date'  => $this->task_date->toDateString(),
        ];
    }
}