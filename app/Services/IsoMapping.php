<?php

namespace App\Services;

use App\Models\ComplianceControl;
use App\Models\Incident;
use Illuminate\Support\Collection;

/**
 * Traduit les références ISO 27001:2013 fournies par Wazuh
 * en contrôles ISO 27001:2022 (table config/iso27001.php).
 *
 * Ne fait que proposer : le contrôle retenu reste choisi par l'analyste.
 */
class IsoMapping
{
    /**
     * Contrôles 2022 correspondant à un contrôle 2013.
     */
    public function to2022(string $code2013): array
    {
        // Pas de config('iso27001.mapping.' . $code) : les points du code seraient lus comme des clés
        return config('iso27001.mapping')[$code2013] ?? [];
    }

    /**
     * Intitulé d'un contrôle 2022.
     */
    public function title(string $code2022): ?string
    {
        return config('iso27001.controls')[$code2022] ?? null;
    }

    /**
     * Suggestions pour un incident : [code 2022 => [sources]], par ordre de priorité :
     * 1. type d'alerte (groupes de la règle, colonne "compliance" calculée à l'import),
     * 2. références ISO 2013 de Wazuh traduites en 2022.
     */
    public function suggestions(Incident $incident): array
    {
        $suggestions = [];

        preg_match_all('/ISO 27001 - (A\.\d+\.\d+)/', (string) $incident->compliance, $fromGroups);

        foreach ($fromGroups[1] as $code2022) {
            $suggestions[$code2022][] = 'type d’alerte (groupes de la règle Wazuh)';
        }

        foreach ((array) data_get($incident->wazuh_data, 'rule.iso_27001-2013', []) as $code2013) {
            foreach ($this->to2022($code2013) as $code2022) {
                $suggestions[$code2022][] = 'ISO 27001:2013 ' . $code2013;
            }
        }

        return $suggestions;
    }

    /**
     * Premier contrôle suggéré présent dans la liste de l'application.
     */
    public function suggestedControl(Incident $incident, Collection $controls): ?ComplianceControl
    {
        foreach (array_keys($this->suggestions($incident)) as $code2022) {
            $control = $controls->first(fn ($c) => $c->framework === 'ISO 27001' && $c->control_code === $code2022);

            if ($control) {
                return $control;
            }
        }

        return null;
    }
}
