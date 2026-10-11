<?php

namespace App\Http\Controllers;

use App\Models\MedicalRecord;
use App\Models\MedicalRecordFile;
use App\Services\AccountAccess;
use Illuminate\Support\Facades\Storage;

class MedicalFileController extends Controller
{
    /** Serves a medical record attachment to signed-in users who may read medical records. */
    public function show(int $id)
    {
        $user = auth()->user();
        abort_unless($user->isAdmin() || (in_array($user->role, ['Staff', 'Caretaker'], true)
            && (AccountAccess::permissions($user->role)['medical-records.read'] ?? false)), 403);

        $record = MedicalRecord::findOrFail($id);
        abort_unless($record->reference_photo_path, 404);
        $headers = ['Cache-Control' => 'private, max-age=3600', 'X-Content-Type-Options' => 'nosniff'];

        $file = MedicalRecordFile::where('medical_record_id', $record->id)->first();
        if ($file) {
            $name = str_replace('"', '', $file->original_name ?: basename($record->reference_photo_path));
            return response($file->content, 200, $headers + [
                'Content-Type' => $file->mime_type,
                'Content-Disposition' => 'inline; filename="'.$name.'"',
            ]);
        }

        // Attachments uploaded before files moved into the database.
        $disk = Storage::disk('public');
        abort_unless($disk->exists($record->reference_photo_path), 404);
        return response()->file($disk->path($record->reference_photo_path), $headers);
    }
}
