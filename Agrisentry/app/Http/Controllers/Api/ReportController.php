<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HealthLog;
use App\Models\MedicalRecord;
use App\Services\HealthAlertEvaluator;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'period' => 'sometimes|in:day,week,month,year,all',
            'start_date' => 'nullable|required_with:end_date|date_format:Y-m-d',
            'end_date' => 'nullable|required_with:start_date|date_format:Y-m-d|after_or_equal:start_date',
        ]);
        $period = $data['period'] ?? 'all';
        $custom = !empty($data['start_date']);
        $start = $custom ? Carbon::parse($data['start_date'])->startOfDay() : match ($period) {
            'day' => now()->startOfDay(), 'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(), 'year' => now()->startOfYear(), default => null,
        };
        $end = $custom ? Carbon::parse($data['end_date'])->addDay()->startOfDay() : now()->addDay()->startOfDay();
        $logs = HealthLog::when($start, fn ($q) => $q->where('created_at', '>=', $start))
            ->where('created_at', '<', $end)->orderByDesc('created_at')->orderByDesc('id')->get();
        $counts = ['urgent' => 0, 'monitoring' => 0, 'normal' => 0];
        foreach ($logs->unique('goat_id') as $log) {
            $status = $log->motion_anomaly ? 'Urgent' : ($log->temperature !== null
                ? HealthAlertEvaluator::resolveStatus((float) $log->temperature)['status'] : $log->severity);
            $key = match (strtolower($status ?? '')) {
                'urgent', 'high', 'critical' => 'urgent', 'warning', 'monitoring', 'medium' => 'monitoring',
                'normal', 'info', 'low' => 'normal', default => null,
            };
            if ($key) $counts[$key]++;
        }
        $temperatures = $logs->pluck('temperature')->filter(fn ($value) => $value !== null)->map(fn ($value) => (float) $value);
        $motionLogs = $logs->filter(fn ($log) => $log->motion_anomaly || filled($log->movement) || str_contains(strtolower($log->event_type ?? ''), 'motion'));
        $inactivity = $motionLogs->where('motion_anomaly', 'Prolonged Inactivity')->count();
        $excessive = $motionLogs->where('motion_anomaly', 'Excessive Movement')->count();
        $medicalCount = MedicalRecord::where(function ($q) use ($start, $end) {
            $q->where(function ($dated) use ($start, $end) {
                $dated->whereNotNull('date_given')->when($start, fn ($q) => $q->where('date_given', '>=', $start->toDateString()))
                    ->where('date_given', '<', $end->toDateString());
            })->orWhere(function ($undated) use ($start, $end) {
                $undated->whereNull('date_given')->when($start, fn ($q) => $q->where('created_at', '>=', $start))->where('created_at', '<', $end);
            });
        })->count();
        return response()->json([
            'period' => $custom ? 'custom' : $period, 'start_date' => $start?->toDateString(),
            'end_date' => $end->copy()->subDay()->toDateString(),
            'urgent_goats' => $counts['urgent'], 'monitoring_goats' => $counts['monitoring'], 'normal_goats' => $counts['normal'],
            'medical_records_count' => $medicalCount, 'health_logs_count' => $logs->count(),
            'motion_readings_count' => $motionLogs->count(),
            'prolonged_inactivity_count' => $inactivity,
            'excessive_movement_count' => $excessive,
            'other_motion_count' => $motionLogs->count() - $inactivity - $excessive,
            'temp_avg' => $temperatures->isNotEmpty() ? round($temperatures->avg(), 1) : null,
            'temp_high' => $temperatures->isNotEmpty() ? $temperatures->max() : null,
            'temp_low' => $temperatures->isNotEmpty() ? $temperatures->min() : null,
        ]);
    }
}
