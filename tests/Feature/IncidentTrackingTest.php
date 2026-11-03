<?php

use App\Models\Incident;
use App\Models\User;
use App\Services\WazuhAlertService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('services.wazuh', [
        'url' => 'https://indexer.test:9200',
        'username' => 'admin',
        'password' => 'secret',
        'verify' => false,
        'index' => 'wazuh-alerts-4.x-*',
        'min_level' => 8,
    ]);

    // Une seule fausse réponse, dont le contenu est modifiable entre deux appels
    $GLOBALS['trackingAlerts'] = $GLOBALS['trackingHistory'] = [];
    Http::fake(['indexer.test:9200/*' => function (Request $request) {
        $isHistory = isset($request['query']['bool']['filter'][0]['terms']);

        return Http::response(['hits' => ['hits' => $GLOBALS[$isHistory ? 'trackingHistory' : 'trackingAlerts']]]);
    }]);
});

/** Alerte SCA "passed → failed" (règle 19011) qui crée l'incident. */
function trackingAlert(string $id, string $timestamp): array
{
    return [
        '_id' => $id,
        '_source' => [
            '@timestamp' => $timestamp,
            'rule' => ['id' => '19011', 'level' => 9, 'groups' => ['sca'], 'description' => 'Ensure sshd MACs are configured.'],
            'agent' => ['id' => '001', 'name' => 'agent1'],
            'data' => ['sca' => ['check' => ['id' => '35654', 'title' => 'Ensure sshd MACs are configured.', 'result' => 'failed', 'previous_result' => 'passed']]],
        ],
    ];
}

/** Changement de résultat du contrôle relevé par Wazuh (19007 / 19010 / 19011). */
function trackingEvent(string $id, string $timestamp, string $result, ?string $previous, string $agent = 'agent1'): array
{
    $check = ['id' => '35654', 'result' => $result];

    if ($previous) {
        $check['previous_result'] = $previous;
    }

    return ['_id' => $id, '_source' => ['@timestamp' => $timestamp, 'agent' => ['name' => $agent], 'data' => ['sca' => ['check' => $check]]]];
}

/** L'Indexer renvoie les alertes à importer, puis l'historique du contrôle. */
function trackingIndexer(array $alerts, array $history): void
{
    $GLOBALS['trackingAlerts'] = $alerts;
    $GLOBALS['trackingHistory'] = $history;
}

/** Historique réel du contrôle CIS 35654 sur agent1 (juillet → novembre 2026). */
function realHistory(): array
{
    return [
        trackingEvent('e1', '2026-07-09T21:45:24.414Z', 'failed', null),
        trackingEvent('e2', '2026-08-03T13:35:51.726Z', 'passed', 'failed'),
        trackingEvent('e3', '2026-08-03T13:40:42.572Z', 'failed', 'passed'),
        trackingEvent('e4', '2026-09-13T15:14:03.400Z', 'passed', 'failed'),
        trackingEvent('e5', '2026-09-13T16:50:37.441Z', 'failed', 'passed'),
        trackingEvent('e6', '2026-10-15T13:21:54.971Z', 'passed', 'failed'),
        trackingEvent('e7', '2026-10-31T18:51:57.097Z', 'failed', 'passed'),
        trackingEvent('e8', '2026-11-01T17:20:34.751Z', 'passed', 'failed'),
        trackingEvent('m1', '2026-10-31T12:26:32.111Z', 'failed', 'passed', 'master'),
    ];
}

test('l’état initial est figé à la création et ne bouge plus ensuite', function () {
    $incident = Incident::create([
        'wazuh_alert_id' => 'T1', 'rule_id' => '5402', 'title' => 'Sudo', 'severity' => 9, 'status' => 'new',
        'detected_at' => '2026-08-03 13:40:42', 'probability' => 'Élevée', 'impact' => 'Élevé', 'risk_level' => 'Élevé',
        'compliance_status' => 'Non conforme',
    ]);

    $incident->update(['probability' => 'Faible', 'risk_level' => 'Faible', 'compliance_status' => 'Conforme']);

    expect($incident->fresh()->initial_state)->toMatchArray([
        'date' => '2026-08-03T13:40:42+00:00',
        'probability' => 'Élevée',
        'impact' => 'Élevé',
        'risk_level' => 'Élevé',
        'compliance_status' => 'Non conforme',
    ]);
});

test('l’import relève l’historique du contrôle CIS sans toucher à l’évaluation', function () {
    trackingIndexer([trackingAlert('A1', '2026-08-03T13:40:42.572Z')], realHistory());

    app(WazuhAlertService::class)->importAlerts();

    $incident = Incident::sole();

    expect($incident->sca_history)->toHaveCount(8)
        ->and($incident->scaSummary())->toBe([
            'first_seen' => '2026-07-09T21:45:24.414Z',
            'result' => 'passed',
            'since' => '2026-11-01T17:20:34.751Z',
            'corrections' => 4,
            'regressions' => 3,
        ])
        ->and($incident->compliance_status)->toBe('Non conforme')
        ->and($incident->risk_level)->toBe('Élevé')
        ->and($incident->status)->toBe('new');
});

test('la relève de l’historique ne change pas la date de mise à jour', function () {
    trackingIndexer([trackingAlert('A1', '2026-08-03T13:40:42.572Z')], []);
    $service = app(WazuhAlertService::class);
    $service->importAlerts();

    $incident = Incident::sole();
    $incident->timestamps = false;
    $incident->forceFill(['updated_at' => '2026-10-01 10:00:00'])->saveQuietly();

    trackingIndexer([], realHistory());

    expect($service->syncScaHistory())->toBe(1)
        ->and($service->syncScaHistory())->toBe(0)
        ->and($incident->fresh()->updated_at->toDateTimeString())->toBe('2026-10-01 10:00:00');
});

test('la fiche affiche le tableau avant / après de l’incident #40', function () {
    trackingIndexer([trackingAlert('A1', '2026-08-03T13:40:42.572Z')], realHistory());
    app(WazuhAlertService::class)->importAlerts();

    $incident = Incident::sole();
    $incident->update([
        'status' => 'resolved', 'probability' => 'Faible', 'impact' => 'Élevé', 'risk_level' => 'Faible',
        'compliance_status' => 'Conforme', 'action_corrective' => 'MACs limités à hmac-sha2-256 et hmac-sha2-512',
    ]);

    $this->actingAs(User::factory()->create())
        ->get(route('incidents.show', $incident))
        ->assertOk()
        ->assertSeeInOrder([
            'Suivi de l', 'contrôle CIS 35654',
            'État initial', 'Ce qui a été fait', 'État final',
            '03/08/2026 (détection)', 'MACs limités à hmac-sha2-256', 'Statut : Résolu', '01/11/2026 (vérifié par Wazuh)',
            'Résultat CIS', 'failed', 'passed ✔',
            'Conformité', 'Non conforme', 'Conforme',
            'Probabilité / Impact', 'Élevée / Élevé', 'Faible / Élevé',
            'Risque', 'Élevé', 'Faible',
            'première détection le 09/07/2026', '4 correction(s), 3 régression(s)',
        ], false)
        ->assertDontSee('Régression :')
        ->assertDontSee('peut passer à « Résolu »', false);
});

test('la fiche signale une régression et une correction confirmée par Wazuh', function () {
    trackingIndexer([trackingAlert('A1', '2026-08-03T13:40:42.572Z')], array_slice(realHistory(), 0, 7));
    app(WazuhAlertService::class)->importAlerts();
    $incident = Incident::sole();
    $incident->update(['status' => 'resolved']);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('incidents.show', $incident))
        ->assertSee('⚠ Régression : Wazuh voit de nouveau ce contrôle en échec depuis le 31/10/2026.');

    // Nouveau scan après correction, incident encore "En cours"
    $incident->update([
        'status' => 'in_progress',
        'sca_history' => [...$incident->sca_history, ['at' => '2026-11-01T17:20:34.751Z', 'result' => 'passed', 'previous' => 'failed']],
    ]);

    $this->actingAs($user)
        ->get(route('incidents.show', $incident))
        ->assertSee('✔ Wazuh confirme la correction depuis le 01/11/2026', false)
        ->assertDontSee('Régression :');
});
