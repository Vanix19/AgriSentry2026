<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Collar extends Model
{
    protected $fillable = [
        'goat_id',
        'collar_code',
        'dev_eui',
        'battery_level',
        'device_status',
        'last_seen',
    ];

    protected $casts = [
        'last_seen' => 'datetime',
    ];

    public function goat()
    {
        return $this->belongsTo(Goat::class);
    }
}
