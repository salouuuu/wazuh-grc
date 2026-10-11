<?php

namespace App\Models;
use App\Models\User;

use Illuminate\Database\Eloquent\Model;
use App\Models\ComplianceControl;

class Incident extends Model
{
    protected $fillable = [
        'wazuh_alert_id',
        'rule_id',
        'title',
        'description',
        'severity',
        'agent_id',
        'agent_name',
        'agent_ip',
        'source_user',
        'command',
        'status',
        'detected_at',
        'probability',
        'impact',
        'risk_level',
        'compliance',
        'comment',
        'action_corrective',
        'responsable',
        'due_date',
        'compliance_status',
        'compliance_control',
        'compliance_comment',
'responsable_user_id',
    ];

    protected $casts = [
        'severity' => 'integer',
        'detected_at' => 'datetime',
        'due_date' => 'date',
    ];
public function complianceControl()
{
    return $this->belongsTo(ComplianceControl::class, 'compliance_control');
}

public function responsable()
{
    return $this->belongsTo(User::class, 'responsable_user_id');
}
}
