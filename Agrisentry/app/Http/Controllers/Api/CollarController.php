<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Collar;
use Illuminate\Http\Request;

class CollarController extends Controller
{
    public function index()
    {
        return response()->json([
            'collars' => Collar::with('goat')->latest()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'goat_id' => 'nullable|exists:goats,id',
            'collar_code' => 'required|string|max:100|unique:collars,collar_code',
            'dev_eui' => 'nullable|string|max:100|unique:collars,dev_eui',
            'battery_level' => 'nullable|integer|min:0|max:100',
            'device_status' => 'nullable|string|max:50',
        ]);

        $collar = Collar::create($validated);

        return response()->json([
            'message' => 'Collar added successfully.',
            'collar' => $collar->load('goat'),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $collar = Collar::findOrFail($id);

        $validated = $request->validate([
            'goat_id' => 'nullable|exists:goats,id',
            'collar_code' => 'sometimes|string|max:100|unique:collars,collar_code,'.$id,
            'dev_eui' => 'nullable|string|max:100|unique:collars,dev_eui,'.$id,
            'battery_level' => 'nullable|integer|min:0|max:100',
            'device_status' => 'nullable|string|max:50',
        ]);

        $collar->update($validated);

        return response()->json([
            'message' => 'Collar updated successfully.',
            'collar' => $collar->load('goat'),
        ]);
    }

    public function destroy($id)
    {
        Collar::findOrFail($id)->delete();

        return response()->json(['message' => 'Collar removed successfully.']);
    }
}
