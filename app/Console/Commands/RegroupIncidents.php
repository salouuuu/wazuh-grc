<?php

namespace App\Console\Commands;

use App\Models\Incident;
use App\Services\IncidentTriage;
use App\Services\WazuhAlertService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Applique le tri (regroupement, conformité SCA) aux incidents
 * déjà en base. Aucun incident n'est supprimé : les doublons restent en base,
 * rattachés à leur incident principal (duplicate_of_id).
 */
class RegroupIncidents extends Command
{
    protected $signature = 'incidents:regroup
        {--dry-run : Affiche ce qui serait modifié sans rien enregistrer}';

    protected $description = 'Regroupe les incidents répétés (sans suppression)';

    public function handle(WazuhAlertService $wazuh, IncidentTriage $triage)
    {
        $dryRun = (bool) $this->option('dry-run');

        DB::beginTransaction();

        try {
            $this->enrich($wazuh, $triage);
            $grouped = $this->regroup($triage);
            $sca = $this->scaCompliance($triage);

            $this->table(['Action', 'Incidents'], [
                ['Rattachés à un incident identique (doublons conservés)', $grouped],
                ['Conformité SCA pré-remplie', $sca],
                ['Incidents principaux restants', Incident::primary()->count()],
                ['  dont à traiter (Nouveau / En cours)', Incident::primary()->open()->count()],
            ]);

            if ($dryRun) {
                DB::rollBack();
                $this->warn('Simulation (--dry-run) : aucune modification enregistrée.');
            } else {
                DB::commit();
                $this->info('Regroupement terminé.');
            }

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Erreur : '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    /** Récupère l'alerte d'origine dans l'Indexer pour les incidents importés avant le tri. */
    private function enrich(WazuhAlertService $wazuh, IncidentTriage $triage): void
    {
        Incident::whereNull('wazuh_data')->chunkById(100, function ($incidents) use ($wazuh, $triage) {
            try {
                $alerts = $wazuh->getAlertsByIds($incidents->pluck('wazuh_alert_id')->all());
            } catch (\Throwable $e) {
                $this->warn('Indexer indisponible, empreinte de secours utilisée : '.$e->getMessage());
                $alerts = [];
            }

            foreach ($incidents as $incident) {
                $alert = $alerts[$incident->wazuh_alert_id] ?? null;

                $incident->update($alert ? [
                    'wazuh_data' => $alert,
                    'fingerprint' => $triage->fingerprint($alert),
                    'category' => $triage->category($alert),
                ] : [
                    'fingerprint' => $incident->fingerprint ?? $triage->fingerprintFromIncident($incident),
                    'category' => $incident->category ?? Incident::CATEGORY_SECURITY,
                ]);
            }
        });
    }

    private function regroup(IncidentTriage $triage): int
    {
        $grouped = 0;

        Incident::primary()
            ->whereDoesntHave('occurrenceLog')
            ->orderBy('detected_at')
            ->orderBy('id')
            ->get()
            ->each(function (Incident $incident) use ($triage, &$grouped) {
                $master = Incident::primary()
                    ->whereKeyNot($incident->id)
                    ->where('fingerprint', $incident->fingerprint)
                    ->whereIn('status', Incident::GROUPABLE_STATUSES)
                    ->whereHas('occurrenceLog')
                    ->latest('detected_at')
                    ->first();

                // Un incident déjà travaillé par un analyste n'est jamais rattaché à un autre.
                if ($master && $this->untouched($incident)) {
                    $incident->update(['duplicate_of_id' => $master->id]);
                    $triage->attach($master, $incident->wazuh_alert_id, $incident->detected_at ?? now());
                    $this->line("  #{$incident->id} rattaché à #{$master->id} : ".mb_strimwidth($incident->title, 0, 70, '…'));
                    $grouped++;

                    return;
                }

                $incident->last_seen_at ??= $incident->detected_at;
                $incident->save();
                $triage->attach($incident, $incident->wazuh_alert_id, $incident->detected_at ?? now());
            });

        return $grouped;
    }

    private function untouched(Incident $incident): bool
    {
        return $incident->status === 'new'
            && blank($incident->comment)
            && blank($incident->action_corrective)
            && blank($incident->responsable_user_id)
            && blank($incident->compliance_status)
            && blank($incident->compliance_control);
    }

    private function scaCompliance(IncidentTriage $triage): int
    {
        $count = 0;

        Incident::primary()
            ->whereNull('compliance_status')
            ->where('category', Incident::CATEGORY_COMPLIANCE)
            ->whereNotNull('wazuh_data')
            ->get()
            ->each(function (Incident $incident) use ($triage, &$count) {
                $values = $triage->scaCompliance($incident->wazuh_data);

                if ($values) {
                    $incident->update($values);
                    $count++;
                }
            });

        return $count;
    }
}
