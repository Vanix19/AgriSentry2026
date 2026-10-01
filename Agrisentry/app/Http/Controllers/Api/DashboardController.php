<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Goat;
use App\Models\HealthLog;
use App\Models\Alert;

class DashboardController extends Controller
{
    public function index()
    {
        $counts = Goat::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        return response()->json([
            'total_goats' => (int) $counts->sum(),
            'normal_goats' => (int) ($counts['Normal'] ?? 0),
            'monitoring_goats' => (int) ($counts['Warning'] ?? 0) + (int) ($counts['Monitoring'] ?? 0),
            'urgent_goats' => (int) ($counts['Urgent'] ?? 0),
            'active_alerts' => Alert::where('status', 'Active')->count(),
            'latest_logs' => HealthLog::latest()->take(5)->get(),
        ]);
    }
}
