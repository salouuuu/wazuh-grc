<?php

namespace App\Services;

use App\Models\Incident;
use App\Models\IncidentOccurrence;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class WazuhAlertService
{
    /** Nombre d'alertes demandées par page à l'Indexer. */
    private const PAGE_SIZE = 500;

    /** Recouvrement pour ne pas rater les alertes indexées en retard (doublons ignorés). */
    private const OVERLAP_MINUTES = 10;

    /** Bilan du dernier import : alertes regroupées. */
    private array $stats = ['grouped' => 0];

    public function __construct(private RiskCalculator $risk, private IncidentTriage $triage)
    {
    }

    public function lastImportStats(): array
    {
        return $this->stats;
    }

    private function client(): PendingRequest
    {
        $config = config('services.wazuh');

        if (empty($config['url'])) {
            throw new RuntimeException('WAZUH_INDEXER_URL n’est pas configuré.');
        }

        return Http::baseUrl($config['url'])
            ->withBasicAuth($config['username'], $config['password'])
            ->withOptions(['verify' => $config['verify']])
            ->acceptJson()
            ->timeout(30);
    }

    /**
     * Récupère une page d'alertes, de la plus ancienne à la plus récente.
     */
    public function getAlerts(int $minLevel, ?Carbon $since = null, int $size = self::PAGE_SIZE, int $from = 0): array
    {
        $filters = [
            ['range' => ['rule.level' => ['gte' => $minLevel]]],
        ];

        if ($since) {
            $filters[] = ['range' => ['@timestamp' => ['gte' => $since->toIso8601ZuluString()]]];
        }

        $response = $this->client()->post('/'.config('services.wazuh.index').'/_search', [
            'from' => $from,
            'size' => $size,
            'sort' => [['@timestamp' => ['order' => 'asc']]],
            'query' => ['bool' => ['filter' => $filters]],
        ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'Erreur Wazuh Indexer (HTTP '.$response->status().') : '.Str::limit($response->body(), 300)
            );
        }

        return $response->json('hits.hits', []);
    }

    /**
     * Importe les nouvelles alertes depuis la dernière synchronisation
     * (ou tout l'historique avec $full = true), sans doublon.
     */
    public function importAlerts(int $limit = 1000, ?int $minLevel = null, bool $full = false): int
    {
        $minLevel ??= (int) config('services.wazuh.min_level');
        $since = $full ? null : $this->lastSyncDate();

        $imported = 0;
        $from = 0;
        $this->stats = ['grouped' => 0];

        while ($imported < $limit) {
            $hits = $this->getAlerts($minLevel, $since, self::PAGE_SIZE, $from);

            if (empty($hits)) {
                break;
            }

            $ids = array_column($hits, '_id');
            $existing = Incident::whereIn('wazuh_alert_id', $ids)
                ->pluck('wazuh_alert_id')
                ->merge(IncidentOccurrence::whereIn('wazuh_alert_id', $ids)->pluck('wazuh_alert_id'))
                ->flip();

            foreach ($hits as $hit) {
                if ($imported >= $limit) {
                    break;
                }

                if (isset($existing[$hit['_id']])) {
                    continue;
                }

                $attributes = $this->mapAlert($hit['_id'], $hit['_source']);

                if ($this->triage->ingest($hit['_id'], $hit['_source'], $attributes)) {
                    $this->stats['grouped']++;
                }

                $imported++;
            }

            if (count($hits) < self::PAGE_SIZE) {
                break;
            }

            $from += self::PAGE_SIZE;
        }

        $this->syncScaHistory();

        return $imported;
    }

    /**
     * Relève, pour chaque incident SCA, les changements de résultat de son contrôle CIS
     * (y compris les "passed" de niveau 3 qui ne créent pas d'incident).
     * Ne modifie que l'historique : l'évaluation reste celle de l'analyste.
     *
     * @return int nombre d'incidents dont l'historique a changé
     */
    public function syncScaHistory(): int
    {
        $incidents = Incident::primary()
            ->where('category', Incident::CATEGORY_COMPLIANCE)
            ->whereNotNull('wazuh_data')
            ->get()
            ->filter(fn (Incident $incident) => data_get($incident->wazuh_data, 'data.sca.check.id') !== null);

        if ($incidents->isEmpty()) {
            return 0;
        }

        $checkIds = $incidents
            ->map(fn (Incident $incident) => (string) data_get($incident->wazuh_data, 'data.sca.check.id'))
            ->unique()
            ->values()
            ->all();

        // Événements par machine et par contrôle, du plus ancien au plus récent
        $events = [];
        $from = 0;

        do {
            $response = $this->client()->post('/'.config('services.wazuh.index').'/_search', [
                'from' => $from,
                'size' => self::PAGE_SIZE,
                'sort' => [['@timestamp' => ['order' => 'asc']]],
                '_source' => ['@timestamp', 'agent.name', 'data.sca.check.id', 'data.sca.check.result', 'data.sca.check.previous_result'],
                'query' => ['bool' => ['filter' => [
                    ['terms' => ['data.sca.check.id' => $checkIds]],
                    ['exists' => ['field' => 'data.sca.check.result']],
                ]]],
            ]);

            if ($response->failed()) {
                throw new RuntimeException(
                    'Erreur Wazuh Indexer (HTTP '.$response->status().') : '.Str::limit($response->body(), 300)
                );
            }

            $hits = $response->json('hits.hits', []);

            foreach ($hits as $hit) {
                $check = data_get($hit['_source'], 'data.sca.check');
                $key = data_get($hit['_source'], 'agent.name').'#'.($check['id'] ?? '');

                $events[$key][$hit['_id']] = [
                    'at' => data_get($hit['_source'], '@timestamp'),
                    'result' => $check['result'] ?? null,
                    'previous' => $check['previous_result'] ?? null,
                ];
            }

            $from += self::PAGE_SIZE;
        } while (count($hits) === self::PAGE_SIZE);

        $updated = 0;

        foreach ($incidents as $incident) {
            $key = $incident->agent_name.'#'.data_get($incident->wazuh_data, 'data.sca.check.id');
            $history = array_values($events[$key] ?? []);

            if ($history && $history !== $incident->sca_history) {
                // Sans toucher à updated_at : ce n'est pas une modification de l'analyste
                $incident->timestamps = false;
                $incident->forceFill(['sca_history' => $history])->saveQuietly();
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * Transforme une alerte Wazuh en attributs d'incident GRC.
     */
    public function mapAlert(string $wazuhAlertId, array $alert): array
    {
        $rule = $alert['rule'] ?? [];
        $agent = $alert['agent'] ?? [];
        $data = $alert['data'] ?? [];

        $level = (int) ($rule['level'] ?? 0);
        $description = $rule['description'] ?? 'Alerte détectée par Wazuh';
        $detectedAt = isset($alert['@timestamp']) ? Carbon::parse($alert['@timestamp']) : now();

        return [
            'wazuh_alert_id' => $wazuhAlertId,
            'rule_id' => (string) ($rule['id'] ?? ''),
            'title' => Str::limit($description, 250),
            'description' => $description,
            'severity' => $level,
            'agent_id' => $agent['id'] ?? null,
            'agent_name' => $agent['name'] ?? null,
            'agent_ip' => filter_var($agent['ip'] ?? null, FILTER_VALIDATE_IP) ?: null,
            'source_user' => $data['srcuser'] ?? null,
            'command' => $data['command'] ?? null,
            'status' => 'new',
            'detected_at' => $detectedAt,
            'last_seen_at' => $detectedAt,
            'compliance' => $this->risk->complianceFromGroups($rule['groups'] ?? []),
            'fingerprint' => $this->triage->fingerprint($alert),
            'category' => $this->triage->category($alert),
            'wazuh_data' => $alert,
            ...$this->risk->fromWazuhLevel($level),
        ];
    }

    /**
     * Relit des alertes déjà importées dans l'Indexer (par identifiant).
     *
     * @return array<string, array> _id => _source
     */
    public function getAlertsByIds(array $ids): array
    {
        $response = $this->client()->post('/'.config('services.wazuh.index').'/_search', [
            'size' => count($ids),
            'query' => ['ids' => ['values' => array_values($ids)]],
        ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'Erreur Wazuh Indexer (HTTP '.$response->status().') : '.Str::limit($response->body(), 300)
            );
        }

        return collect($response->json('hits.hits', []))
            ->mapWithKeys(fn ($hit) => [$hit['_id'] => $hit['_source']])
            ->all();
    }

    private function lastSyncDate(): ?Carbon
    {
        // Les alertes regroupées ne créent pas d'incident : on tient compte des occurrences.
        $last = collect([Incident::max('detected_at'), IncidentOccurrence::max('detected_at')])->filter()->max();

        return $last ? Carbon::parse($last)->subMinutes(self::OVERLAP_MINUTES) : null;
    }
}
