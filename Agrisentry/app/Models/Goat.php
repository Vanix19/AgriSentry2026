<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Goat extends Model
{
    protected $attributes = ['pregnancy_status' => 'unknown'];

    protected $fillable = [
        'name',
        'code',
        'breed',
        'age',
        'sex',
        'pregnancy_status',
        'weight',
        'owner',
        'ear_tag',
        'color',
        'collar_id',
        'temperature',
        'movement',
        'battery',
        'status',
        'alert_reason',
    ];

    public function medicalRecords()
    {
        return $this->hasMany(MedicalRecord::class);
    }

    public function healthLogs()
    {
        return $this->hasMany(HealthLog::class);
    }

    public function alerts()
    {
        return $this->hasMany(Alert::class);
    }

    public function collar()
    {
        return $this->hasOne(Collar::class);
    }
}
