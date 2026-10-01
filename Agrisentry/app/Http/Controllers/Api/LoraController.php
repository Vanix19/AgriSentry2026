<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\PublishFirebaseTelemetry;
use App\Models\Collar;
use App\Models\HealthLog;
use App\Services\HealthAlertEvaluator;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class LoraController extends Controller
{
    // Movement/LED/severity codes per firmware/agrisentry-collar/agrisentry-collar.ino
    private const MOVEMENT_LABELS = [
        0 => 'Normal',
        1 => 'Active',
        2 => 'Low Movement',
        3 => 'Prolonged Inactivity',
        4 => 'Excessive Movement',
    ];

    private const LED_LABELS = [
        0 => 'Green',
        1 => 'Red',
        2 => 'Blue',
    ];

    private const SEVERITY_LABELS = [
        0 => 'Normal',
        1 => 'Warning',
        2 => 'Urgent',
    ];

    public function __construct(private readonly HealthAlertEvaluator $evaluator)
    {
    }

    /**
     * Accepts either generic flat JSON (device_id/dev_eui, temperature, movement,
     * battery_level, led_status, severity), a TTN v3-shaped payload with a payload
     * formatter already applied (uplink_message.decoded_payload.*), or a raw TTN
     * uplink where uplink_message.frm_payload is still base64-encoded collar bytes
     * (6 bytes as sent by the firmware: [tempX10 hi][tempX10 lo][battery][movementCode][ledCode][severityCode]).
     */
    public function uplink(Request $request)
    {
        try {
            $expected = config('services.lorawan.secret');
            $provided = $request->header('X-LoRaWAN-Secret') ?? $request->input('secret');

            if (!is_string($expected) || $expected === '') {
                return response()->json(['message' => 'LoRaWAN integration is not configured.'], 503);
            }
            if (!is_string($provided) || !hash_equals($expected, $provided)) {
                return response()->json(['message' => 'Unauthorized.'], 401);
            }

            $payload = $request->all();

            $deviceId = Arr::get($payload, 'device_id')
                ?? Arr::get($payload, 'deviceId')
                ?? Arr::get($payload, 'end_device_ids.device_id');

            $devEui = Arr::get($payload, 'dev_eui')
                ?? Arr::get($payload, 'devEui')
                ?? Arr::get($payload, 'end_device_ids.dev_eui');

            // TTN's payload formatter (ttn-payload-formatter.js) already decodes
            // frm_payload into {temperature, battery_level, movement, led_status,
            // severity} — see the 'decoded_payload' shape logged from a live uplink.
            // Fall back to manually decoding the raw frm_payload bytes if that's
            // ever missing.
            $decoded = Arr::get($payload, 'uplink_message.decoded_payload', []);
            $frmPayload = Arr::get($payload, 'uplink_message.frm_payload');
            if (!is_array($decoded) || ($frmPayload !== null && !is_string($frmPayload))) {
                return response()->json(['message' => 'Invalid TTN payload.'], 422);
            }

            $temperature = Arr::get($payload, 'temperature') ?? Arr::get($decoded, 'temperature');
            $movement = Arr::get($payload, 'movement') ?? Arr::get($decoded, 'movement');
            $battery = Arr::get($payload, 'battery_level') ?? Arr::get($decoded, 'battery_level');
            $severity = Arr::get($payload, 'severity') ?? Arr::get($decoded, 'severity');
            $ledStatus = Arr::get($payload, 'led_status')
                ?? Arr::get($decoded, 'led_status')
                ?? (is_numeric($temperature) ? $this->ledLabel((float) $temperature) : null);

            if ($frmPayload) {
                $raw = $this->decodeFrmPayload($frmPayload);

                if ($raw) {
                    // Raw packet layout is authoritative; an old TTN formatter may
                    // otherwise misread motion/flags as battery or discard motion.
                    $temperature = $raw['temperature'];
                    $movement = $raw['movement'];
                    $battery = $raw['battery'];
                    $ledStatus = $raw['led_status'];
                    $severity = $raw['severity'];
                } else {
                    return response()->json(['message' => 'Invalid TTN payload.'], 422);
                }
            }

            if (is_string($movement)) {
                $labels = array_combine(array_map('strtolower', self::MOVEMENT_LABELS), self::MOVEMENT_LABELS);
                $movement = $labels[strtolower(trim($movement))] ?? $movement;
            }

            if (! $deviceId && ! $devEui) {
                return response()->json(['message' => 'device_id or dev_eui is required.'], 422);
            }

            $validator = Validator::make([
                'device_id' => $deviceId, 'dev_eui' => $devEui,
                'temperature' => $temperature, 'movement' => $movement,
                'battery_level' => $battery, 'severity' => $severity, 'led_status' => $ledStatus,
            ], [
                'device_id' => 'nullable|string|max:255', 'dev_eui' => 'nullable|string|max:255',
                'temperature' => 'nullable|numeric|between:-55,125',
                'movement' => 'nullable|string|in:Normal,Active,Low Movement,Prolonged Inactivity,Excessive Movement',
                'battery_level' => 'nullable|numeric|between:0,100',
                'severity' => 'nullable|string|in:Normal,Warning,Urgent',
                'led_status' => 'nullable|string|in:Green,Red,Blue',
            ]);
            if ($validator->fails()) {
                return response()->json(['message' => 'Invalid sensor reading.', 'errors' => $validator->errors()], 422);
            }
            if ($temperature === null && $movement === null && $battery === null) {
                return response()->json(['message' => 'At least one valid sensor reading is required.'], 422);
            }

            $collar = Collar::query()->where(function ($query) use ($deviceId, $devEui) {
                if ($deviceId) $query->where('collar_code', $deviceId);
                if ($devEui) $query->orWhere('dev_eui', $devEui);
            })->first();

            if (! $collar) {
                return response()->json(['message' => 'Unknown device/collar. Register the collar first.'], 404);
            }

            $battery = $battery !== null ? (int) round((float) $battery) : null;

            $collar->update([
                'battery_level' => $battery ?? $collar->battery_level,
                'last_seen' => now(),
                'device_status' => 'Active',
            ]);

            $healthLog = null;
            $goat = $collar->goat;

            if ($goat) {
                $goat->update(array_filter([
                    'battery' => $battery, 'movement' => $movement,
                ], fn ($value) => $value !== null));
                $healthLog = HealthLog::create([
                    'goat_id' => $goat->id,
                    'event_type' => 'LoRaWAN Uplink',
                    'description' => "Automatic reading from collar {$collar->collar_code}",
                    'temperature' => $temperature,
                    'movement' => $movement,
                    'led_status' => $temperature !== null ? $this->ledLabel((float) $temperature) : $ledStatus,
                    'severity' => in_array($movement, ['Prolonged Inactivity', 'Excessive Movement'], true) ? 'Urgent' : ($temperature !== null ? HealthAlertEvaluator::resolveStatus((float) $temperature)['status'] : $severity),
                ]);

                if ($temperature !== null) {
                    $status = HealthAlertEvaluator::resolveStatus((float) $temperature);

                    $goat->update(array_merge([
                        'temperature' => $temperature,
                        'movement' => $movement ?? $goat->movement,
                        'battery' => $battery ?? $goat->battery,
                    ], $status));
                }

                // A normal temperature must not hide a current motion emergency.
                if ($movement !== null) {
                    $motionUrgent = in_array($movement, ['Prolonged Inactivity', 'Excessive Movement'], true);
                    $goat->update($motionUrgent
                        ? ['status' => 'Urgent', 'alert_reason' => $movement]
                        : HealthAlertEvaluator::resolveStatus($goat->temperature !== null ? (float) $goat->temperature : null));
                    $resolved = $goat->alerts()->where('status', 'Active')
                        ->whereIn('alert_type', ['Prolonged Inactivity', 'Excessive Movement']);
                    if ($motionUrgent) $resolved->where('alert_type', '!=', $movement);
                    $resolved->update(['status' => 'Resolved']);
                } elseif (in_array($goat->movement, ['Prolonged Inactivity', 'Excessive Movement'], true)) {
                    $goat->update(['status' => 'Urgent', 'alert_reason' => $goat->movement]);
                }

                $this->evaluator->evaluate($goat->fresh(), $collar);
                $this->evaluator->raiseMotionAlert($goat->fresh(), $movement);
            }

            // A Firebase outage must not reject already-persisted collar readings.
            try {
                if (config('firebase.enabled')) {
                    PublishFirebaseTelemetry::dispatch($collar->id)
                        ->onConnection(config('firebase.queue_connection'));
                }
            } catch (\Throwable $error) {
                Log::warning('Firebase telemetry publish failed', ['collar_id' => $collar->id, 'type' => get_class($error)]);
            }

            return response()->json([
                'message' => 'Uplink processed.',
                'collar' => $collar,
                'health_log' => $healthLog,
            ], 201);
        } catch (\Throwable $e) {
            Log::error($e);

            return response()->json([
                'message' => 'Unable to process uplink.',
            ], 500);
        }
    }

    /**
     * @return array{temperature: float, movement: string, battery: int, led_status: string, severity: string}|null
     */
    private function decodeFrmPayload(string $frmPayload): ?array
    {
        $raw = base64_decode($frmPayload, true);
        if ($raw === false || !in_array(strlen($raw), [5, 6], true)) return null;
        $bytes = array_values(unpack('C*', $raw) ?: []);

        if (count($bytes) === 5) {
            // User's Heltec sketch: temp high, temp low, MotionState, flags, battery.
            [$hi, $lo, $motion, $flags, $battery] = $bytes;
            $scaled = ($hi << 8) | $lo;
            if ($scaled > 32767) $scaled -= 65536;
            $temperature = ($flags & 0x44) || $scaled === -1 ? null : $scaled / 10.0;
            $movement = ($flags & 0x20) ? null : ([0 => 'Normal', 1 => 'Prolonged Inactivity', 2 => 'Active', 3 => 'Excessive Movement'][$motion] ?? 'Unknown');
            return [
                'temperature' => $temperature, 'movement' => $movement,
                'battery' => $battery,
                'led_status' => $temperature !== null ? $this->ledLabel($temperature) : null,
                'severity' => in_array($movement, ['Prolonged Inactivity', 'Excessive Movement'], true)
                    ? 'Urgent' : ($temperature !== null ? HealthAlertEvaluator::resolveStatus($temperature)['status'] : null),
            ];
        }

        [$tempHi, $tempLo, $battery, $movementCode, , $severityCode] = $bytes;

        $tempX10 = ($tempHi << 8) | $tempLo;
        if ($tempX10 > 32767) {
            $tempX10 -= 65536; // int16_t sign extension
        }

        $temperature = $tempX10 / 10.0;

        return [
            'temperature' => $temperature,
            'movement' => $this->movementLabel($movementCode),
            'battery' => $battery,
            'led_status' => $this->ledLabel($temperature),
            'severity' => $this->severityLabel($severityCode),
        ];
    }

    private function movementLabel(?int $code): ?string
    {
        return $code === null ? null : (self::MOVEMENT_LABELS[$code] ?? 'Unknown');
    }

    private function severityLabel(?int $code): ?string
    {
        return $code === null ? null : (self::SEVERITY_LABELS[$code] ?? 'Unknown');
    }

    // No LED byte arrives from TTN — derive it from the same thresholds the
    // firmware uses to pick the status LED (see agrisentry-collar.ino).
    private function ledLabel(float $temperature): string
    {
        if ($temperature > 38.5) {
            return self::LED_LABELS[1]; // Red
        }

        if ($temperature < 33.0) {
            return self::LED_LABELS[2]; // Blue
        }

        return self::LED_LABELS[0]; // Green
    }
}
