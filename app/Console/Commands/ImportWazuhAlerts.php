<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\WazuhAlertService;

class ImportWazuhAlerts extends Command
{
    protected $signature = 'wazuh:import {--limit=10}';

    protected $description = 'Importe les alertes Wazuh dans les incidents GRC';

    public function handle(WazuhAlertService $wazuh)
    {
        $limit = (int) $this->option('limit');

        try {
            $imported = $wazuh->importAlerts($limit);

            $this->info(
                "$imported nouvelle(s) alerte(s) Wazuh importée(s)."
            );

            return Command::SUCCESS;

        } catch (\Throwable $e) {

            $this->error(
                'Erreur lors de l’import Wazuh : ' . $e->getMessage()
            );

            return Command::FAILURE;
        }
    }
}
