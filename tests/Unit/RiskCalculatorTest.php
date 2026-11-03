<?php

use App\Services\RiskCalculator;

beforeEach(function () {
    $this->risk = new RiskCalculator();
});

test('le niveau de risque suit la matrice du projet', function (string $probability, string $impact, string $expected) {
    expect($this->risk->riskLevel($probability, $impact))->toBe($expected);
})->with([
    ['Faible', 'Faible', 'Faible'],
    ['Faible', 'Élevé', 'Moyen'],         // cas de l'incident #40 après correction
    ['Élevée', 'Faible', 'Moyen'],
    ['Faible', 'Critique', 'Moyen'],
    ['Moyenne', 'Moyen', 'Moyen'],
    ['Élevée', 'Moyen', 'Élevé'],
    ['Moyenne', 'Élevé', 'Élevé'],
    ['Moyenne', 'Critique', 'Élevé'],
    ['Élevée', 'Élevé', 'Élevé'],
    ['Élevée', 'Critique', 'Critique'],
    ['Très élevée', 'Critique', 'Critique'],
]);

test('la matrice affichée est identique au calcul', function () {
    $matrix = $this->risk->matrix();

    expect($matrix)->toHaveCount(4);
    expect(array_key_first($matrix))->toBe('Très élevée');

    foreach ($matrix as $probability => $row) {
        expect($row)->toHaveCount(4);

        foreach ($row as $impact => $level) {
            expect($level)->toBe($this->risk->riskLevel($probability, $impact));
        }
    }
});

test('évaluation initiale à partir du niveau Wazuh', function (int $level, string $probability, string $impact, string $risk) {
    expect($this->risk->fromWazuhLevel($level))->toBe([
        'probability' => $probability,
        'impact' => $impact,
        'risk_level' => $risk,
    ]);
})->with([
    [3, 'Faible', 'Faible', 'Faible'],
    [7, 'Moyenne', 'Moyen', 'Moyen'],
    [8, 'Moyenne', 'Moyen', 'Moyen'],
    [10, 'Élevée', 'Élevé', 'Élevé'],
    [12, 'Très élevée', 'Critique', 'Critique'],
    [15, 'Très élevée', 'Critique', 'Critique'],
]);

test('suggestion de conformité ISO 27001 à partir des groupes Wazuh', function () {
    expect($this->risk->complianceFromGroups(['syslog', 'sudo']))
        ->toBe("ISO 27001 - A.8.2 : Droits d'accès privilégiés");

    expect($this->risk->complianceFromGroups(['authentication_failed', 'authentication_success']))
        ->toBe('ISO 27001 - A.5.16 : Gestion des identités');

    expect($this->risk->complianceFromGroups(['ossec', 'syscheck', 'sudo']))
        ->toContain('A.8.9')->toContain('A.8.2');

    expect($this->risk->complianceFromGroups(['ossec']))->toBe('À évaluer');
});
