<?php

namespace App\Models;

use App\Services\SmsGatewayService;
use App\Services\VetAdvisoryService;
use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    protected $fillable = [
        'goat_id',
        'alert_type',
        'message',
        'recommendation',
        'severity',
        'status',
    ];

    protected static function booted(): void
    {
        static::created(function (Alert $alert) {
            if (! $alert->getRawOriginal('recommendation')) {
                $alert->recommendation = (str_contains(strtolower($alert->alert_type), 'temperature')
                    ? VetAdvisoryService::temperatureRecommendation($alert->recordedTemperature()) : null)
                    ?? VetAdvisoryService::recommendationFor($alert->alert_type, $alert->severity);
                $alert->saveQuietly();
            }

            app(\App\Services\HealthAlertEmail::class)->notify($alert);
            if (config('services.semaphore.key')) {
                app(SmsGatewayService::class)->notifyForAlert($alert);
            }
        });
    }

    public function getRecommendationAttribute($value): ?string
    {
        if (strtolower($this->status ?? '') === 'active' && str_contains(strtolower($this->alert_type ?? ''), 'temperature')) {
            return VetAdvisoryService::temperatureRecommendation($this->recordedTemperature()) ?? $value;
        }
        return $value;
    }

    public function goat()
    {
        return $this->belongsTo(Goat::class);
    }

    private function recordedTemperature(): ?float
    {
        // Existing alerts store their triggering reading in the message.
        // Prefer it so advice matches the card rather than a later goat reading.
        if (preg_match('/(-?\d+(?:\.\d+)?)\s*°\s*C/u', $this->message ?? '', $matches)) {
            return (float) $matches[1];
        }

        return $this->goat?->temperature;
    }
}
