

<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Incidents GRC') }}
        </h2>
    </x-slot>

    <style>
        /* Hover uniquement sur la ligne */
        .incident-row {
            transition: background-color 0.2s ease;
        }

        .incident-row:hover {
            background-color: #e5e7eb;
        }

        /* Aucun effet hover sur le lien */
        .incident-link {
            text-decoration: none;
        }

        .incident-link:hover {
            text-decoration: none;
            color: #2563eb;
        }
    </style>

    <div class="py-6">

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">

                <div class="p-6">

                    <h3 class="text-lg font-semibold mb-6">
                        Alertes Wazuh
                    </h3>
<form method="GET" action="{{ route('incidents.index') }}" class="mb-6">
    <div class="flex gap-3">
        <input
            type="text"
            name="search"
            value="{{ $search ?? '' }}"
            placeholder="Rechercher un incident..."
            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
        >

        <button
            type="submit"
            class="px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
        >
            Rechercher
        </button>

        @if(!empty($search))
            <a
                href="{{ route('incidents.index') }}"
                class="px-5 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300"
            >
                Réinitialiser
            </a>
        @endif
    </div>
</form>


                    <div class="overflow-x-auto">

                        <table class="min-w-full divide-y divide-gray-200">

                            <thead class="bg-gray-50">

                                <tr>

                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        Incident
                                    </th>

                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        Sévérité
                                    </th>

                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        Probabilité
                                    </th>

                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        Impact
                                    </th>

                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        Risque
                                    </th>

                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        Conformité
                                    </th>

                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        Statut
                                    </th>

                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        Date
                                    </th>

                                </tr>

                            </thead>


                            <tbody class="bg-white divide-y divide-gray-200">

                                @forelse($incidents as $incident)

                                    <tr class="incident-row">

                                        {{-- Incident --}}
                                        <td class="px-4 py-4">

                                            <div class="font-medium text-gray-900">

                                                <a href="{{ route('incidents.show', $incident) }}"
                                                   class="incident-link text-blue-600">

                                                    {{ $incident->title }}

                                                </a>

                                            </div>

                                            <div class="text-sm text-gray-500">
                                                Règle #{{ $incident->rule_id }}
                                            </div>

                                        </td>


                                        {{-- Sévérité --}}
                                        <td class="px-4 py-4">

                                            <span class="font-semibold">
                                                {{ $incident->severity }}
                                            </span>

                                        </td>


                                        {{-- Probabilité --}}
                                        <td class="px-4 py-4">

                                            {{ $incident->probability ?? '—' }}

                                        </td>


                                        {{-- Impact --}}
                                        <td class="px-4 py-4">

                                            {{ $incident->impact ?? '—' }}

                                        </td>


                                        {{-- Risque --}}
                                        <td class="px-4 py-4">

                                            <span class="font-semibold">
                                                {{ $incident->risk_level ?? '—' }}
                                            </span>

                                        </td>


                                        {{-- Conformité --}}
                                        <td class="px-4 py-4 text-sm">

{{ $incident->complianceControl ? $incident->complianceControl->framework . ' - ' . $incident->complianceControl->control_code : 'À évaluer' }}
                                        </td>


                                        {{-- Statut --}}
                                        <td class="px-4 py-4">

                                            <span class="px-2 py-1 text-xs rounded">
                                                {{ $incident->status }}
                                            </span>

                                        </td>


                                        {{-- Date --}}
                                        <td class="px-4 py-4 text-sm text-gray-500">

                                            {{ $incident->detected_at?->format('d/m/Y H:i') ?? '—' }}

                                        </td>

                                    </tr>


                                @empty

                                    <tr>

                                        <td colspan="8"
                                            class="px-4 py-8 text-center text-gray-500">

                                            Aucun incident détecté.

                                        </td>

                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        </div>

    </div>

</x-app-layout>

