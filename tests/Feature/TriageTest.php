<?php

use App\Models\Incident;
use App\Models\IncidentOccurrence;
use App\Models\User;
use App\Services\WazuhAlertService;
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

    // Une seule fausse réponse, dont le contenu est modifiable entre deux imports
    $GLOBALS['indexerHits'] = [];
    Http::fake(['indexer.test:9200/*' => fn () => Http::response(['hits' => ['hits' => $GLOBALS['indexerHits']]])]);
});

function alertHit(string $id, array $source = [], string $timestamp = '2026-10-30T10:00:00.000Z'): array
{
    return [
        '_id' => $id,
        '_source' => array_replace_recursive([
            '@timestamp' => $timestamp,
            'rule' => ['id' => '5402', 'level' => 10, 'description' => 'Sudo vers root', 'groups' => ['sudo']],
            'agent' => ['id' => '001', 'name' => 'agent1', 'ip' => '192.168.30.131'],
            'data' => ['srcuser' => 'kibnd'],
        ], $source),
    ];
}

function scaHit(string $id, string $checkId, string $timestamp = '2026-10-30T10:00:00.000Z'): array
{
    return [
        '_id' => $id,
        '_source' => [
            '@timestamp' => $timestamp,
            'rule' => [
                'id' => '19011', 'level' => 9, 'groups' => ['sca'], 'cis' => ['5.1.15'],
                'description' => "CIS Ubuntu: contrôle $checkId: Status changed from passed to failed",
            ],
            'agent' => ['id' => '000', 'name' => 'master'],
            'data' => ['sca' => [
                'policy' => 'CIS Ubuntu Linux 24.04 LTS Benchmark v1.0.0.',
                'check' => ['id' => $checkId, 'title' => 'Ensure sshd MACs are configured.', 'result' => 'failed', 'remediation' => 'Edit /etc/ssh/sshd_config'],
            ]],
        ],
    ];
}

function indexerReturns(array $hits): void
{
    $GLOBALS['indexerHits'] = $hits;
}

test('une alerte qui se répète est regroupée dans un seul incident', function () {
    indexerReturns([
        alertHit('A1', timestamp: '2026-10-30T10:00:00.000Z'),
        alertHit('A2', timestamp: '2026-10-30T10:01:00.000Z'),
        alertHit('A3', timestamp: '2026-10-30T10:02:00.000Z'),
    ]);

    $service = app(WazuhAlertService::class);

    expect($service->importAlerts())->toBe(3);
    expect($service->lastImportStats()['grouped'])->toBe(2);

    $incident = Incident::sole();
    expect($incident->occurrences)->toBe(3);
    expect($incident->last_seen_at->toIso8601ZuluString())->toBe('2026-10-30T10:02:00Z');
    expect(IncidentOccurrence::pluck('wazuh_alert_id')->sort()->values()->all())->toBe(['A1', 'A2', 'A3']);

    // Réimport : les alertes déjà rattachées ne sont pas recomptées
    expect($service->importAlerts())->toBe(0);
    expect(Incident::sole()->occurrences)->toBe(3);
});

test('des alertes sur des objets différents restent des incidents distincts', function () {
    indexerReturns([
        alertHit('A1', ['data' => ['srcuser' => 'alice']]),
        alertHit('A2', ['data' => ['srcuser' => 'bob']]),
        alertHit('A3', ['agent' => ['name' => 'web-01']]),
        alertHit('A4', ['rule' => ['id' => '5403']]),
    ]);

    app(WazuhAlertService::class)->importAlerts();

    expect(Incident::count())->toBe(4);
});

test('un contrôle SCA en échec est regroupé par contrôle et marqué non conforme', function () {
    indexerReturns([
        scaHit('S1', '35654', '2026-08-03T13:07:15.000Z'),
        scaHit('S2', '35654', '2026-10-30T21:23:04.000Z'),
        scaHit('S3', '35660'),
    ]);

    app(WazuhAlertService::class)->importAlerts();

    expect(Incident::count())->toBe(2);

    $incident = Incident::where('occurrences', 2)->sole();
    expect($incident)
        ->category->toBe(Incident::CATEGORY_COMPLIANCE)
        ->compliance_status->toBe('Non conforme')
        ->compliance_comment->toContain('5.1.15')->toContain('en échec');
});

test('une alerte qui revient après résolution ouvre un nouvel incident', function () {
    indexerReturns([alertHit('A1')]);
    $service = app(WazuhAlertService::class);
    $service->importAlerts();
    Incident::sole()->update(['status' => 'resolved']);

    indexerReturns([alertHit('A2', timestamp: '2026-10-30T11:00:00.000Z')]);
    $service->importAlerts();

    expect(Incident::count())->toBe(2);
    expect(Incident::where('status', 'new')->count())->toBe(1);
});

test('incidents:regroup rattache les anciens doublons sans rien supprimer', function () {
    $legacy = fn (string $id, array $attributes = []) => Incident::create([
        'wazuh_alert_id' => $id, 'rule_id' => '5402', 'title' => 'Sudo vers root', 'severity' => 10,
        'agent_name' => 'agent1', 'status' => 'new', 'detected_at' => '2026-10-30 10:00:00', ...$attributes,
    ]);

    $first = $legacy('A1');
    $second = $legacy('A2', ['detected_at' => '2026-10-30 11:00:00']);
    $evaluated = $legacy('A3', ['detected_at' => '2026-10-30 12:00:00', 'status' => 'in_progress', 'comment' => 'Analyse en cours']);
    $test = $legacy('T1', ['rule_id' => '100102', 'title' => 'Test GRC']);

    indexerReturns([
        alertHit('A1'),
        alertHit('A2', timestamp: '2026-10-30T11:00:00.000Z'),
        alertHit('A3', timestamp: '2026-10-30T12:00:00.000Z'),
        alertHit('T1', ['rule' => ['id' => '100102', 'groups' => ['grc_test']]]),
    ]);

    // Simulation : rien n'est modifié
    $this->artisan('incidents:regroup --dry-run')->assertSuccessful();
    expect(Incident::whereNotNull('fingerprint')->count())->toBe(0);
    expect(IncidentOccurrence::count())->toBe(0);

    $this->artisan('incidents:regroup')->assertSuccessful();

    expect(Incident::count())->toBe(4);
    expect($second->fresh()->duplicate_of_id)->toBe($first->id);
    expect($first->fresh())->occurrences->toBe(2)->wazuh_data->not->toBeNull();
    expect($evaluated->fresh())->duplicate_of_id->toBeNull()->status->toBe('in_progress');
    expect($test->fresh()->status)->toBe('new');

    // Idempotent
    $this->artisan('incidents:regroup')->assertSuccessful();
    expect($first->fresh()->occurrences)->toBe(2);
    expect(IncidentOccurrence::count())->toBe(4);
});

test('le traitement en masse change le statut des incidents sélectionnés', function () {
    indexerReturns([alertHit('A1', ['data' => ['srcuser' => 'alice']]), alertHit('A2', ['data' => ['srcuser' => 'bob']])]);
    app(WazuhAlertService::class)->importAlerts();
    $ids = Incident::pluck('id')->all();
    $user = User::factory()->create();

    $this->actingAs($user)->post('/incidents/bulk', ['ids' => $ids, 'status' => 'deleted'])
        ->assertSessionHasErrors('status');

    $this->actingAs($user)->post('/incidents/bulk', ['ids' => $ids, 'status' => 'resolved'])
        ->assertSessionHas('success');

    expect(Incident::where('status', 'resolved')->count())->toBe(2);
});

test('la liste masque par défaut les doublons', function () {
    $make = fn (string $id, array $attributes = []) => Incident::create([
        'wazuh_alert_id' => $id, 'rule_id' => '1', 'title' => "Incident $id", 'severity' => 8, 'status' => 'new', ...$attributes,
    ]);
    $master = $make('M');
    $make('D', ['duplicate_of_id' => $master->id]);
    $make('R', ['status' => 'resolved']);

    $user = User::factory()->create();

    $this->actingAs($user)->get('/incidents')
        ->assertOk()
        ->assertViewHas('incidents', fn ($p) => $p->pluck('wazuh_alert_id')->sort()->values()->all() === ['M', 'R']);

    $this->actingAs($user)->get('/incidents?status=all&duplicates=1')
        ->assertViewHas('incidents', fn ($p) => $p->total() === 3);

    $this->actingAs($user)->get('/dashboard')
        ->assertOk()
        ->assertViewHas('totalIncidents', 2)
        ->assertViewHas('toTriage', 1);
});

test('la fiche affiche le contrôle SCA et les référentiels Wazuh', function () {
    indexerReturns([scaHit('S1', '35654'), scaHit('S2', '35654', '2026-10-31T10:00:00.000Z')]);
    app(WazuhAlertService::class)->importAlerts();

    $this->actingAs(User::factory()->create())
        ->get('/incidents/'.Incident::sole()->id)
        ->assertOk()
        ->assertSee('Correction recommandée')
        ->assertSee('Edit /etc/ssh/sshd_config')
        ->assertSee('CIS : 5.1.15')
        ->assertSee('Historique des alertes (2)');
});
