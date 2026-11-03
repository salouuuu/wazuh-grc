<?php

use App\Mail\IncidentAssigned;
use App\Models\ComplianceControl;
use App\Models\Incident;
use App\Models\User;
use Database\Seeders\ComplianceControlSeeder;
use Illuminate\Support\Facades\Mail;

function makeIncident(array $attributes = []): Incident
{
    static $n = 0;
    $n++;

    return Incident::create([
        'wazuh_alert_id' => "alert-$n",
        'rule_id' => '5402',
        'title' => "Incident $n",
        'description' => 'Élévation de privilèges via sudo',
        'severity' => 10,
        'agent_name' => 'agent1',
        'agent_ip' => '192.168.30.131',
        'status' => 'new',
        'detected_at' => now(),
        'probability' => 'Élevée',
        'impact' => 'Élevé',
        'risk_level' => 'Élevé',
        'compliance' => "ISO 27001 - A.8.2 : Droits d'accès privilégiés",
        ...$attributes,
    ]);
}

function evaluation(array $overrides = []): array
{
    return [
        'probability' => 'Moyenne',
        'impact' => 'Critique',
        'status' => 'in_progress',
        ...$overrides,
    ];
}

test('les pages GRC sont réservées aux utilisateurs connectés', function () {
    $incident = makeIncident();

    $this->get('/dashboard')->assertRedirect('/login');
    $this->get('/incidents')->assertRedirect('/login');
    $this->get("/incidents/{$incident->id}")->assertRedirect('/login');
    $this->put("/incidents/{$incident->id}", evaluation())->assertRedirect('/login');

    expect($incident->fresh()->status)->toBe('new');
});

test('le tableau de bord affiche les indicateurs', function () {
    makeIncident(['risk_level' => 'Critique']);
    makeIncident(['status' => 'resolved', 'compliance_status' => 'Conforme']);

    $this->actingAs(User::factory()->create())
        ->get('/dashboard')
        ->assertOk()
        ->assertViewHas('totalIncidents', 2)
        ->assertViewHas('criticalIncidents', 1)
        ->assertViewHas('resolvedIncidents', 1)
        ->assertViewHas('complianceDistribution', fn ($d) => $d['Conforme'] === 1 && $d['À évaluer'] === 1);
});

test('la liste des incidents est paginée et filtrable', function () {
    foreach (range(1, 30) as $i) {
        makeIncident();
    }
    makeIncident(['title' => 'Brute force SSH', 'agent_name' => 'web-01']);

    $user = User::factory()->create();

    $this->actingAs($user)->get('/incidents')
        ->assertOk()
        ->assertViewHas('incidents', fn ($p) => $p->count() === 25 && $p->total() === 31);

    $this->actingAs($user)->get('/incidents?search=web-01')
        ->assertOk()
        ->assertSee('Brute force SSH')
        ->assertViewHas('incidents', fn ($p) => $p->total() === 1);
});

test('la fiche incident affiche une matrice cohérente', function () {
    $incident = makeIncident();

    $this->actingAs(User::factory()->create())
        ->get("/incidents/{$incident->id}")
        ->assertOk()
        ->assertSee('Matrice de risque')
        ->assertSee("Droits d'accès privilégiés");
});

test('l’évaluation recalcule le risque et conserve la suggestion de conformité', function () {
    $this->seed(ComplianceControlSeeder::class);
    $incident = makeIncident();
    $control = ComplianceControl::where('control_code', 'A.8.2')->sole();

    $this->actingAs(User::factory()->create())
        ->put("/incidents/{$incident->id}", evaluation([
            'compliance_status' => 'Non conforme',
            'compliance_control' => $control->id,
            'comment' => 'Usage de sudo non autorisé',
        ]))
        ->assertRedirect("/incidents/{$incident->id}")
        ->assertSessionHas('success');

    expect($incident->fresh())
        ->risk_level->toBe('Élevé')            // Moyenne (2) × Critique (4) = 8
        ->status->toBe('in_progress')
        ->compliance_status->toBe('Non conforme')
        ->compliance_control->toEqual($control->id)
        ->compliance->toBe("ISO 27001 - A.8.2 : Droits d'accès privilégiés");
});

test('les valeurs invalides sont refusées', function () {
    $incident = makeIncident();

    $this->actingAs(User::factory()->create())
        ->put("/incidents/{$incident->id}", evaluation([
            'probability' => 'Énorme',
            'status' => 'deleted',
            'compliance_control' => 999,
            'responsable_user_id' => 999,
        ]))
        ->assertSessionHasErrors(['probability', 'status', 'compliance_control', 'responsable_user_id']);

    expect($incident->fresh()->status)->toBe('new');
});

test('le responsable reçoit un e-mail uniquement lors de l’affectation', function () {
    Mail::fake();
    $incident = makeIncident();
    $analyst = User::factory()->create();
    $responsable = User::factory()->create();

    $this->actingAs($analyst)
        ->put("/incidents/{$incident->id}", evaluation(['responsable_user_id' => $responsable->id]));

    $this->actingAs($analyst)
        ->put("/incidents/{$incident->id}", evaluation(['responsable_user_id' => $responsable->id, 'comment' => 'suivi']));

    Mail::assertSent(IncidentAssigned::class, 1);
    Mail::assertSent(IncidentAssigned::class, fn ($mail) => $mail->hasTo($responsable->email));
});

test('la case « Renvoyer l’e-mail » renvoie l’e-mail au même responsable', function () {
    Mail::fake();
    $incident = makeIncident();
    $analyst = User::factory()->create();
    $responsable = User::factory()->create();

    $this->actingAs($analyst)
        ->put("/incidents/{$incident->id}", evaluation(['responsable_user_id' => $responsable->id]));

    $this->actingAs($analyst)
        ->put("/incidents/{$incident->id}", evaluation(['responsable_user_id' => $responsable->id, 'resend_email' => '1']));

    // Sans responsable, la case ne fait rien
    $this->actingAs($analyst)
        ->put("/incidents/{$incident->id}", evaluation(['responsable_user_id' => null, 'resend_email' => '1']));

    Mail::assertSent(IncidentAssigned::class, 2);
    Mail::assertSent(IncidentAssigned::class, fn ($mail) => $mail->hasTo($responsable->email));
});

test('un échec d’envoi du mail ne bloque pas l’évaluation', function () {
    Mail::shouldReceive('to')->andThrow(new RuntimeException('SMTP indisponible'));
    $incident = makeIncident();
    $responsable = User::factory()->create();

    $this->actingAs(User::factory()->create())
        ->put("/incidents/{$incident->id}", evaluation(['responsable_user_id' => $responsable->id]))
        ->assertRedirect("/incidents/{$incident->id}")
        ->assertSessionHas('success', fn ($msg) => str_contains($msg, 'n’a pas pu être envoyé'));

    expect($incident->fresh()->responsable_user_id)->toBe($responsable->id);
});
