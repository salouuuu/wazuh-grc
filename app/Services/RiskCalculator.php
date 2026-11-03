<?php

namespace App\Services;

/**
 * Source unique de vérité pour l'évaluation du risque :
 * utilisée à l'import Wazuh, à la mise à jour d'un incident
 * et pour l'affichage de la matrice de risque.
 *
 * Le niveau de risque est lu dans la matrice définie pour le projet (MATRIX),
 * identique à celle affichée à l'origine dans la fiche incident.
 */
class RiskCalculator
{
    public const PROBABILITIES = [
        'Faible' => 1,
        'Moyenne' => 2,
        'Élevée' => 3,
        'Très élevée' => 4,
    ];

    public const IMPACTS = [
        'Faible' => 1,
        'Moyen' => 2,
        'Élevé' => 3,
        'Critique' => 4,
    ];

    public const RISK_LEVELS = ['Faible', 'Moyen', 'Élevé', 'Critique'];

    /**
     * Matrice de risque du projet : [probabilité][impact] => niveau.
     */
    public const MATRIX = [
        'Très élevée' => ['Faible' => 'Moyen', 'Moyen' => 'Élevé', 'Élevé' => 'Critique', 'Critique' => 'Critique'],
        'Élevée' => ['Faible' => 'Moyen', 'Moyen' => 'Élevé', 'Élevé' => 'Élevé', 'Critique' => 'Critique'],
        'Moyenne' => ['Faible' => 'Faible', 'Moyen' => 'Moyen', 'Élevé' => 'Élevé', 'Critique' => 'Élevé'],
        'Faible' => ['Faible' => 'Faible', 'Moyen' => 'Faible', 'Élevé' => 'Moyen', 'Critique' => 'Moyen'],
    ];

    /**
     * Correspondance groupes de règles Wazuh → contrôles ISO 27001:2022.
     */
    public const COMPLIANCE_MAPPING = [
        'authentication_failed' => 'ISO 27001 - A.5.16 : Gestion des identités',
        'authentication_failures' => 'ISO 27001 - A.5.16 : Gestion des identités',
        'authentication_success' => 'ISO 27001 - A.5.16 : Gestion des identités',
        'invalid_login' => 'ISO 27001 - A.5.15 : Contrôle d’accès',
        'sudo' => "ISO 27001 - A.8.2 : Droits d'accès privilégiés",
        'file_permissions' => 'ISO 27001 - A.8.9 : Gestion de la configuration',
        'syscheck' => 'ISO 27001 - A.8.9 : Gestion de la configuration',
        'sca' => 'ISO 27001 - A.8.9 : Gestion de la configuration',
        'config_changed' => 'ISO 27001 - A.8.9 : Gestion de la configuration',
        'rootcheck' => 'ISO 27001 - A.8.7 : Protection contre les logiciels malveillants',
        'virus' => 'ISO 27001 - A.8.7 : Protection contre les logiciels malveillants',
        'vulnerability-detector' => 'ISO 27001 - A.8.8 : Gestion des vulnérabilités techniques',
        'attack' => 'ISO 27001 - A.8.16 : Activités de surveillance',
        'web' => 'ISO 27001 - A.8.16 : Activités de surveillance',
    ];

    public function score(string $probability, string $impact): int
    {
        return (self::PROBABILITIES[$probability] ?? 1) * (self::IMPACTS[$impact] ?? 1);
    }

    public function riskLevel(string $probability, string $impact): string
    {
        return self::MATRIX[$probability][$impact] ?? 'Faible';
    }

    /**
     * Évaluation initiale à partir du niveau de la règle Wazuh (0-15).
     */
    public function fromWazuhLevel(int $level): array
    {
        [$probability, $impact] = match (true) {
            $level >= 12 => ['Très élevée', 'Critique'],
            $level >= 9 => ['Élevée', 'Élevé'],
            $level >= 7 => ['Moyenne', 'Moyen'],
            default => ['Faible', 'Faible'],
        };

        return [
            'probability' => $probability,
            'impact' => $impact,
            'risk_level' => $this->riskLevel($probability, $impact),
        ];
    }

    /**
     * Suggestion de conformité à partir des groupes de la règle Wazuh.
     */
    public function complianceFromGroups(array $groups): string
    {
        $compliance = [];

        foreach ($groups as $group) {
            if (isset(self::COMPLIANCE_MAPPING[$group])) {
                $compliance[] = self::COMPLIANCE_MAPPING[$group];
            }
        }

        return $compliance ? implode(', ', array_unique($compliance)) : 'À évaluer';
    }

    /**
     * Matrice complète [probabilité][impact] => niveau, pour l'affichage.
     */
    public function matrix(): array
    {
        $matrix = [];

        foreach (array_reverse(array_keys(self::PROBABILITIES)) as $probability) {
            foreach (array_keys(self::IMPACTS) as $impact) {
                $matrix[$probability][$impact] = $this->riskLevel($probability, $impact);
            }
        }

        return $matrix;
    }
}
