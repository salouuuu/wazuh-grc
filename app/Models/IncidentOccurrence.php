<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncidentOccurrence extends Model
{
    protected $fillable = [
        'incident_id',
        'wazuh_alert_id',
        'detected_at',
    ];

    protected $casts = [
        'detected_at' => 'datetime',
    ];

    public function incident()
    {
        return $this->belongsTo(Incident::class);
    }
}
