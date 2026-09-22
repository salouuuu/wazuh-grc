<?php

namespace App\Services;

use App\Models\Incident;
use Illuminate\Support\Facades\Http;

class WazuhAlertService
{
    private function client()
    {
        return Http::withoutVerifying()
            ->withBasicAuth(
                env('WAZUH_INDEXER_USERNAME'),
                env('WAZUH_INDEXER_PASSWORD')
            );
    }

    public function getAlerts(int $limit = 10): array
    {
        $response = $this->client()->post(
            env('WAZUH_INDEXER_URL') . '/wazuh-alerts-4.x-*/_search',
            [
                'size' => $limit,
                'sort' => [
                    ['@timestamp' => ['order' => 'desc']]
                ],
                'query' => [
                    'bool' => [
                        'filter' => [
                            [
                                'range' => [
                                    'rule.level' => [
                                        'gte' => 8
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        );

        if ($response->failed()) {
            throw new \Exception(
                'Erreur Wazuh Indexer : ' . $response->body()
            );
        }

        return $response->json();
    }

    public function importAlerts(int $limit = 10): int
    {
        $result = $this->getAlerts($limit);

        $imported = 0;

        foreach ($result['hits']['hits'] as $hit) {

            $alert = $hit['_source'];

            $wazuhAlertId = $hit['_id'];

            $rule = $alert['rule'] ?? [];
            $agent = $alert['agent'] ?? [];
            $data = $alert['data'] ?? [];
            $grc = $this->calculateGrcData($alert);
            if (Incident::where('wazuh_alert_id', $wazuhAlertId)->exists()) {
                continue;
            }

            Incident::create([
                'wazuh_alert_id' => $wazuhAlertId,

                'rule_id' => $rule['id'] ?? null,

                'title' => $rule['description']
                    ?? 'Alerte Wazuh',

                'description' => $rule['description']
                    ?? 'Alerte détectée par Wazuh',

                'severity' => $rule['level']
                    ?? 0,

                'agent_id' => $agent['id']
                    ?? null,

                'agent_name' => $agent['name']
                    ?? null,

                'agent_ip' => $agent['ip']
                    ?? null,

                'source_user' => $data['srcuser']
                    ?? null,

                'command' => $data['command']
                    ?? null,

                'status' => 'new',

                'detected_at' => $alert['@timestamp']
                    ?? now(),
               'probability' => $grc['probability'],
               'impact' => $grc['impact'],
               'risk_level' => $grc['risk_level'],
               'compliance' => $grc['compliance'],
            ]);

            $imported++;
        }

        return $imported;
    }
private function calculateGrcData(array $alert): array
{
    $rule = $alert['rule'] ?? [];

    $severity = (int) ($rule['level'] ?? 0);
    $ruleId = (string) ($rule['id'] ?? '');
    $groups = $rule['groups'] ?? [];

    // Probabilité basée sur la sévérité
    if ($severity >= 12) {
        $probability = 'Très élevée';
    } elseif ($severity >= 9) {
        $probability = 'Élevée';
    } elseif ($severity >= 7) {
        $probability = 'Moyenne';
    } else {
        $probability = 'Faible';
    }

    // Impact initial
    if ($severity >= 12) {
        $impact = 'Critique';
    } elseif ($severity >= 9) {
        $impact = 'Élevé';
    } elseif ($severity >= 7) {
        $impact = 'Moyen';
    } else {
        $impact = 'Faible';
    }

    // Niveau de risque
    if ($probability === 'Très élevée' && $impact === 'Critique') {
        $riskLevel = 'Critique';
    } elseif (
        in_array($probability, ['Très élevée', 'Élevée']) &&
        in_array($impact, ['Critique', 'Élevé'])
    ) {
        $riskLevel = 'Élevé';
    } elseif (
        $probability === 'Moyenne' &&
        in_array($impact, ['Moyen', 'Élevé'])
    ) {
        $riskLevel = 'Moyen';
    } else {
        $riskLevel = 'Faible';
    }

    // Première classification conformité
// Première classification conformité
$compliance = [];

if (
    in_array('authentication_failed', $groups) ||
    in_array('authentication_success', $groups)
) {
    $compliance[] = 'ISO 27001 - A.5.16 : Gestion des identités';
}

if (in_array('file_permissions', $groups)) {
    $compliance[] = 'ISO 27001 - A.8.9 : Gestion de la configuration';
}

if (in_array('sudo', $groups)) {
    $compliance[] = "ISO 27001 - A.8.2 : Droits d'accès privilégiés";
}

if (empty($compliance)) {
    $compliance[] = 'À évaluer';
}

return [
    'probability' => $probability,
    'impact' => $impact,
    'risk_level' => $riskLevel,
    'compliance' => implode(', ', array_unique($compliance)),
];

}
}
