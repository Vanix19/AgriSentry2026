<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function index()
    {
        return response()->json([
            'alerts' => Alert::with('goat')->where('status', 'Active')->latest()->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'goat_id' => 'required|exists:goats,id',
            'alert_type' => 'required|string|max:255',
            'message' => 'required|string',
            'recommendation' => 'nullable|string',
            'severity' => 'nullable|string|max:100',
            'status' => 'nullable|string|max:100',
        ]);

        if (!isset($validated['status'])) {
            $validated['status'] = 'Active';
        }

        $alert = Alert::create($validated);

        return response()->json([
            'message' => 'Alert saved successfully.',
            'alert' => $alert
        ], 201);
    }
}
