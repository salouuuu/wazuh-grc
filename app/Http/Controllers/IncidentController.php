<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use Illuminate\Http\Request;
use App\Models\ComplianceControl;

class IncidentController extends Controller
{
public function index(Request $request)
{
    $search = $request->input('search');

    $incidents = Incident::query()
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
        ->latest('detected_at')
        ->get();

    return view('incidents.index', compact('incidents', 'search'));
}

public function dashboard()
{
    $totalIncidents = Incident::count();

    $criticalIncidents = Incident::where('risk_level', 'Critique')->count();

    $highRiskIncidents = Incident::where('risk_level', 'Élevé')->count();

    $resolvedIncidents = Incident::whereIn('status', [
        'resolved',
        'closed'
    ])->count();

    $overdueIncidents = Incident::whereNotNull('due_date')
        ->whereDate('due_date', '<', now())
        ->whereNotIn('status', ['resolved', 'closed'])
        ->count();

    // Répartition des risques
    $riskDistribution = [
        'Faible' => Incident::where('risk_level', 'Faible')->count(),
        'Moyen' => Incident::where('risk_level', 'Moyen')->count(),
        'Élevé' => Incident::where('risk_level', 'Élevé')->count(),
        'Critique' => Incident::where('risk_level', 'Critique')->count(),
    ];

$statusDistribution = [
    'Nouveau' => Incident::where('status', 'new')->count(),
    'En cours' => Incident::where('status', 'in_progress')->count(),
    'Résolu' => Incident::where('status', 'resolved')->count(),
    'Clôturé' => Incident::where('status', 'closed')->count(),
];

$complianceDistribution = [
    'Conforme' => Incident::where('compliance_status', 'Conforme')->count(),
    'Partiellement conforme' => Incident::where('compliance_status', 'Partiellement conforme')->count(),
    'Non conforme' => Incident::where('compliance_status', 'Non conforme')->count(),
    'À évaluer' => Incident::whereNull('compliance_status')->count(),
];

$recentIncidents = Incident::latest('detected_at')
    ->take(5)
    ->get();

return view('dashboard', compact(
    'totalIncidents',
    'criticalIncidents',
    'highRiskIncidents',
    'resolvedIncidents',
    'overdueIncidents',
    'riskDistribution',
    'statusDistribution',
    'complianceDistribution',
    'recentIncidents'
));
}

public function show(Incident $incident)
{
    $complianceControls = ComplianceControl::orderBy('framework')
        ->orderBy('control_code')
        ->get();

    return view('incidents.show', compact(
        'incident',
        'complianceControls'
    ));
}
    public function update(Request $request, Incident $incident)
    {
$validated = $request->validate([
    'probability' => 'required|in:Faible,Moyenne,Élevée,Très élevée',
    'impact' => 'required|in:Faible,Moyen,Élevé,Critique',
    'status' => 'required|in:new,in_progress,resolved,closed',
    'comment' => 'nullable|string|max:2000',

    'action_corrective' => 'nullable|string|max:5000',
    'responsable' => 'nullable|string|max:255',
    'due_date' => 'nullable|date',
'compliance_status' => 'nullable|in:Conforme,Partiellement conforme,Non conforme',
'compliance_control' => 'nullable|integer',
'compliance_comment' => 'nullable|string|max:5000',
]);
        $scores = [
            'Faible' => 1,
            'Moyenne' => 2,
            'Élevée' => 3,
            'Très élevée' => 4,
            'Moyen' => 2,
            'Critique' => 4,
        ];

        $score = $scores[$validated['probability']]
               * $scores[$validated['impact']];

        if ($score >= 12) {
            $risk = 'Critique';
        } elseif ($score >= 8) {
            $risk = 'Élevé';
        } elseif ($score >= 4) {
            $risk = 'Moyen';
        } else {
            $risk = 'Faible';
        }
\Log::info('DEBUG COMPLIANCE', [
    'validated' => $validated,
    'compliance_calculee' => $validated['compliance_status'] ?? 'À évaluer',
]);

$incident->update([
    ...$validated,
    'compliance' => $validated['compliance_status'] ?? 'À évaluer',
    'risk_level' => $risk,
]);
        return redirect()
            ->route('incidents.show', $incident)
            ->with('success', 'Évaluation GRC mise à jour.');
    }
}

