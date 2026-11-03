<?php

use App\Models\Incident;
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
});

function wazuhHit(string $id, int $level = 10, array $groups = ['sudo'], string $timestamp = '2026-10-30T10:00:00.000Z', string $user = 'kibnd'): array
{
    return [
        '_id' => $id,
        '_source' => [
            '@timestamp' => $timestamp,
            'rule' => ['id' => '5402', 'level' => $level, 'description' => "Alerte $id", 'groups' => $groups],
            'agent' => ['id' => '001', 'name' => 'agent1', 'ip' => '192.168.30.131'],
            'data' => ['srcuser' => $user, 'command' => '/usr/bin/chmod 755 /etc/passwd'],
        ],
    ];
}

function fakeIndexer(array $hits): void
{
    Http::fake([
        'indexer.test:9200/*' => Http::response(['hits' => ['hits' => $hits]]),
    ]);
}

test('les alertes Wazuh sont converties en incidents GRC', function () {
    fakeIndexer([wazuhHit('A1', 12)]);

    expect(app(WazuhAlertService::class)->importAlerts())->toBe(1);

    $incident = Incident::sole();

    expect($incident)
        ->wazuh_alert_id->toBe('A1')
        ->rule_id->toBe('5402')
        ->severity->toBe(12)
        ->agent_name->toBe('agent1')
        ->agent_ip->toBe('192.168.30.131')
        ->source_user->toBe('kibnd')
        ->status->toBe('new')
        ->probability->toBe('Très élevée')
        ->impact->toBe('Critique')
        ->risk_level->toBe('Critique')
        ->compliance->toBe("ISO 27001 - A.8.2 : Droits d'accès privilégiés");
});

test('une alerte déjà importée n’est pas dupliquée', function () {
    fakeIndexer([wazuhHit('A1', user: 'alice'), wazuhHit('A2', user: 'bob')]);

    $service = app(WazuhAlertService::class);

    expect($service->importAlerts())->toBe(2);
    expect($service->importAlerts())->toBe(0);
    expect(Incident::count())->toBe(2);
});

test('la limite d’import est respectée', function () {
    fakeIndexer([wazuhHit('A1', user: 'alice'), wazuhHit('A2', user: 'bob'), wazuhHit('A3', user: 'carol')]);

    expect(app(WazuhAlertService::class)->importAlerts(limit: 2))->toBe(2);
    expect(Incident::count())->toBe(2);
});

test('la requête filtre sur le niveau minimum et reprend depuis le dernier import', function () {
    fakeIndexer([wazuhHit('A1', timestamp: '2026-10-30T10:00:00.000Z')]);
    $service = app(WazuhAlertService::class);
    $service->importAlerts();

    fakeIndexer([]);
    $service->importAlerts(minLevel: 7);

    Http::assertSent(function (Request $request) {
        $filters = $request['query']['bool']['filter'];

        return str_ends_with($request->url(), '/wazuh-alerts-4.x-*/_search')
            && $request['sort'][0]['@timestamp']['order'] === 'asc'
            && $filters[0]['range']['rule.level']['gte'] === 7
            && $filters[1]['range']['@timestamp']['gte'] === '2026-10-30T09:50:00Z';
    });
});

test('une IP d’agent invalide ou absente est ignorée', function () {
    $hit = wazuhHit('A1');
    unset($hit['_source']['agent']['ip']);
    fakeIndexer([$hit]);

    app(WazuhAlertService::class)->importAlerts();

    expect(Incident::sole()->agent_ip)->toBeNull();
});

test('la commande wazuh:import signale une erreur de l’Indexer', function () {
    Http::fake(['indexer.test:9200/*' => Http::response('Unauthorized', 401)]);

    $this->artisan('wazuh:import')
        ->expectsOutputToContain('Erreur Wazuh Indexer (HTTP 401)')
        ->assertFailed();

    expect(Incident::count())->toBe(0);
});

test('la commande wazuh:import affiche le nombre d’alertes importées', function () {
    fakeIndexer([wazuhHit('A1'), wazuhHit('A2')]);

    $this->artisan('wazuh:import')
        ->expectsOutput('2 nouvelle(s) alerte(s) Wazuh importée(s).')
        ->assertSuccessful();
});
