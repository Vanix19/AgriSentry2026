<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaretakerPhoneNumber extends Model
{
    protected $fillable = [
        'user_id',
        'phone_number',
        'label',
        'is_primary',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
