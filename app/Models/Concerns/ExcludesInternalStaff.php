<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait ExcludesInternalStaff
{
    public function scopeExcludeInternalStaff(Builder $query): Builder
    {
        return $query->whereDoesntHave('user', fn (Builder $userQuery) => $userQuery
            ->role(['Admin', 'HR', 'Accounting'])
            ->whereRaw('LOWER(email) LIKE ?', ['%@stmary.edu']));
    }
}
