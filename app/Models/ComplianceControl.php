<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComplianceControl extends Model
{
    protected $fillable = [
        'framework',
        'control_code',
        'title',
        'description',
    ];
}
