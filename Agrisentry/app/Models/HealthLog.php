<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HealthLog extends Model
{
    protected $appends = ['motion_anomaly'];
    public function getMotionAnomalyAttribute(): ?string
    {
        $text = strtolower(($this->movement ?? '').' '.($this->event_type ?? '').' '.($this->description ?? ''));
        foreach (['Prolonged Inactivity', 'Excessive Movement'] as $anomaly) {
            if (str_contains($text, strtolower($anomaly))) return $anomaly;
        }
        return null;
    }

    protected $fillable = [
        'goat_id',
        'event_type',
        'description',
        'temperature',
        'movement',
        'led_status',
        'severity',
    ];

    public function goat()
    {
        return $this->belongsTo(Goat::class);
    }
}