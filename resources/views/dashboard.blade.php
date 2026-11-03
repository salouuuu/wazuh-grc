<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">

Dashboard GRC
        </h2>
    </x-slot>

    <div class="py-6">

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Titre --}}
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900">
                    Vue d'ensemble GRC
                </h1>

                <p class="text-gray-500 mt-1">
                    Synthèse des incidents de sécurité et de leur traitement.
                    {{ $totalAlerts }} alerte(s) Wazuh reçue(s), regroupée(s) en {{ $totalIncidents }} incident(s).
                </p>
            </div>


{{-- Indicateurs clés --}}
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">

    <a href="{{ route('incidents.index') }}" class="bg-white rounded-lg shadow-sm p-4 hover:ring-2 hover:ring-blue-200">
        <p class="text-sm text-gray-500">À traiter</p>
        <p class="text-3xl font-bold text-gray-900 mt-1">{{ $openIncidents }}</p>
        <p class="text-xs text-gray-400 mt-1">Nouveaux + en cours</p>
    </a>

    <a href="{{ route('incidents.index', ['status' => 'new']) }}" class="bg-white rounded-lg shadow-sm p-4 hover:ring-2 hover:ring-blue-200">
        <p class="text-sm text-gray-500">À trier</p>
        <p class="text-3xl font-bold text-blue-600 mt-1">{{ $toTriage }}</p>
        <p class="text-xs text-gray-400 mt-1">Statut « Nouveau »</p>
    </a>

    <a href="{{ route('incidents.index', ['risk_level' => 'Critique']) }}" class="bg-white rounded-lg shadow-sm p-4 hover:ring-2 hover:ring-blue-200">
        <p class="text-sm text-gray-500">Critiques ouverts</p>
        <p class="text-3xl font-bold text-red-600 mt-1">{{ $criticalIncidents }}</p>
        <p class="text-xs text-gray-400 mt-1">+ {{ $highRiskIncidents }} élevé(s)</p>
    </a>

    <div class="bg-white rounded-lg shadow-sm p-4">
        <p class="text-sm text-gray-500">En retard</p>
        <p class="text-3xl font-bold {{ $overdueIncidents ? 'text-red-600' : 'text-gray-900' }} mt-1">{{ $overdueIncidents }}</p>
        <p class="text-xs text-gray-400 mt-1">Échéance dépassée</p>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-4">
        <p class="text-sm text-gray-500">Résolus / clôturés</p>
        <p class="text-3xl font-bold text-green-600 mt-1">{{ $resolvedIncidents }}</p>
        <p class="text-xs text-gray-400 mt-1">sur {{ $totalIncidents }} incident(s)</p>
    </div>

</div>


{{-- Répartition des risques --}}
<div class="mt-6 bg-white rounded-lg shadow-sm p-6">

    <h3 class="text-lg font-semibold text-gray-900 mb-4">
        Répartition des risques
        <span class="text-sm font-normal text-gray-500">(incidents à traiter)</span>
    </h3>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

        {{-- Faible --}}
        <div class="border rounded-lg p-4 text-center">
            <p class="text-sm text-gray-500">
                Faible
            </p>

            <p class="text-3xl font-bold text-green-600 mt-2">
                {{ $riskDistribution['Faible'] }}
            </p>
        </div>

        {{-- Moyen --}}
        <div class="border rounded-lg p-4 text-center">
            <p class="text-sm text-gray-500">
                Moyen
            </p>

            <p class="text-3xl font-bold text-yellow-500 mt-2">
                {{ $riskDistribution['Moyen'] }}
            </p>
        </div>

        {{-- Élevé --}}
        <div class="border rounded-lg p-4 text-center">
            <p class="text-sm text-gray-500">
                Élevé
            </p>

            <p class="text-3xl font-bold text-orange-500 mt-2">
                {{ $riskDistribution['Élevé'] }}
            </p>
        </div>

        {{-- Critique --}}
        <div class="border rounded-lg p-4 text-center">
            <p class="text-sm text-gray-500">
                Critique
            </p>

            <p class="text-3xl font-bold text-red-600 mt-2">
                {{ $riskDistribution['Critique'] }}
            </p>
        </div>

    </div>

</div>


{{-- Alertes reçues sur 30 jours --}}
<div class="mt-6 bg-white rounded-lg shadow-sm p-6">

    <h3 class="text-lg font-semibold text-gray-900 mb-4">
        Alertes Wazuh reçues (30 derniers jours)
    </h3>

    <div class="relative" style="height: 220px;">
        <canvas id="alertsChart"></canvas>
    </div>

</div>


{{-- Graphiques --}}
<div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">

    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            Analyse des risques
        </h3>
        <div class="relative mx-auto" style="width: 260px; height: 260px;">
            <canvas id="riskChart"></canvas>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            État des incidents
        </h3>
        <div class="relative mx-auto" style="width: 260px; height: 260px;">
            <canvas id="statusChart"></canvas>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            État de la conformité
        </h3>
        <div class="relative mx-auto" style="width: 260px; height: 260px;">
            <canvas id="complianceChart"></canvas>
        </div>
    </div>

</div>


{{-- Origine des incidents --}}
<div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">

    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            Incidents à traiter par catégorie
        </h3>
        @forelse($categoryDistribution as $category => $total)
            <a href="{{ route('incidents.index', ['category' => $category]) }}" class="flex justify-between py-2 border-b last:border-0 hover:text-blue-600">
                <span>{{ $category }}</span>
                <span class="font-semibold">{{ $total }}</span>
            </a>
        @empty
            <p class="text-gray-500">Aucun incident à traiter.</p>
        @endforelse
        <p class="text-xs text-gray-400 mt-3">
            « Conformité (SCA) » : contrôles CIS en échec détectés par les scans Wazuh ;
            « Sécurité » : événements (authentification, sudo, comptes…).
        </p>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            Incidents à traiter par agent
        </h3>
        @forelse($agentDistribution as $agent => $total)
            <a href="{{ route('incidents.index', ['agent_name' => $agent]) }}" class="flex justify-between py-2 border-b last:border-0 hover:text-blue-600">
                <span>{{ $agent ?: '—' }}</span>
                <span class="font-semibold">{{ $total }}</span>
            </a>
        @empty
            <p class="text-gray-500">Aucun incident à traiter.</p>
        @endforelse
    </div>

</div>


{{-- Incidents récurrents --}}
@if($topRecurring->isNotEmpty())
<div class="mt-6 bg-white rounded-lg shadow-sm p-6">

    <h3 class="text-lg font-semibold text-gray-900">
        Incidents les plus récurrents
    </h3>
    <p class="text-sm text-gray-500 mt-1 mb-4">
        Une même alerte répétée est regroupée dans un seul incident.
    </p>

    <table class="min-w-full divide-y divide-gray-200">
        <tbody class="divide-y divide-gray-200">
            @foreach($topRecurring as $incident)
                <tr>
                    <td class="py-3 pr-4">
                        <a href="{{ route('incidents.show', $incident) }}" class="text-blue-600 hover:text-blue-800">
                            {{ $incident->title }}
                        </a>
                        <div class="text-sm text-gray-500">Règle #{{ $incident->rule_id }} · {{ $incident->agent_name }}</div>
                    </td>
                    <td class="py-3 pr-4 whitespace-nowrap font-semibold">× {{ $incident->occurrences }}</td>
                    <td class="py-3 pr-4 whitespace-nowrap text-sm">{{ $incident->statusLabel() }}</td>
                    <td class="py-3 whitespace-nowrap text-sm text-gray-500">
                        dernière : {{ ($incident->last_seen_at ?? $incident->detected_at)?->format('d/m/Y H:i') }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

</div>
@endif


{{-- Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    const doughnutOptions = {
        responsive: true,
        maintainAspectRatio: false,

        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    };

    const alertsData = @json($alertsPerDay);

    new Chart(document.getElementById('alertsChart'), {
        type: 'bar',

        data: {
            labels: Object.keys(alertsData),

            datasets: [{
                label: 'Alertes',
                data: Object.values(alertsData),
                backgroundColor: '#3b82f6',
                borderRadius: 3
            }]
        },

        options: {
            responsive: true,
            maintainAspectRatio: false,

            plugins: {
                legend: {
                    display: false
                }
            },

            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });

    const riskData = @json($riskDistribution);

    new Chart(document.getElementById('riskChart'), {
        type: 'doughnut',

        data: {
            labels: Object.keys(riskData),

            datasets: [{
                data: Object.values(riskData),

                backgroundColor: [
                    '#22c55e',
                    '#eab308',
                    '#f97316',
                    '#ef4444'
                ],

                borderWidth: 2
            }]
        },

        options: doughnutOptions
    });

    const statusData = @json($statusDistribution);

    new Chart(document.getElementById('statusChart'), {

        type: 'doughnut',

        data: {
            labels: Object.keys(statusData),

            datasets: [{
                data: Object.values(statusData),

                backgroundColor: [
                    '#3b82f6',
                    '#f59e0b',
                    '#22c55e',
                    '#6b7280',
                    '#d1d5db'
                ],

                borderWidth: 2
            }]
        },

        options: doughnutOptions
    });

    const complianceData = @json($complianceDistribution);

    new Chart(document.getElementById('complianceChart'), {

        type: 'doughnut',

        data: {
            labels: Object.keys(complianceData),

            datasets: [{
                data: Object.values(complianceData),

                backgroundColor: [
                    '#22c55e',
                    '#eab308',
                    '#ef4444',
                    '#9ca3af'
                ],

                borderWidth: 2
            }]
        },

        options: doughnutOptions
    });
</script>


{{-- Incidents récents --}}
<div class="mt-6 bg-white rounded-lg shadow-sm p-6">

    <div class="flex justify-between items-center mb-4">

        <div>
            <h3 class="text-lg font-semibold text-gray-900">
                Incidents récents à traiter
            </h3>

            <p class="text-sm text-gray-500 mt-1">
                Les dernières alertes reçues depuis Wazuh (hors incidents clos).
            </p>
        </div>

        <a href="{{ route('incidents.index') }}"
           class="text-sm text-blue-600 hover:text-blue-800">
            Voir tous les incidents →
        </a>

    </div>


    <div class="overflow-x-auto">

        <table class="min-w-full divide-y divide-gray-200">

            <thead class="bg-gray-50">

                <tr>

                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">
                        Incident
                    </th>

                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">
                        Sévérité
                    </th>

                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">
                        Risque
                    </th>

                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">
                        Statut
                    </th>

                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">
                        Dernière alerte
                    </th>

                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">
                        Action
                    </th>

                </tr>

            </thead>


            <tbody class="bg-white divide-y divide-gray-200">

                @forelse($recentIncidents as $incident)

                    <tr class="hover:bg-gray-100 transition-colors">

                        <td class="px-4 py-4">

                            <div class="font-medium text-gray-900">
                                {{ $incident->title }}
                                @if($incident->occurrences > 1)
                                    <span class="ml-1 px-2 py-0.5 text-xs rounded-full bg-gray-200 text-gray-700">× {{ $incident->occurrences }}</span>
                                @endif
                            </div>

                            <div class="text-sm text-gray-500">
                                Règle #{{ $incident->rule_id }}
                            </div>

                        </td>


                        <td class="px-4 py-4">

                            <span class="font-semibold">
                                {{ $incident->severity }}
                            </span>

                        </td>


                        <td class="px-4 py-4">

                            <span class="font-semibold">
                                {{ $incident->risk_level ?? '—' }}
                            </span>

                        </td>


                        <td class="px-4 py-4">

                            {{ $incident->statusLabel() }}

                        </td>


                        <td class="px-4 py-4 text-sm text-gray-500">

                            {{ ($incident->last_seen_at ?? $incident->detected_at)?->format('d/m/Y H:i') ?? '—' }}

                        </td>


                        <td class="px-4 py-4">

                            <a href="{{ route('incidents.show', $incident) }}"
                               class="text-blue-600 hover:text-blue-800 text-sm">

                                Voir

                            </a>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="6"
                            class="px-4 py-8 text-center text-gray-500">

                            Aucun incident à traiter.

                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>



            {{-- Accès aux incidents --}}
            <div class="mt-6 bg-white rounded-lg shadow-sm p-6">

                <div class="flex justify-between items-center">

                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">
                            Gestion des incidents
                        </h3>

                        <p class="text-sm text-gray-500 mt-1">
                            Consulter et traiter les alertes provenant de Wazuh.
                        </p>
                    </div>

                    <a href="{{ route('incidents.index') }}"
                       class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">

                        Voir les incidents

                    </a>

                </div>

            </div>

        </div>

    </div>

</x-app-layout>
