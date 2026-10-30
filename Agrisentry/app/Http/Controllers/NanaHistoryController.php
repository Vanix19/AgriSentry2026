<?php

namespace App\Http\Controllers;

use App\Models\Goat;
use App\Services\AccountAccess;
use App\Services\NanaHistoryPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class NanaHistoryController extends Controller
{
    private function goat(bool $lock = false): Goat
    {
        $query = Goat::whereKey(4);
        if ($lock) $query->lockForUpdate();
        $goat = $query->first();
        abort_unless($goat && strcasecmp(trim($goat->name), 'NANA') === 0, 404, 'Goat #4 must be named NANA.');
        return $goat;
    }

    private function plan(NanaHistoryPlan $planner): array
    {
        // UTC boundaries of the four local calendar days, including after the cutoff.
        $records = DB::table('health_logs')->where('goat_id', 4)
            ->where('created_at', '>=', '2026-10-27 16:00:00')
            ->where('created_at', '<', '2026-10-31 16:00:00')
            ->orderBy('id')->get(['id', 'created_at'])->map(fn ($row) => (array) $row)->all();
        return $planner->build($records);
    }

    public function show(Request $request, NanaHistoryPlan $planner)
    {
        $goat = $this->goat();
        $plan = $this->plan($planner);
        $request->session()->put('nana_history_preview', $plan['fingerprint']);
        return response()->view('admin-nana-history', compact('goat', 'plan'))->header('Cache-Control', 'no-store');
    }

    public function store(Request $request, NanaHistoryPlan $planner)
    {
        $request->validate(['acknowledge' => 'accepted']);
        $preview = $request->session()->pull('nana_history_preview');
        $count = DB::transaction(function () use ($request, $planner, $preview) {
            $this->goat(true);
            $plan = $this->plan($planner);
            if (!is_string($preview) || !hash_equals($plan['fingerprint'], $preview)) {
                throw ValidationException::withMessages(['history' => 'The history changed or the preview expired. Review the refreshed preview and try again.']);
            }
            foreach (array_chunk($plan['rows'], 50) as $chunk) DB::table('health_logs')->insert($chunk);
            if (count($plan['rows']) > 0) AccountAccess::record($request->user(), 'Imported NANA simulated history: '.count($plan['rows']).' readings', $request);
            return count($plan['rows']);
        });
        return redirect('/admin/nana-history')->with('status', $count.' simulated readings added. Open NANA’s profile to view the history.');
    }
}
