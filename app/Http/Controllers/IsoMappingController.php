<?php

namespace App\Http\Controllers;

use App\Models\ComplianceControl;
use App\Models\Incident;

class IsoMappingController extends Controller
{
    /**
     * Table de correspondance ISO 27001:2013 → 2022 (lecture seule).
     */
    public function index()
    {
        $inApp = ComplianceControl::where('framework', 'ISO 27001')->pluck('control_code')->all();

        // Références ISO 2013 réellement envoyées par Wazuh pour les incidents importés
        $seenInWazuh = Incident::whereNotNull('wazuh_data')->get(['wazuh_data'])
            ->flatMap(fn ($incident) => (array) data_get($incident->wazuh_data, 'rule.iso_27001-2013', []))
            ->countBy()
            ->all();

        return view('iso.mapping', [
            'mapping' => config('iso27001.mapping'),
            'controls' => config('iso27001.controls'),
            'inApp' => $inApp,
            'seenInWazuh' => $seenInWazuh,
        ]);
    }
}
