<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\IncidentOccurrence;
use Illuminate\Http\Request;
use App\Models\ComplianceControl;
use App\Models\User;
use App\Services\IncidentTriage;
use App\Services\IsoMapping;
use App\Services\RiskCalculator;

use App\Mail\IncidentAssigned;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class IncidentController extends Controller
{
public function index(Request $request)
{
    $search = $request->input('search');
    $filters = $request->only(['status', 'risk_level', 'category', 'agent_name']);
    $showDuplicates = $request->boolean('duplicates');

    $statusFilter = $filters['status'] ?? 'all';

    $incidents = Incident::query()
        ->with('complianceControl')
        ->unless($showDuplicates, fn ($q) => $q->primary())
        ->when($statusFilter !== 'all', fn ($q) => $q->where('status', $statusFilter))
        ->when($filters['risk_level'] ?? null, fn ($q, $v) => $q->where('risk_level', $v))
        ->when($filters['category'] ?? null, fn ($q, $v) => $q->where('category', $v))
        ->when($filters['agent_name'] ?? null, fn ($q, $v) => $q->where('agent_name', $v))
        ->when($search, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('rule_id', 'like', "%{$search}%")
                  ->orWhere('agent_name', 'like', "%{$search}%")
                  ->orWhere('agent_ip', 'like', "%{$search}%")
                  ->orWhere('source_user', 'like', "%{$search}%");
            });
        })
        ->orderByRaw('COALESCE(last_seen_at, detected_at) DESC')
        ->paginate(25)
        ->withQueryString();

    $agents = Incident::whereNotNull('agent_name')->distinct()->orderBy('agent_name')->pluck('agent_name');

    return view('incidents.index', compact('incidents', 'search', 'filters', 'statusFilter', 'showDuplicates', 'agents'));
}

public function dashboard()
{
    // Les doublons regroupés (duplicate_of_id) ne sont pas recomptés.
    $totalIncidents = Incident::primary()->count();

    $riskCounts = Incident::primary()->open()->selectRaw('risk_level, count(*) as total')
        ->groupBy('risk_level')
        ->pluck('total', 'risk_level');

    $statusCounts = Incident::primary()->selectRaw('status, count(*) as total')
        ->groupBy('status')
        ->pluck('total', 'status');

    $complianceCounts = Incident::primary()
        ->selectRaw('compliance_status, count(*) as total')
        ->groupBy('compliance_status')
        ->pluck('total', 'compliance_status');

    $criticalIncidents = $riskCounts['Critique'] ?? 0;

    $highRiskIncidents = $riskCounts['Élevé'] ?? 0;

    $resolvedIncidents = ($statusCounts['resolved'] ?? 0) + ($statusCounts['closed'] ?? 0);

    $openIncidents = ($statusCounts['new'] ?? 0) + ($statusCounts['in_progress'] ?? 0);

    $toTriage = $statusCounts['new'] ?? 0;

    $totalAlerts = IncidentOccurrence::count() ?: $totalIncidents;

    $overdueIncidents = Incident::primary()->whereNotNull('due_date')
        ->whereDate('due_date', '<', now())
        ->whereNotIn('status', ['resolved', 'closed'])
        ->count();

    // Répartition des risques (incidents à traiter uniquement)
    $riskDistribution = [];
    foreach (RiskCalculator::RISK_LEVELS as $level) {
        $riskDistribution[$level] = $riskCounts[$level] ?? 0;
    }

$statusDistribution = [];
foreach (Incident::STATUSES as $status => $label) {
    $statusDistribution[$label] = $statusCounts[$status] ?? 0;
}

$complianceDistribution = [
    'Conforme' => $complianceCounts['Conforme'] ?? 0,
    'Partiellement conforme' => $complianceCounts['Partiellement conforme'] ?? 0,
    'Non conforme' => $complianceCounts['Non conforme'] ?? 0,
    'À évaluer' => $complianceCounts[''] ?? 0,
];

$categoryDistribution = Incident::primary()->open()
    ->selectRaw("COALESCE(category, ?) as cat, count(*) as total", [Incident::CATEGORY_SECURITY])
    ->groupBy('cat')
    ->pluck('total', 'cat');

$agentDistribution = Incident::primary()->open()
    ->selectRaw('agent_name, count(*) as total')
    ->groupBy('agent_name')
    ->orderByDesc('total')
    ->pluck('total', 'agent_name');

// Alertes reçues par jour sur 30 jours (toutes occurrences, y compris répétitions)
$days = collect(range(29, 0))->mapWithKeys(fn ($i) => [now()->subDays($i)->format('Y-m-d') => 0]);
$alertsPerDay = $days->merge(
    IncidentOccurrence::where('detected_at', '>=', now()->subDays(29)->startOfDay())
        ->selectRaw('DATE(detected_at) as day, count(*) as total')
        ->groupBy('day')
        ->pluck('total', 'day')
)->mapWithKeys(fn ($total, $day) => [\Illuminate\Support\Carbon::parse($day)->format('d/m') => $total]);

$topRecurring = Incident::primary()
    ->where('occurrences', '>', 1)
    ->orderByDesc('occurrences')
    ->take(5)
    ->get();

$recentIncidents = Incident::primary()->open()
    ->orderByRaw('COALESCE(last_seen_at, detected_at) DESC')
    ->take(5)
    ->get();

return view('dashboard', compact(
    'totalIncidents',
    'criticalIncidents',
    'highRiskIncidents',
    'resolvedIncidents',
    'openIncidents',
    'toTriage',
    'totalAlerts',
    'overdueIncidents',
    'riskDistribution',
    'statusDistribution',
    'complianceDistribution',
    'categoryDistribution',
    'agentDistribution',
    'alertsPerDay',
    'topRecurring',
    'recentIncidents'
));
}

public function show(Incident $incident, RiskCalculator $risk, IsoMapping $iso)
{
    $complianceControls = ComplianceControl::orderBy('framework')
        ->orderBy('control_code')
        ->get();

    $users = User::orderBy('name')->get();

    $riskMatrix = $risk->matrix();

    $incident->load(['occurrenceLog', 'duplicateOf']);

    // Correspondance ISO 2013 (Wazuh) → ISO 2022 : proposée, jamais imposée
    $isoSuggestions = $iso->suggestions($incident);
    $suggestedControl = $iso->suggestedControl($incident, $complianceControls);

    return view('incidents.show', compact(
        'incident',
        'complianceControls',
        'users',
        'riskMatrix',
        'isoSuggestions',
        'suggestedControl'
    ));
}
    public function update(Request $request, Incident $incident, RiskCalculator $risk)
    {
$validated = $request->validate([
    'probability' => 'required|in:'.implode(',', array_keys(RiskCalculator::PROBABILITIES)),
    'impact' => 'required|in:'.implode(',', array_keys(RiskCalculator::IMPACTS)),
    'status' => 'required|in:'.implode(',', array_keys(Incident::STATUSES)),
    'comment' => 'nullable|string|max:2000',

    'action_corrective' => 'nullable|string|max:5000',
'responsable_user_id' => 'nullable|exists:users,id',
    'due_date' => 'nullable|date',
'compliance_status' => 'nullable|in:Conforme,Partiellement conforme,Non conforme',
'compliance_control' => 'nullable|exists:compliance_controls,id',
'compliance_comment' => 'nullable|string|max:5000',
]);

        $previousResponsable = $incident->responsable_user_id;
        $resend = $request->boolean('resend_email');

// La suggestion automatique de conformité (colonne "compliance") est conservée :
// l'évaluation de l'analyste est stockée dans compliance_status / compliance_control.
$incident->update([
    ...$validated,
    'risk_level' => $risk->riskLevel($validated['probability'], $validated['impact']),
]);

$message = 'Évaluation GRC mise à jour.';

// Envoyer un e-mail au responsable s'il vient d'être (ré)affecté, ou si le renvoi est demandé
if (!empty($validated['responsable_user_id'])
    && ($resend || (int) $validated['responsable_user_id'] !== (int) $previousResponsable)) {
    $responsable = User::find($validated['responsable_user_id']);

    if ($responsable && $responsable->email) {
        try {
            Mail::to($responsable->email)->send(
                new IncidentAssigned($incident, $responsable)
            );
        } catch (\Throwable $e) {
            Log::warning('Échec de l’envoi du mail d’affectation', [
                'incident_id' => $incident->id,
                'error' => $e->getMessage(),
            ]);

            $message = str_replace('Évaluation GRC mise à jour.', 'Évaluation GRC mise à jour (l’e-mail au responsable n’a pas pu être envoyé).', $message);
        }
    }
}
        return redirect()
            ->route('incidents.show', $incident)
            ->with('success', $message);
    }

    /**
     * Traitement en masse depuis la liste (tri des alertes).
     */
    public function bulk(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:incidents,id',
            'status' => 'required|in:'.implode(',', array_keys(Incident::STATUSES)),
        ]);

        $values = ['status' => $validated['status'], 'updated_at' => now()];

        $count = Incident::whereKey($validated['ids'])->update($values);

        return back()->with('success', "$count incident(s) passé(s) au statut « ".Incident::STATUSES[$validated['status']].' ».');
    }
}
