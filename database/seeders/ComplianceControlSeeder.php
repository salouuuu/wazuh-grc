<?php

namespace Database\Seeders;

use App\Models\ComplianceControl;
use Illuminate\Database\Seeder;

class ComplianceControlSeeder extends Seeder
{
    /**
     * Contrôles ISO 27001:2022 utilisés pour l'évaluation de conformité.
     * L'ordre est conservé pour garder les mêmes identifiants.
     */
    public function run(): void
    {
        $controls = [
            ['A.5.15', 'Contrôle d’accès'],
            ['A.5.16', 'Gestion des identités'],
            ['A.8.7', 'Protection contre les logiciels malveillants'],
            ['A.8.8', 'Gestion des vulnérabilités techniques'],
            ['A.8.16', 'Activités de surveillance'],
            ['A.8.2', "Droits d'accès privilégiés"],
            ['A.8.9', 'Gestion de la configuration'],
        ];

        foreach ($controls as [$code, $title]) {
            ComplianceControl::updateOrCreate(
                ['framework' => 'ISO 27001', 'control_code' => $code],
                ['title' => $title]
            );
        }
    }
}
