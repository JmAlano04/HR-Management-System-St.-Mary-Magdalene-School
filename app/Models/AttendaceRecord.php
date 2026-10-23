<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class AttendanceRecord extends Model
{
    public const STATUSES = ['Present', 'Late', 'Half Day', 'Absent'];

    protected $table = 'attendance_records'; // 2026_10_18_150333_craete_attendance_record_table

    protected $fillable = [
        'employee_id', 'date', 'time_in', 'time_out', 'hours_worked', 'status', 'archived',
    ];

    protected $casts = [
        'date'         => 'date',
        'hours_worked' => 'decimal:2',
        'archived'     => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(SchoolEmployee::class, 'employee_id');
    }

    /* ---------- Display helpers ---------- */

    public function getTimeInLabelAttribute(): string
    {
        return $this->time_in ? Carbon::parse($this->time_in)->format('h:i A') : '—';
    }

    public function getTimeOutLabelAttribute(): string
    {
        return $this->time_out ? Carbon::parse($this->time_out)->format('h:i A') : '—';
    }

    public function getHoursLabelAttribute(): string
    {
        return $this->hours_worked !== null ? number_format((float) $this->hours_worked, 2) . ' hrs' : '—';
    }
}