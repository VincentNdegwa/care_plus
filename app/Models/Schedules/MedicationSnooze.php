<?php

namespace App\Models\Schedules;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicationSnooze extends Model
{
    protected $fillable = [
        'medication_schedule_id',
        'snooze_time',
        'status'
    ];

    protected $casts = [
        'snooze_time' => 'datetime',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $enums = [
        'status' => ['Pending', 'Snoozed', 'Dismissed']
    ];

    /**
     * Get the medication schedule that owns the snooze.
     */
    public function medicationSchedule(): BelongsTo
    {
        return $this->belongsTo(MedicationSchedule::class, 'medication_schedule_id');
    }

    /**
     * Scope a query to only include pending snoozes.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'Pending');
    }

    /**
     * Scope a query to only include snoozed records.
     */
    public function scopeSnoozed($query)
    {
        return $query->where('status', 'Snoozed');
    }

    /**
     * Scope a query to only include dismissed records.
     */
    public function scopeDismissed($query)
    {
        return $query->where('status', 'Dismissed');
    }

    /**
     * Check if the snooze is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'Pending';
    }

    /**
     * Check if the snooze is active (not dismissed).
     */
    public function isActive(): bool
    {
        return $this->status !== 'Dismissed';
    }

    /**
     * Dismiss the snooze.
     */
    public function dismiss(): bool
    {
        return $this->update(['status' => 'Dismissed']);
    }
}
