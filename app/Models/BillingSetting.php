<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingSetting extends Model
{
    protected $fillable = [
        'alert_message',
        'show_marquee_alert',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'show_marquee_alert' => 'boolean',
        ];
    }
}
