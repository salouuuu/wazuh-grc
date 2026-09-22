
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
                </p>
            </div>



{{-- Répartition des risques --}}
<div class="mt-6 bg-white rounded-lg shadow-sm p-6">

    <h3 class="text-lg font-semibold text-gray-900 mb-4">
        Répartition des risques
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


{{-- Graphique de répartition des risques --}}
<div class="mt-6 bg-white rounded-lg shadow-sm p-6">

    <h3 class="text-lg font-semibold text-gray-900 mb-4">
        Analyse des risques
    </h3>

<div class="relative mx-auto" style="width: 280px; height: 280px;">
    <canvas id="riskChart"></canvas>
</div>

</div>

{{-- Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
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
options: {
    responsive: true,
    maintainAspectRatio: false,

    plugins: {
        legend: {
            position: 'bottom'
        }
    }
}
    });
</script>


{{-- Graphique des statuts --}}
<div class="mt-6 bg-white rounded-lg shadow-sm p-6">

    <h3 class="text-lg font-semibold text-gray-900 mb-4">
        État des incidents
    </h3>

    <div class="relative mx-auto" style="width: 280px; height: 280px;">
        <canvas id="statusChart"></canvas>
    </div>

</div>

<script>
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
                    '#6b7280'
                ],

                borderWidth: 2
            }]
        },

        options: {
            responsive: true,
            maintainAspectRatio: false,

            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
</script>



{{-- Graphique de conformité --}}
<div class="mt-6 bg-white rounded-lg shadow-sm p-6">

    <h3 class="text-lg font-semibold text-gray-900 mb-4">
        État de la conformité
    </h3>

    <div class="relative mx-auto" style="width: 280px; height: 280px;">
        <canvas id="complianceChart"></canvas>
    </div>

</div>

<script>
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

        options: {
            responsive: true,
            maintainAspectRatio: false,

            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
</script>


{{-- Incidents récents --}}
<div class="mt-6 bg-white rounded-lg shadow-sm p-6">

    <div class="flex justify-between items-center mb-4">

        <div>
            <h3 class="text-lg font-semibold text-gray-900">
                Incidents récents
            </h3>

            <p class="text-sm text-gray-500 mt-1">
                Les dernières alertes reçues depuis Wazuh.
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
                        Date
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

                            {{ $incident->status }}

                        </td>


                        <td class="px-4 py-4 text-sm text-gray-500">

                            {{ $incident->detected_at?->format('d/m/Y H:i') ?? '—' }}

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

                            Aucun incident détecté.

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

