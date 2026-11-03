<?php

namespace App\Services;

use App\Models\Incident;
use App\Models\IncidentOccurrence;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Tri des alertes Wazuh : regroupement des répétitions en un seul incident.
 */
class IncidentTriage
{
    /**
     * Empreinte d'une alerte : même règle, même agent et même "objet"
     * (contrôle SCA, ou IP / utilisateurs concernés) = même incident.
     */
    public function fingerprint(array $alert): string
    {
        $subject = Arr::get($alert, 'data.sca.check.id')
            ?? implode('|', [
                Arr::get($alert, 'data.srcip'),
                Arr::get($alert, 'data.srcuser'),
                Arr::get($alert, 'data.dstuser'),
            ]);

        return sha1(implode('#', [
            Arr::get($alert, 'rule.id'),
            Arr::get($alert, 'agent.name'),
            $subject,
        ]));
    }

    /** Empreinte de secours pour un incident dont l'alerte d'origine n'est plus disponible. */
    public function fingerprintFromIncident(Incident $incident): string
    {
        return sha1(implode('#', [
            $incident->rule_id,
            $incident->agent_name,
            $incident->title.'|'.$incident->source_user,
        ]));
    }

    public function category(array $alert): string
    {
        return in_array('sca', Arr::get($alert, 'rule.groups', []), true)
            ? Incident::CATEGORY_COMPLIANCE
            : Incident::CATEGORY_SECURITY;
    }

    /**
     * Un contrôle CIS (SCA) en échec est une non-conformité constatée :
     * pré-remplit l'évaluation de conformité (modifiable par l'analyste).
     */
    public function scaCompliance(array $alert): array
    {
        $check = Arr::get($alert, 'data.sca.check');

        $status = match ($check['result'] ?? null) {
            'failed' => 'Non conforme',
            'passed' => 'Conforme',
            default => null,
        };

        if (! $status) {
            return [];
        }

        return [
            'compliance_status' => $status,
            'compliance_comment' => sprintf(
                'Évaluation automatique : contrôle CIS %s « %s » %s (%s).',
                Arr::get($alert, 'rule.cis.0', Arr::get($check, 'compliance.cis', '?')),
                $check['title'] ?? '?',
                $status === 'Conforme' ? 'validé' : 'en échec',
                Arr::get($alert, 'data.sca.policy', 'SCA Wazuh'),
            ),
        ];
    }

    /**
     * Enregistre une alerte : rattachée à l'incident ouvert de même empreinte,
     * sinon nouvel incident.
     *
     * @return bool true si l'alerte a été regroupée dans un incident existant
     */
    public function ingest(string $wazuhAlertId, array $alert, array $attributes): bool
    {
        return DB::transaction(function () use ($wazuhAlertId, $alert, $attributes) {
            $detectedAt = $attributes['detected_at'] ?? now();

            $master = Incident::primary()
                ->where('fingerprint', $attributes['fingerprint'])
                ->whereIn('status', Incident::GROUPABLE_STATUSES)
                ->latest('detected_at')
                ->first();

            if ($master) {
                $this->attach($master, $wazuhAlertId, $detectedAt);

                return true;
            }

            $incident = Incident::create([...$attributes, ...$this->scaCompliance($alert)]);

            IncidentOccurrence::create([
                'incident_id' => $incident->id,
                'wazuh_alert_id' => $wazuhAlertId,
                'detected_at' => $detectedAt,
            ]);

            return false;
        });
    }

    /** Rattache une alerte (nouvelle ou ancien doublon) à un incident maître. */
    public function attach(Incident $master, string $wazuhAlertId, Carbon $detectedAt): void
    {
        IncidentOccurrence::firstOrCreate(
            ['wazuh_alert_id' => $wazuhAlertId],
            ['incident_id' => $master->id, 'detected_at' => $detectedAt],
        );

        $master->occurrences = $master->occurrenceLog()->count();

        if (! $master->last_seen_at || $detectedAt->gt($master->last_seen_at)) {
            $master->last_seen_at = $detectedAt;
        }

        $master->save();
    }
}
