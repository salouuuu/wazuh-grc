<?php

namespace Database\Seeders;

use App\Models\ComplianceControl;
use Illuminate\Database\Seeder;

class IsoWazuhControlSeeder extends Seeder
{
    /**
     * Contrôles ISO 27001:2022 correspondant aux références ISO 27001:2013
     * relevées dans les alertes Wazuh (voir config/iso27001.php).
     * Ajout seulement : un contrôle déjà présent n'est pas modifié.
     */
    public function run(): void
    {
        $codes = [
            'A.5.10',  // ← A.8.1.3
            'A.5.17',  // ← A.9.4.3
            'A.5.18',  // ← A.9.2.6
            'A.7.10',  // ← A.8.3.1
            'A.8.5',   // ← A.9.4.2
            'A.8.15',  // ← A.12.4.1, A.12.4.3
            'A.8.17',  // ← A.12.4.4
            'A.8.19',  // ← A.12.5.1, A.12.6.2
            'A.8.20',  // ← A.13.1.1
            'A.8.22',  // ← A.13.1.3
            'A.8.24',  // ← A.10.1.1
            'A.8.27',  // ← A.14.2.5
            'A.8.32',  // ← A.12.1.2
        ];

        foreach ($codes as $code) {
            ComplianceControl::firstOrCreate(
                ['framework' => 'ISO 27001', 'control_code' => $code],
                ['title' => config('iso27001.controls')[$code]]
            );
        }
    }
}
