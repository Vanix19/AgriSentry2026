<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Goat;
use App\Models\MedicalRecord;
use Illuminate\Http\Request;

class MedicalRecordController extends Controller
{
    public function index()
    {
        return response()->json([
            'medical_records' => MedicalRecord::with('goat')->orderBy('created_at', 'desc')->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'goat_id' => 'required|exists:goats,id',
            'record_type' => 'required|string|max:50',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date_given' => 'nullable|date',
            'next_due_date' => 'nullable|date',
            'administered_by' => 'nullable|string|max:255',
            'reference_photo' => 'nullable|file|mimes:jpeg,jpg,png,webp,pdf|max:20480',
        ]);

        if ($request->hasFile('reference_photo')) {
            $validated['reference_photo_path'] = $request->file('reference_photo')->store('medical-references', 'public');
        }
        unset($validated['reference_photo']);

        $medicalRecord = MedicalRecord::create($validated);

        return response()->json([
            'message' => 'Medical record saved successfully.',
            'medical_record' => $medicalRecord
        ], 201);
    }

    public function storeForGoat(Request $request, $id)
    {
        $goat = Goat::findOrFail($id);

        $validated = $request->validate([
            'record_type' => 'required|string|max:50',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date_given' => 'nullable|date',
            'next_due_date' => 'nullable|date',
            'administered_by' => 'nullable|string|max:255',
            'reference_photo' => 'nullable|file|mimes:jpeg,jpg,png,webp,pdf|max:20480',
        ]);

        if ($request->hasFile('reference_photo')) {
            $validated['reference_photo_path'] = $request->file('reference_photo')->store('medical-references', 'public');
        }
        unset($validated['reference_photo']);

        $validated['goat_id'] = $goat->id;

        $medicalRecord = MedicalRecord::create($validated);

        return response()->json([
            'message' => 'Medical record saved successfully.',
            'medical_record' => $medicalRecord
        ], 201);
    }
}
