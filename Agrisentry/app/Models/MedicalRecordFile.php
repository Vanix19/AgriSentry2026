<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalRecordFile extends Model
{
    protected $fillable = ['medical_record_id', 'mime_type', 'original_name', 'size', 'content'];

    protected $hidden = ['content'];
}
