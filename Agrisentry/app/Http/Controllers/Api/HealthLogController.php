<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HealthLog;
use Illuminate\Http\Request;

class HealthLogController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'motion_anomaly' => 'sometimes|in:all,any,none,Prolonged Inactivity,Excessive Movement',
            'after_id' => 'sometimes|integer|min:0',
        ]);
        $motion = $data['motion_anomaly'] ?? 'all';
        $logs = HealthLog::with('goat')->when(isset($data['after_id']), fn ($query) => $query->where('id', '>', $data['after_id']))->latest()->orderByDesc('id')->get()->filter(fn ($log) => match ($motion) {
            'all' => true, 'any' => $log->motion_anomaly !== null, 'none' => $log->motion_anomaly === null,
            default => $log->motion_anomaly === $motion,
        })->values();
        return response()->json([
            'health_logs' => $logs
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'goat_id' => 'required|exists:goats,id',
            'event_type' => 'required|string|max:255',
            'description' => 'nullable|string',
            'temperature' => 'nullable|numeric',
            'movement' => 'nullable|string|max:255',
            'led_status' => 'nullable|string|max:100',
            'severity' => 'nullable|string|max:100',
        ]);

        $healthLog = HealthLog::create($validated);

        return response()->json([
            'message' => 'Health log saved successfully.',
            'health_log' => $healthLog
        ], 201);
    }
}
