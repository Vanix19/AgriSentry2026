<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Goat;
use App\Models\MedicalRecord;
use App\Models\MedicalRecordFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $medicalRecord = $this->createWithAttachment($request, $validated);

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

        $validated['goat_id'] = $goat->id;

        $medicalRecord = $this->createWithAttachment($request, $validated);

        return response()->json([
            'message' => 'Medical record saved successfully.',
            'medical_record' => $medicalRecord
        ], 201);
    }

    /** Saves the record and its optional attachment together; files are kept in the database. */
    private function createWithAttachment(Request $request, array $validated): MedicalRecord
    {
        unset($validated['reference_photo']);
        $upload = $request->file('reference_photo');
        if ($upload) $validated['reference_photo_path'] = 'medical-references/'.$upload->hashName();

        return DB::transaction(function () use ($validated, $upload) {
            $medicalRecord = MedicalRecord::create($validated);
            if ($upload) {
                MedicalRecordFile::create([
                    'medical_record_id' => $medicalRecord->id,
                    'mime_type' => $upload->getMimeType(),
                    'original_name' => mb_substr($upload->getClientOriginalName(), 0, 255),
                    'size' => $upload->getSize(),
                    'content' => file_get_contents($upload->getRealPath()),
                ]);
            }
            return $medicalRecord;
        });
    }
}
