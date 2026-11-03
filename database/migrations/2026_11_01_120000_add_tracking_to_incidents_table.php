<?php

use App\Models\Incident;
use App\Services\IncidentTriage;
use App\Services\RiskCalculator;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            // Photo de l'incident à sa détection (figée) : état initial du tableau de suivi
            $table->json('initial_state')->nullable();
            // Changements passed / failed du contrôle CIS relevés par Wazuh
            $table->json('sca_history')->nullable();
        });

        // Incidents existants : état initial reconstitué à partir de l'alerte Wazuh d'origine
        $risk = app(RiskCalculator::class);
        $triage = app(IncidentTriage::class);

        Incident::query()->each(function (Incident $incident) use ($risk, $triage) {
            $original = [
                ...$risk->fromWazuhLevel((int) $incident->severity),
                'compliance_status' => $triage->scaCompliance($incident->wazuh_data ?? [])['compliance_status'] ?? null,
            ];

            $incident->timestamps = false;
            $incident->forceFill(['initial_state' => $incident->stateSnapshot($original)])->saveQuietly();
        });
    }

    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->dropColumn(['initial_state', 'sca_history']);
        });
    }
};
