<?php

// app/Models/JobOpening.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class JobOpening extends Model
{
    protected $fillable = ['title'];

    /**
     * Shape expected by the partial: collection of ['id', 'title', 'applicants'].
     *
     * Applicants are rows in `applications` whose `position` column holds the
     * job title (applications are kept when an opening is deleted, so they are
     * matched by title, not by foreign key).
     */
    public static function withApplicantCounts(): Collection
    {
        return static::query()
            ->orderBy('title')
            ->get()
            ->map(fn (self $job) => [
                'id'         => $job->id,
                'title'      => $job->title,
                'applicants' => (int) \Illuminate\Support\Facades\DB::table('applications')
                    ->where('position', $job->title)
                    ->count(),
            ]);
    }
}