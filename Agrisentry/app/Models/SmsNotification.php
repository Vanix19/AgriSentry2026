<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsNotification extends Model
{
    protected $fillable = [
        'alert_id',
        'caretaker_phone_number_id',
        'phone_number',
        'message',
        'status',
        'provider_response',
    ];

    public function alert()
    {
        return $this->belongsTo(Alert::class);
    }
}
