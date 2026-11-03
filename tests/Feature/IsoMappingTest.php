<?php

use App\Models\ComplianceControl;
use App\Models\Incident;
use App\Models\User;
use App\Services\IsoMapping;
use Database\Seeders\ComplianceControlSeeder;
use Database\Seeders\IsoWazuhControlSeeder;

function isoIncident(array $attributes = []): Incident
{
    static $n = 0;
    $n++;

    return Incident::create([
        'wazuh_alert_id' => "iso-$n",
        'rule_id' => '19011',
        'title' => "Incident ISO $n",
        'severity' => 9,
        'agent_name' => 'agent1',
        'status' => 'new',
        'detected_at' => now(),
        'probability' => 'Élevée',
        'impact' => 'Élevé',
        'risk_level' => 'Élevé',
        ...$attributes,
    ]);
}

test('la table couvre les 114 contrôles 2013 et pointe vers des contrôles 2022 existants', function () {
    $mapping = config('iso27001.mapping');
    $controls = config('iso27001.controls');

    expect($mapping)->toHaveCount(114)
        ->and($controls)->toHaveCount(93);

    foreach ($mapping as $code2013 => $codes2022) {
        foreach ($codes2022 as $code) {
            expect($controls)->toHaveKey($code);
        }
    }
});

test('les références Wazuh 2013 sont traduites en 2022', function () {
    $iso = app(IsoMapping::class);

    expect($iso->to2022('A.10.1.1'))->toBe(['A.8.24'])
        ->and($iso->to2022('A.13.1.1'))->toBe(['A.8.20'])
        ->and($iso->to2022('A.9.4.3'))->toBe(['A.5.17'])
        ->and($iso->to2022('A.18.2.3'))->toBe(['A.5.36', 'A.8.8'])
        ->and($iso->to2022('A.8.3.2'))->toBe(['A.7.10'])
        ->and($iso->to2022('A.11.2.7'))->toBe(['A.7.14'])
        ->and($iso->to2022('inconnu'))->toBe([]);

    $incident = isoIncident([
        'category' => Incident::CATEGORY_COMPLIANCE,
        'wazuh_data' => ['rule' => ['iso_27001-2013' => ['A.10.1.1', 'A.13.1.1']]],
    ]);

    expect($iso->suggestions($incident))->toBe([
        'A.8.24' => ['ISO 27001:2013 A.10.1.1'],
        'A.8.20' => ['ISO 27001:2013 A.13.1.1'],
    ]);
});

test('le seeder ajoute les contrôles sans modifier les existants', function () {
    $this->seed(ComplianceControlSeeder::class);
    ComplianceControl::where('control_code', 'A.8.9')->update(['title' => 'Titre personnalisé']);

    $this->seed(IsoWazuhControlSeeder::class);
    $this->seed(IsoWazuhControlSeeder::class);

    expect(ComplianceControl::count())->toBe(20)
        ->and(ComplianceControl::where('control_code', 'A.8.9')->value('title'))->toBe('Titre personnalisé')
        ->and(ComplianceControl::where('control_code', 'A.8.24')->value('title'))->toBe('Utilisation de la cryptographie');
});

test('la fiche pré-sélectionne le contrôle suggéré sans écraser le choix de l’analyste', function () {
    $this->seed(ComplianceControlSeeder::class);
    $this->seed(IsoWazuhControlSeeder::class);
    $crypto = ComplianceControl::where('control_code', 'A.8.24')->first();
    $access = ComplianceControl::where('control_code', 'A.5.15')->first();

    $data = ['rule' => ['iso_27001-2013' => ['A.10.1.1']]];
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('incidents.show', isoIncident(['wazuh_data' => $data])))
        ->assertOk()
        ->assertSee('Contrôles suggérés')
        ->assertSee('A.8.24 est pré-sélectionné')
        ->assertSee('value="' . $crypto->id . '"' . "\n                selected", false);

    $this->actingAs($user)
        ->get(route('incidents.show', isoIncident(['wazuh_data' => $data, 'compliance_control' => $access->id])))
        ->assertOk()
        ->assertSee('value="' . $access->id . '"' . "\n                selected", false)
        ->assertDontSee('value="' . $crypto->id . '"' . "\n                selected", false)
        ->assertDontSee('est pré-sélectionné');
});

test('la suggestion par type d’alerte passe en premier et c’est elle qui est pré-sélectionnée', function () {
    $this->seed(ComplianceControlSeeder::class);
    $this->seed(IsoWazuhControlSeeder::class);
    $configuration = ComplianceControl::where('control_code', 'A.8.9')->first();

    // Cas réel de l'incident #40 : contrôle CIS sshd MACs
    $incident = isoIncident([
        'category' => Incident::CATEGORY_COMPLIANCE,
        'compliance' => 'ISO 27001 - A.8.9 : Gestion de la configuration',
        'wazuh_data' => ['rule' => ['iso_27001-2013' => ['A.10.1.1', 'A.13.1.1']]],
    ]);

    expect(app(IsoMapping::class)->suggestions($incident))->toBe([
        'A.8.9' => ['type d’alerte (groupes de la règle Wazuh)'],
        'A.8.24' => ['ISO 27001:2013 A.10.1.1'],
        'A.8.20' => ['ISO 27001:2013 A.13.1.1'],
    ]);

    $this->actingAs(User::factory()->create())
        ->get(route('incidents.show', $incident))
        ->assertOk()
        ->assertSeeInOrder(['Contrôles suggérés', 'A.8.9', 'A.8.24', 'A.8.20'])
        ->assertSee('★ A.8.9 est pré-sélectionné')
        ->assertDontSee('Suggestion automatique (groupes de la règle Wazuh)')
        ->assertSee('value="' . $configuration->id . '"' . "\n                selected", false);
});

test('la page de correspondance est accessible', function () {
    $this->get(route('iso.mapping'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create())
        ->get(route('iso.mapping'))
        ->assertOk()
        ->assertSee('A.10.1.1')
        ->assertSee('Utilisation de la cryptographie')
        ->assertSee('Renseignements sur les menaces');
});
