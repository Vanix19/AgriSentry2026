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

        // Relative so links stay on the host the page was opened from. The file
        // name keeps its extension so pages can tell PDFs from images.
        return '/medical-files/'.$this->id.'/'.basename($this->reference_photo_path);
    }

    public function file()
    {
        return $this->hasOne(MedicalRecordFile::class);
    }

    public function goat()
    {
        return $this->belongsTo(Goat::class);
    }
}
