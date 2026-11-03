

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

        @if(!empty($search) || array_filter($filters) || $showDuplicates)
            <a
                href="{{ route('incidents.index') }}"
                class="px-5 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300"
            >
                Réinitialiser
            </a>
        @endif
    </div>

    {{-- Filtres --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mt-3 text-sm">
        <select name="status" onchange="this.form.submit()" class="rounded-lg border-gray-300">
            <option value="all" @selected($statusFilter === 'all')>Tous les statuts</option>
            @foreach(\App\Models\Incident::STATUSES as $value => $label)
                <option value="{{ $value }}" @selected($statusFilter === $value)>{{ $label }}</option>
            @endforeach
        </select>

        <select name="risk_level" onchange="this.form.submit()" class="rounded-lg border-gray-300">
            <option value="">Tous les risques</option>
            @foreach(\App\Services\RiskCalculator::RISK_LEVELS as $level)
                <option value="{{ $level }}" @selected(($filters['risk_level'] ?? '') === $level)>{{ $level }}</option>
            @endforeach
        </select>

        <select name="category" onchange="this.form.submit()" class="rounded-lg border-gray-300">
            <option value="">Toutes les catégories</option>
            @foreach([\App\Models\Incident::CATEGORY_SECURITY, \App\Models\Incident::CATEGORY_COMPLIANCE] as $category)
                <option value="{{ $category }}" @selected(($filters['category'] ?? '') === $category)>{{ $category }}</option>
            @endforeach
        </select>

        <select name="agent_name" onchange="this.form.submit()" class="rounded-lg border-gray-300">
            <option value="">Tous les agents</option>
            @foreach($agents as $agent)
                <option value="{{ $agent }}" @selected(($filters['agent_name'] ?? '') === $agent)>{{ $agent }}</option>
            @endforeach
        </select>

        <label class="flex items-center gap-2 text-gray-600">
            <input type="checkbox" name="duplicates" value="1" onchange="this.form.submit()" @checked($showDuplicates) class="rounded border-gray-300">
            Afficher les doublons regroupés
        </label>
    </div>
</form>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-100 text-green-700 rounded">
        {{ session('success') }}
    </div>
@endif

@if($errors->any())
    <div class="mb-4 p-4 bg-red-100 text-red-700 rounded">
        <ul class="list-disc ml-5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- Traitement en masse (les cases à cocher du tableau sont liées à ce formulaire) --}}
<form id="bulk-form" method="POST" action="{{ route('incidents.bulk') }}"
      class="mb-4 flex flex-wrap items-center gap-3 p-3 bg-gray-50 rounded-lg text-sm">
    @csrf
    <span class="text-gray-600">Pour la sélection :</span>
    <select name="status" class="rounded-lg border-gray-300 text-sm">
        @foreach(\App\Models\Incident::STATUSES as $value => $label)
            <option value="{{ $value }}" @selected($value === 'in_progress')>{{ $label }}</option>
        @endforeach
    </select>
    <button type="submit" class="px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-800">
        Appliquer
    </button>
</form>


                    <div class="overflow-x-auto">

                        <table class="min-w-full divide-y divide-gray-200">

                            <thead class="bg-gray-50">

                                <tr>

                                    <th class="px-4 py-3">
                                        <input type="checkbox" class="rounded border-gray-300" title="Tout sélectionner"
                                               onclick="document.querySelectorAll('.bulk-check').forEach(c => c.checked = this.checked)">
                                    </th>

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
                                        Dernière alerte
                                    </th>

                                </tr>

                            </thead>


                            <tbody class="bg-white divide-y divide-gray-200">

                                @forelse($incidents as $incident)

                                    <tr class="incident-row {{ $incident->duplicate_of_id ? 'text-gray-400' : '' }}">

                                        <td class="px-4 py-4">
                                            <input type="checkbox" name="ids[]" value="{{ $incident->id }}" form="bulk-form"
                                                   class="bulk-check rounded border-gray-300">
                                        </td>

                                        {{-- Incident --}}
                                        <td class="px-4 py-4">

                                            <div class="font-medium text-gray-900">

                                                <a href="{{ route('incidents.show', $incident) }}"
                                                   class="incident-link text-blue-600">

                                                    {{ $incident->title }}

                                                </a>

                                            </div>

                                            <div class="text-sm text-gray-500">
                                                Règle #{{ $incident->rule_id }} · {{ $incident->agent_name ?? '—' }}
                                                @if($incident->category === \App\Models\Incident::CATEGORY_COMPLIANCE)
                                                    · <span class="text-indigo-600">SCA</span>
                                                @endif
                                                @if($incident->occurrences > 1)
                                                    · <span class="px-2 py-0.5 text-xs rounded-full bg-gray-200 text-gray-700"
                                                            title="Alerte répétée {{ $incident->occurrences }} fois">× {{ $incident->occurrences }}</span>
                                                @endif
                                                @if($incident->duplicate_of_id)
                                                    · doublon de <a href="{{ route('incidents.show', $incident->duplicate_of_id) }}" class="text-blue-600">#{{ $incident->duplicate_of_id }}</a>
                                                @endif
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

{{ $incident->complianceControl ? $incident->complianceControl->framework . ' - ' . $incident->complianceControl->control_code : ($incident->compliance_status ?? 'À évaluer') }}
                                        </td>


                                        {{-- Statut --}}
                                        <td class="px-4 py-4">

                                            @php
                                                $statusColors = [
                                                    'new' => 'bg-blue-100 text-blue-800',
                                                    'in_progress' => 'bg-yellow-100 text-yellow-800',
                                                    'resolved' => 'bg-green-100 text-green-800',
                                                    'closed' => 'bg-gray-200 text-gray-700',
                                                ];
                                            @endphp
                                            <span class="px-2 py-1 text-xs rounded whitespace-nowrap {{ $statusColors[$incident->status] ?? '' }}">
                                                {{ $incident->statusLabel() }}
                                            </span>

                                        </td>


                                        {{-- Date --}}
                                        <td class="px-4 py-4 text-sm text-gray-500">

                                            {{ ($incident->last_seen_at ?? $incident->detected_at)?->format('d/m/Y H:i') ?? '—' }}

                                        </td>

                                    </tr>


                                @empty

                                    <tr>

                                        <td colspan="9"
                                            class="px-4 py-8 text-center text-gray-500">

                                            Aucun incident détecté.

                                        </td>

                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>

                    <div class="mt-4">
                        {{ $incidents->links() }}
                    </div>

                </div>

            </div>

        </div>

    </div>

</x-app-layout>

