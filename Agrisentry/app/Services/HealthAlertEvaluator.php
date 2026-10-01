<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\Collar;
use App\Models\Goat;

class HealthAlertEvaluator
{
    private const NORMAL_LOW = 33.0;
    private const URGENT_LOW = 32.0;
    private const NORMAL_HIGH = 38.5;
    private const URGENT_HIGH = 39.5;
    private const BATTERY_LOW = 20;
    private const DEDUPE_MINUTES = 30;

    /**
     * Single source of truth for goat status bands, per AgriSentry's
     * skin-surface temperature spec: blue/low < 33.0°C, green/normal
     * 33.0-38.5°C, red/high > 38.5°C.
     */
    public static function resolveStatus(?float $temperature): array
    {
        if ($temperature === null) {
            return ['status' => 'Normal', 'alert_reason' => 'No alert.'];
        }

        if ($temperature > self::URGENT_HIGH) {
            return ['status' => 'Urgent', 'alert_reason' => 'High temperature detected (possible fever or heat stress).'];
        }

        if ($temperature < self::URGENT_LOW) {
            return ['status' => 'Urgent', 'alert_reason' => 'Low temperature detected (possible hypothermia).'];
        }

        if ($temperature > self::NORMAL_HIGH) {
            return ['status' => 'Warning', 'alert_reason' => 'Slightly elevated temperature; monitor and recheck promptly.'];
        }

        if ($temperature < self::NORMAL_LOW) {
            return ['status' => 'Warning', 'alert_reason' => 'Slightly low temperature; monitor and recheck promptly.'];
        }

        return ['status' => 'Normal', 'alert_reason' => 'No alert.'];
    }

    /**
     * @return Alert[] alerts actually created (empty if nothing urgent, or deduped)
     */
    public function evaluate(Goat $goat, ?Collar $collar = null): array
    {
        $created = [];
        $temp = $goat->temperature !== null ? (float) $goat->temperature : null;

        // Retire previous temperature bands when a new reading changes the condition.
        // Keep other alert types active and preserve their original recommendations.
        if ($temp !== null) {
            $currentType = match (true) {
                $temp > self::URGENT_HIGH => 'High Temperature',
                $temp < self::URGENT_LOW => 'Low Temperature',
                $temp > self::NORMAL_HIGH => 'High Temperature Warning',
                $temp < self::NORMAL_LOW => 'Low Temperature Warning',
                default => null,
            };
            $previous = Alert::where('goat_id', $goat->id)->where('status', 'Active')
                ->whereIn('alert_type', ['High Temperature', 'Low Temperature', 'High Temperature Warning', 'Low Temperature Warning']);
            if ($currentType !== null) $previous->where('alert_type', '!=', $currentType);
            $previous->update(['status' => 'Resolved']);
        }

        if ($temp !== null && $temp > self::URGENT_HIGH) {
            $created[] = $this->raise($goat, 'High Temperature', "High skin temperature reading: {$temp}°C.", 'High');
        } elseif ($temp !== null && $temp < self::URGENT_LOW) {
            $created[] = $this->raise($goat, 'Low Temperature', "Low skin temperature reading: {$temp}°C.", 'High');
        } elseif ($temp !== null && $temp > self::NORMAL_HIGH) {
            $created[] = $this->raise($goat, 'High Temperature Warning', "Slightly high temperature: {$temp}°C.", 'Warning');
        } elseif ($temp !== null && $temp < self::NORMAL_LOW) {
            $created[] = $this->raise($goat, 'Low Temperature Warning', "Slightly low temperature: {$temp}°C.", 'Warning');
        }

        if ($collar?->battery_level !== null && $collar->battery_level <= self::BATTERY_LOW) {
            $created[] = $this->raise($goat, 'Low Battery', "Collar {$collar->collar_code} battery at {$collar->battery_level}%.", 'Medium');
        }

        return array_values(array_filter($created));
    }

    public function raiseMotionAlert(Goat $goat, ?string $movement): ?Alert
    {
        return match ($movement) {
            'Prolonged Inactivity' => $this->raise($goat, $movement, 'Continuous low movement for at least 90 minutes.', 'Urgent'),
            'Excessive Movement' => $this->raise($goat, $movement, 'Continuous high-intensity movement for more than 15 seconds.', 'Urgent'),
            default => null,
        };
    }

    private function raise(Goat $goat, string $type, string $message, string $severity): ?Alert
    {
        $recentlyAlerted = Alert::where('goat_id', $goat->id)
            ->where('status', 'Active')
            ->where('alert_type', $type)
            ->where('created_at', '>=', now()->subMinutes(self::DEDUPE_MINUTES))
            ->exists();

        if ($recentlyAlerted) {
            return null;
        }

        // Recommendation + SMS are handled by Alert's model event.
        return Alert::create([
            'goat_id' => $goat->id,
            'alert_type' => $type,
            'message' => $message,
            'severity' => $severity,
            'status' => 'Active',
        ]);
    }
}
