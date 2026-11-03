
<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Détail de l'incident
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white shadow-sm sm:rounded-lg p-6">

                {{-- En-tête --}}
                <div class="flex justify-between items-center mb-6">

                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">
                            {{ $incident->title }}
                        </h1>

                        <p class="text-sm text-gray-500 mt-1">
                            Incident #{{ $incident->id }}
                            · {{ $incident->statusLabel() }}
                            @if($incident->category)
                                · {{ $incident->category }}
                            @endif
                            @if($incident->occurrences > 1)
                                · <strong>alerte répétée {{ $incident->occurrences }} fois</strong>
                            @endif
                        </p>
                    </div>

                    <a href="{{ route('incidents.index') }}"
                       class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">
                        ← Retour
                    </a>

                </div>


                @if($incident->duplicateOf)
                    <div class="mb-6 p-4 bg-yellow-50 text-yellow-800 rounded">
                        Cette alerte est un doublon regroupé dans l'incident
                        <a href="{{ route('incidents.show', $incident->duplicateOf) }}" class="underline font-semibold">#{{ $incident->duplicateOf->id }}</a> :
                        traitez l'incident principal.
                    </div>
                @endif

                {{-- Informations Wazuh --}}
                <div class="border-t pt-6">

                    <h3 class="text-lg font-semibold mb-4">
                        Informations Wazuh
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>
                            <strong>Règle Wazuh :</strong>
                            {{ $incident->rule_id ?? '—' }}
                        </div>

                        <div>
                            <strong>Sévérité :</strong>
                            {{ $incident->severity ?? '—' }}
                        </div>

                        <div>
                            <strong>Agent :</strong>
                            {{ $incident->agent_name ?? '—' }}
                        </div>

                        <div>
                            <strong>Adresse IP :</strong>
                            {{ $incident->agent_ip ?? '—' }}
                        </div>

                        <div>
                            <strong>Utilisateur :</strong>
                            {{ $incident->source_user ?? '—' }}
                        </div>

                        <div>
                            <strong>Date de détection :</strong>
                            {{ $incident->detected_at?->format('d/m/Y H:i:s') ?? '—' }}
                        </div>

                        @if($incident->occurrences > 1)
                            <div>
                                <strong>Dernière occurrence :</strong>
                                {{ $incident->last_seen_at?->format('d/m/Y H:i:s') ?? '—' }}
                                ({{ $incident->occurrences }} alertes)
                            </div>
                        @endif

                        @if($groups = data_get($incident->wazuh_data, 'rule.groups'))
                            <div>
                                <strong>Groupes Wazuh :</strong>
                                {{ implode(', ', $groups) }}
                            </div>
                        @endif

                    </div>

                    {{-- Référentiels fournis nativement par la règle Wazuh --}}
                    @php
                        $frameworks = collect([
                            'ISO 27001:2013' => data_get($incident->wazuh_data, 'rule.iso_27001-2013'),
                            'PCI DSS' => data_get($incident->wazuh_data, 'rule.pci_dss'),
                            'RGPD' => data_get($incident->wazuh_data, 'rule.gdpr'),
                            'NIST 800-53' => data_get($incident->wazuh_data, 'rule.nist_800_53'),
                            'HIPAA' => data_get($incident->wazuh_data, 'rule.hipaa'),
                            'TSC (SOC 2)' => data_get($incident->wazuh_data, 'rule.tsc'),
                            'CIS' => data_get($incident->wazuh_data, 'rule.cis'),
                            'MITRE ATT&CK' => data_get($incident->wazuh_data, 'rule.mitre.technique') ?? data_get($incident->wazuh_data, 'rule.mitre_techniques'),
                        ])->filter();
                    @endphp

                    @if($frameworks->isNotEmpty())
                        <div class="mt-4">
                            <p class="text-sm font-medium text-gray-700 mb-2">Référentiels associés par Wazuh</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach($frameworks as $framework => $refs)
                                    <span class="px-2 py-1 text-xs rounded bg-indigo-50 text-indigo-700">
                                        {{ $framework }} : {{ implode(', ', (array) $refs) }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                </div>


                {{-- Contrôle SCA (CIS) --}}
                @if($check = data_get($incident->wazuh_data, 'data.sca.check'))
                    <div class="border-t mt-6 pt-6">

                        <h3 class="text-lg font-semibold mb-4">
                            Contrôle de configuration (SCA)
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div><strong>Politique :</strong> {{ data_get($incident->wazuh_data, 'data.sca.policy', '—') }}</div>
                            <div><strong>Contrôle :</strong> #{{ $check['id'] ?? '—' }} {{ $check['title'] ?? '' }}</div>
                            <div>
                                <strong>Résultat :</strong>
                                <span class="{{ ($check['result'] ?? '') === 'failed' ? 'text-red-600 font-semibold' : '' }}">{{ $check['result'] ?? '—' }}</span>
                                @isset($check['previous_result'])
                                    (avant : {{ $check['previous_result'] }})
                                @endisset
                            </div>
                        </div>

                        @isset($check['rationale'])
                            <p class="mt-4 text-sm text-gray-700"><strong>Pourquoi :</strong> {{ $check['rationale'] }}</p>
                        @endisset

                        @isset($check['remediation'])
                            <p class="mt-4 text-sm font-medium text-gray-700">Correction recommandée</p>
                            <p class="mt-1 text-sm bg-gray-100 p-4 rounded">{{ $check['remediation'] }}</p>
                        @endisset

                    </div>
                @endif



                {{-- Description --}}
                <div class="border-t mt-6 pt-6">

                    <h3 class="text-lg font-semibold mb-4">
                        Description
                    </h3>

                    <p class="text-gray-700">
                        {{ $incident->description ?? 'Aucune description disponible.' }}
                    </p>

                </div>


                {{-- Commande --}}
                @if($incident->command)

                    <div class="border-t mt-6 pt-6">

                        <h3 class="text-lg font-semibold mb-4">
                            Commande
                        </h3>

                        <code class="block bg-gray-100 p-4 rounded">
                            {{ $incident->command }}
                        </code>

                    </div>

                @endif


                {{-- Évaluation GRC --}}
                <div class="border-t mt-6 pt-6">

                    <h3 class="text-lg font-semibold mb-4">
                        Évaluation GRC
                    </h3>


                    {{-- Message de succès --}}
                    @if(session('success'))

                        <div class="mb-4 p-4 bg-green-100 text-green-700 rounded">
                            {{ session('success') }}
                        </div>

                    @endif


                    {{-- Erreurs --}}
                    @if($errors->any())

                        <div class="mb-4 p-4 bg-red-100 text-red-700 rounded">

                            <ul class="list-disc ml-5">

                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach

                            </ul>

                        </div>

                    @endif


                    {{-- Formulaire --}}
                    <form method="POST"
                          action="{{ route('incidents.update', $incident) }}">

                        @csrf
                        @method('PUT')


                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">


                            {{-- Probabilité --}}
                            <div>

                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Probabilité
                                </label>

                                <select name="probability"
                                        class="w-full border-gray-300 rounded">

                                    @foreach(['Faible', 'Moyenne', 'Élevée', 'Très élevée'] as $value)

                                        <option value="{{ $value }}"
                                            @selected($incident->probability === $value)>
                                            {{ $value }}
                                        </option>

                                    @endforeach

                                </select>

                            </div>


                            {{-- Impact --}}
                            <div>

                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Impact
                                </label>

                                <select name="impact"
                                        class="w-full border-gray-300 rounded">

                                    @foreach(['Faible', 'Moyen', 'Élevé', 'Critique'] as $value)

                                        <option value="{{ $value }}"
                                            @selected($incident->impact === $value)>
                                            {{ $value }}
                                        </option>

                                    @endforeach

                                </select>

                            </div>


                            {{-- Statut --}}
                            <div>

                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Statut
                                </label>

                                <select name="status" id="status-select"
                                        class="w-full border-gray-300 rounded">

                                    @foreach(\App\Models\Incident::STATUSES as $value => $label)
                                        <option value="{{ $value }}"
                                            @selected(old('status', $incident->status) === $value)>
                                            {{ $label }}
                                        </option>
                                    @endforeach

                                </select>

                            </div>

                        </div>

{{-- Plan de traitement --}}
<div class="border-t mt-6 pt-6">

    <h3 class="text-lg font-semibold mb-4">
        Plan de traitement
    </h3>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

        {{-- Action corrective --}}
        <div class="md:col-span-2">

            <label class="block text-sm font-medium text-gray-700 mb-2">
                Action corrective
            </label>

            <textarea name="action_corrective"
                      rows="4"
                      class="w-full border-gray-300 rounded"
                      placeholder="Décrire les mesures à mettre en place...">{{ old('action_corrective', $incident->action_corrective) }}</textarea>

        </div>

        {{-- Responsable --}}
        <div>

            <label class="block text-sm font-medium text-gray-700 mb-2">
                Responsable
            </label>

<select name="responsable_user_id"
        class="w-full border-gray-300 rounded">

    <option value="">-- Sélectionner un responsable --</option>

    @foreach($users as $user)
        <option value="{{ $user->id }}"
            {{ old('responsable_user_id', $incident->responsable_user_id) == $user->id ? 'selected' : '' }}>
            {{ $user->name }}
        </option>
    @endforeach

</select>

            {{-- Renvoi volontaire au même responsable (nouvelle tâche, mise à jour) --}}
            <label class="mt-2 inline-flex items-center gap-2 text-sm text-gray-600">
                <input type="checkbox" name="resend_email" value="1" class="rounded border-gray-300">
                Renvoyer l'e-mail au responsable
            </label>
        </div>

        {{-- Échéance --}}
        <div>

            <label class="block text-sm font-medium text-gray-700 mb-2">
                Échéance
            </label>

            <input type="date"
                   name="due_date"
                   value="{{ old('due_date', $incident->due_date?->format('Y-m-d')) }}"
                   class="w-full border-gray-300 rounded">

        </div>

    </div>

</div>
             
{{-- État de l'échéance --}}
@if($incident->due_date)

    <div class="mt-4">

        <p class="text-sm font-medium text-gray-700 mb-2">
            État de l'échéance
        </p>

        @if(in_array($incident->status, ['resolved', 'closed']))

            <span class="inline-flex px-3 py-1 rounded-full text-sm font-semibold"
                  style="background-color: #bbf7d0; color: #166534;">
                ✓ Terminé
            </span>

@elseif($incident->due_date->lt(today()))
            <span class="inline-flex px-3 py-1 rounded-full text-sm font-semibold"
                  style="background-color: #fecaca; color: #991b1b;">
                ⚠ Échéance dépassée
            </span>

        @elseif($incident->due_date->diffInDays(now()) <= 3)

            <span class="inline-flex px-3 py-1 rounded-full text-sm font-semibold"
                  style="background-color: #fed7aa; color: #9a3412;">
                ⚠ Échéance proche
            </span>

        @else

            <span class="inline-flex px-3 py-1 rounded-full text-sm font-semibold"
                  style="background-color: #bbf7d0; color: #166534;">
                ✓ Dans les délais
            </span>

        @endif

    </div>

@endif               
{{-- Commentaire --}}
<div class="mt-4">

    <label class="block text-sm font-medium text-gray-700 mb-2">
        Commentaire / justification
    </label>

    <textarea name="comment"
              rows="4"
              class="w-full border-gray-300 rounded"
              placeholder="Justification de l'évaluation du risque...">{{ old('comment', $incident->comment) }}</textarea>

</div>



{{-- Matrice de risque (générée par App\Services\RiskCalculator) --}}
@php
    $riskColors = [
        'Faible' => ['#bbf7d0', '#4ade80'],
        'Moyen' => ['#fef08a', '#facc15'],
        'Élevé' => ['#fed7aa', '#fb923c'],
        'Critique' => ['#fecaca', '#f87171'],
    ];
@endphp
<div class="mt-6">

    <h3 class="text-lg font-semibold mb-4">
        Matrice de risque
    </h3>

    <div class="overflow-x-auto">

        <table class="min-w-full border border-gray-300 text-center">

            <thead>
                <tr class="bg-gray-100">
                    <th class="border border-gray-300 p-3">
                        Probabilité ↓ / Impact →
                    </th>

                    @foreach(array_keys(reset($riskMatrix)) as $impact)
                        <th class="border border-gray-300 p-3">
                            {{ $impact }}
                        </th>
                    @endforeach
                </tr>
            </thead>

            <tbody>

                @foreach($riskMatrix as $probability => $row)
                    <tr>

                        <th class="border border-gray-300 p-3 bg-gray-100">
                            {{ $probability }}
                        </th>

                        @foreach($row as $impact => $level)
                            @php
                                $isCurrent = $incident->probability === $probability && $incident->impact === $impact;
                            @endphp

                            <td class="border border-gray-300 p-3 {{ $isCurrent ? 'font-bold ring-2 ring-inset ring-gray-800' : '' }}"
                                style="background-color: {{ $riskColors[$level][$isCurrent ? 1 : 0] }};">
                                {{ $level }}
                            </td>
                        @endforeach

                    </tr>
                @endforeach

            </tbody>

        </table>

    </div>

</div>
{{-- Résultat de l'évaluation --}}
<div class="mt-4 p-4 bg-gray-50 rounded">

    <p class="text-sm text-gray-500">
        Niveau de risque calculé
    </p>

    <p class="text-xl font-bold mt-1">
        {{ $incident->risk_level ?? 'À évaluer' }}
    </p>

    @if($incident->probability && $incident->impact)

        <p class="text-sm text-gray-500 mt-1">
            Probabilité : {{ $incident->probability }}
            — Impact : {{ $incident->impact }}
        </p>

    @endif

</div>


                         {{-- Conformité --}}
<div class="border-t mt-6 pt-6">

    <h3 class="text-lg font-semibold mb-4">
        Conformité
    </h3>

    {{-- Contrôles suggérés : type d'alerte (suggestion automatique) + correspondance ISO 2013 → 2022 --}}
    @php
        $preselected = !$incident->compliance_control ? $suggestedControl?->control_code : null;

        // Intitulés : ceux de la liste de l'application, puis ceux de la suggestion automatique, puis la table ISO
        preg_match_all('/ISO 27001 - (A\.\d+\.\d+) : ([^,]+)/', (string) $incident->compliance, $groupTitles);
        $suggestionTitles = $complianceControls->where('framework', 'ISO 27001')->pluck('title', 'control_code')->all()
            + array_combine($groupTitles[1], array_map('trim', $groupTitles[2]))
            + config('iso27001.controls');
    @endphp

    @if(!empty($isoSuggestions))
        <div class="mb-4 p-4 bg-indigo-50 text-indigo-900 rounded text-sm">
            <p class="font-medium mb-2">Contrôles suggérés</p>
            <ul class="space-y-1">
                @foreach($isoSuggestions as $code => $sources)
                    <li>
                        <span class="inline-block w-4">{{ $code === $preselected ? '★' : '' }}</span>
                        <strong>{{ $code }}</strong>
                        {{ $suggestionTitles[$code] ?? '' }}
                        <span class="text-indigo-600">← {{ implode(', ', array_unique($sources)) }}</span>
                        @unless($complianceControls->contains('control_code', $code))
                            <span class="text-gray-500">(absent de la liste)</span>
                        @endunless
                    </li>
                @endforeach
            </ul>
            <p class="mt-2 text-xs text-indigo-700">
                @if($preselected)
                    ★ {{ $preselected }} est pré-sélectionné ci-dessous : à vous de valider ou de choisir un autre contrôle.
                @else
                    Suggestion seulement : le contrôle retenu reste votre choix.
                @endif
                <a href="{{ route('iso.mapping') }}" class="underline">Table de correspondance complète</a>
            </p>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

        {{-- Statut de conformité --}}
        <div>

            <label class="block text-sm font-medium text-gray-700 mb-2">
                Statut de conformité
            </label>

            <select name="compliance_status"
                    class="w-full border-gray-300 rounded">

                <option value="">
                    -- Sélectionner --
                </option>

                <option value="Conforme"
                    @selected($incident->compliance_status === 'Conforme')>
                    Conforme
                </option>

                <option value="Partiellement conforme"
                    @selected($incident->compliance_status === 'Partiellement conforme')>
                    Partiellement conforme
                </option>

                <option value="Non conforme"
                    @selected($incident->compliance_status === 'Non conforme')>
                    Non conforme
                </option>

            </select>

        </div>


        {{-- Contrôle --}}

<div>

    <label class="block text-sm font-medium text-gray-700 mb-2">
        Contrôle / Référentiel
    </label>

    <select name="compliance_control"
            class="w-full border-gray-300 rounded">

        <option value="">
            -- Sélectionner un contrôle --
        </option>

        @foreach($complianceControls as $control)

            <option value="{{ $control->id }}"
                @selected(($incident->compliance_control ?? $suggestedControl?->id) == $control->id)>

                {{ $control->framework }}
                - {{ $control->control_code }}
                : {{ $control->title }}
                @if(!$incident->compliance_control && $suggestedControl?->id === $control->id)
                    (suggéré)
                @endif

            </option>

        @endforeach

    </select>

</div>

        </div>



    {{-- Justification conformité --}}
    <div class="mt-4">

        <label class="block text-sm font-medium text-gray-700 mb-2">
            Justification de conformité
        </label>

        <textarea name="compliance_comment"
                  rows="4"
                  class="w-full border-gray-300 rounded"
                  placeholder="Justifier l'évaluation de conformité...">{{ old('compliance_comment', $incident->compliance_comment) }}</textarea>

    </div>

</div>  

                        {{-- Bouton --}}
                        <div class="mt-6">

                            <button type="submit"
                                    class="px-5 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                Enregistrer l'évaluation
                            </button>

                        </div>

                    </form>

                </div>


                {{-- Suivi : état initial → ce qui a été fait → état final (lecture seule) --}}
                @php
                    $initial = $incident->initial_state ?? [];
                    $final = $incident->stateSnapshot();
                    $sca = $incident->scaSummary();
                    $finalScaResult = $sca['result'] ?? $final['sca_result'];
                    $day = fn ($date) => $date ? \Illuminate\Support\Carbon::parse($date)->format('d/m/Y') : '—';
                    $trackedRows = [
                        'Conformité' => 'compliance_status',
                        'Probabilité / Impact' => null,
                        'Risque' => 'risk_level',
                    ];
                @endphp
                @if($initial)
                    <div class="border-t mt-6 pt-6">

                        <h3 class="text-lg font-semibold mb-4">
                            Suivi de l'incident
                            @if($checkId = data_get($incident->wazuh_data, 'data.sca.check.id'))
                                (contrôle CIS {{ $checkId }})
                            @endif
                        </h3>

                        <div class="overflow-x-auto">
                            <table class="min-w-full border border-gray-300 text-sm">
                                <thead>
                                    <tr class="bg-gray-100">
                                        <th class="border border-gray-300 p-2"></th>
                                        <th class="border border-gray-300 p-2 text-left">État initial</th>
                                        <th class="border border-gray-300 p-2 text-left">Ce qui a été fait</th>
                                        <th class="border border-gray-300 p-2 text-left">État final</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <th class="border border-gray-300 p-2 bg-gray-100 text-left">Date</th>
                                        <td class="border border-gray-300 p-2">{{ $day($initial['date'] ?? null) }} (détection)</td>
                                        <td class="border border-gray-300 p-2 align-top" rowspan="{{ $finalScaResult ? 5 : 4 }}">
                                            {{ $incident->action_corrective ?: 'Aucune action renseignée (champ « Action corrective »).' }}
                                            <p class="mt-2 text-gray-500">Statut : {{ $incident->statusLabel() }}</p>
                                        </td>
                                        <td class="border border-gray-300 p-2">
                                            @if(($sca['result'] ?? null) === 'passed')
                                                {{ $day($sca['since']) }} (vérifié par Wazuh)
                                            @else
                                                {{ $day($incident->updated_at) }} (dernière mise à jour)
                                            @endif
                                        </td>
                                    </tr>
                                    @if($finalScaResult)
                                        <tr>
                                            <th class="border border-gray-300 p-2 bg-gray-100 text-left">Résultat CIS</th>
                                            <td class="border border-gray-300 p-2">{{ $initial['sca_result'] ?? '—' }}</td>
                                            <td class="border border-gray-300 p-2 {{ $finalScaResult === 'passed' ? 'text-green-700 font-semibold' : 'text-red-600 font-semibold' }}">
                                                {{ $finalScaResult }}{{ $finalScaResult === 'passed' ? ' ✔' : '' }}
                                            </td>
                                        </tr>
                                    @endif
                                    @foreach($trackedRows as $label => $key)
                                        @php
                                            $before = $key ? ($initial[$key] ?? null) : ($initial['probability'] ?? '—').' / '.($initial['impact'] ?? '—');
                                            $after = $key ? $final[$key] : ($final['probability'] ?? '—').' / '.($final['impact'] ?? '—');
                                        @endphp
                                        <tr>
                                            <th class="border border-gray-300 p-2 bg-gray-100 text-left">{{ $label }}</th>
                                            <td class="border border-gray-300 p-2">{{ $before ?? '—' }}</td>
                                            <td class="border border-gray-300 p-2 {{ $before !== $after ? 'font-semibold' : '' }}">{{ $after ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if($sca)
                            <p class="mt-2 text-sm text-gray-500">
                                Historique Wazuh : première détection le {{ $day($sca['first_seen']) }}
                                @if($sca['corrections'] || $sca['regressions'])
                                    — {{ $sca['corrections'] }} correction(s), {{ $sca['regressions'] }} régression(s)
                                @endif
                            </p>

                            @if($sca['result'] === 'failed' && in_array($incident->status, ['resolved', 'closed']))
                                <p class="mt-2 p-3 bg-red-50 text-red-700 rounded text-sm">
                                    ⚠ Régression : Wazuh voit de nouveau ce contrôle en échec depuis le {{ $day($sca['since']) }}.
                                </p>
                            @elseif($sca['result'] === 'passed' && in_array($incident->status, ['new', 'in_progress']))
                                <p class="mt-2 p-3 bg-green-50 text-green-700 rounded text-sm">
                                    ✔ Wazuh confirme la correction depuis le {{ $day($sca['since']) }} : l'incident peut passer à « Résolu ».
                                </p>
                            @endif
                        @endif

                    </div>
                @endif


                {{-- Historique des alertes regroupées --}}
                @if($incident->occurrenceLog->count() > 1)
                    <div class="border-t mt-6 pt-6">

                        <h3 class="text-lg font-semibold mb-4">
                            Historique des alertes ({{ $incident->occurrenceLog->count() }})
                        </h3>

                        <ul class="text-sm text-gray-700 space-y-1 max-h-64 overflow-y-auto">
                            @foreach($incident->occurrenceLog as $occurrence)
                                <li>
                                    {{ $occurrence->detected_at?->format('d/m/Y H:i:s') ?? '—' }}
                                    <code class="text-xs text-gray-500">{{ $occurrence->wazuh_alert_id }}</code>
                                </li>
                            @endforeach
                        </ul>

                    </div>
                @endif

                {{-- Identifiant Wazuh --}}
                <div class="border-t mt-6 pt-6">

                    <p class="text-sm text-gray-500">
                        Identifiant de l'alerte Wazuh
                    </p>

                    <code class="text-sm">
                        {{ $incident->wazuh_alert_id }}
                    </code>

                </div>

            </div>

        </div>
    </div>

</x-app-layout>

