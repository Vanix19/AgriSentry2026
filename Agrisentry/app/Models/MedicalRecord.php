<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalRecord extends Model
{
    protected $fillable = [
        'goat_id',
        'record_type',
        'title',
        'description',
        'date_given',
        'next_due_date',
        'administered_by',
        'reference_photo_path',
    ];

    protected $appends = ['reference_photo_url'];

    public function getReferencePhotoUrlAttribute(): ?string
    {
        if (! $this->reference_photo_path) {
            return null;
        }

        // Keep uploaded-file links on the same host and port as the page. An
        // absolute APP_URL breaks when the app is opened through Artisan's
        // :8081 server or an ngrok URL.
        return '/storage/'.ltrim($this->reference_photo_path, '/');
    }

    public function goat()
    {
        return $this->belongsTo(Goat::class);
    }
}
