<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\WazuhAlertService;

class ImportWazuhAlerts extends Command
{
    protected $signature = 'wazuh:import
        {--limit=1000 : Nombre maximum d’alertes importées par exécution}
        {--min-level= : Niveau minimum de la règle Wazuh (défaut : WAZUH_MIN_LEVEL)}
        {--all : Parcourt tout l’historique au lieu de reprendre depuis le dernier import}';

    protected $description = 'Importe les alertes Wazuh dans les incidents GRC';

    public function handle(WazuhAlertService $wazuh)
    {
        $limit = (int) $this->option('limit');
        $minLevel = $this->option('min-level') !== null ? (int) $this->option('min-level') : null;

        try {
            $imported = $wazuh->importAlerts($limit, $minLevel, (bool) $this->option('all'));

            $this->info(
                "$imported nouvelle(s) alerte(s) Wazuh importée(s)."
            );

            $stats = $wazuh->lastImportStats();

            if ($stats['grouped']) {
                $this->line(
                    "dont {$stats['grouped']} regroupée(s) dans un incident existant."
                );
            }

            return Command::SUCCESS;

        } catch (\Throwable $e) {

            $this->error(
                'Erreur lors de l’import Wazuh : ' . $e->getMessage()
            );

            return Command::FAILURE;
        }
    }
}
