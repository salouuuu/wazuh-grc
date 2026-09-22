
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
                        </p>
                    </div>

                    <a href="{{ route('incidents.index') }}"
                       class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">
                        ← Retour
                    </a>

                </div>


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

                    </div>

                </div>


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

                                <select name="status"
                                        class="w-full border-gray-300 rounded">

                                    <option value="new"
                                        @selected($incident->status === 'new')>
                                        Nouveau
                                    </option>

                                    <option value="in_progress"
                                        @selected($incident->status === 'in_progress')>
                                        En cours
                                    </option>

                                    <option value="resolved"
                                        @selected($incident->status === 'resolved')>
                                        Résolu
                                    </option>

                                    <option value="closed"
                                        @selected($incident->status === 'closed')>
                                        Clôturé
                                    </option>

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

            <input type="text"
                   name="responsable"
                   value="{{ old('responsable', $incident->responsable) }}"
                   class="w-full border-gray-300 rounded"
                   placeholder="Ex : Administrateur système">

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



{{-- Matrice de risque --}}
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

                    <th class="border border-gray-300 p-3">
                        Faible
                    </th>

                    <th class="border border-gray-300 p-3">
                        Moyen
                    </th>

                    <th class="border border-gray-300 p-3">
                        Élevé
                    </th>

                    <th class="border border-gray-300 p-3">
                        Critique
                    </th>
                </tr>
            </thead>

            <tbody>

                {{-- Très élevée --}}
                <tr>

                    <th class="border border-gray-300 p-3 bg-gray-100">
                        Très élevée
                    </th>

                    <td class="border border-gray-300 p-3"
                        style="background-color: {{ $incident->probability === 'Très élevée' && $incident->impact === 'Faible' ? '#86efac' : '#bbf7d0' }};">
                        Moyen
                    </td>

                    <td class="border border-gray-300 p-3"
                        style="background-color: {{ $incident->probability === 'Très élevée' && $incident->impact === 'Moyen' ? '#fb923c' : '#fed7aa' }};">
                        Élevé
                    </td>

                    <td class="border border-gray-300 p-3"
                        style="background-color: {{ $incident->probability === 'Très élevée' && $incident->impact === 'Élevé' ? '#f87171' : '#fecaca' }};">
                        Critique
                    </td>

                    <td class="border border-gray-300 p-3"
                        style="background-color: {{ $incident->probability === 'Très élevée' && $incident->impact === 'Critique' ? '#f87171' : '#fecaca' }};">
                        Critique
                    </td>

                </tr>

                {{-- Élevée --}}
                <tr>

                    <th class="border border-gray-300 p-3 bg-gray-100">
                        Élevée
                    </th>

                    <td class="border border-gray-300 p-3"
                        style="background-color: {{ $incident->probability === 'Élevée' && $incident->impact === 'Faible' ? '#86efac' : '#fef08a' }};">
                        Moyen
                    </td>

                    <td class="border border-gray-300 p-3"
                        style="background-color: {{ $incident->probability === 'Élevée' && $incident->impact === 'Moyen' ? '#fb923c' : '#fed7aa' }};">
                        Élevé
                    </td>

                    <td class="border border-gray-300 p-3"
                        style="background-color: {{ $incident->probability === 'Élevée' && $incident->impact === 'Élevé' ? '#fb923c' : '#fed7aa' }};">
                        Élevé
                    </td>

                    <td class="border border-gray-300 p-3"
                        style="background-color: {{ $incident->probability === 'Élevée' && $incident->impact === 'Critique' ? '#f87171' : '#fecaca' }};">
                        Critique
                    </td>

                </tr>

                {{-- Moyenne --}}
                <tr>

                    <th class="border border-gray-300 p-3 bg-gray-100">
                        Moyenne
                    </th>

                    <td class="border border-gray-300 p-3"
                        style="background-color: {{ $incident->probability === 'Moyenne' && $incident->impact === 'Faible' ? '#4ade80' : '#bbf7d0' }};">
                        Faible
                    </td>

                    <td class="border border-gray-300 p-3"
                        style="background-color: {{ $incident->probability === 'Moyenne' && $incident->impact === 'Moyen' ? '#facc15' : '#fef08a' }};">
                        Moyen
                    </td>

                    <td class="border border-gray-300 p-3"
                        style="background-color: {{ $incident->probability === 'Moyenne' && $incident->impact === 'Élevé' ? '#fb923c' : '#fed7aa' }};">
                        Élevé
                    </td>

                    <td class="border border-gray-300 p-3"
                        style="background-color: {{ $incident->probability === 'Moyenne' && $incident->impact === 'Critique' ? '#fb923c' : '#fed7aa' }};">
                        Élevé
                    </td>

                </tr>

                {{-- Faible --}}
                <tr>

                    <th class="border border-gray-300 p-3 bg-gray-100">
                        Faible
                    </th>

                    <td class="border border-gray-300 p-3"
                        style="background-color: {{ $incident->probability === 'Faible' && $incident->impact === 'Faible' ? '#4ade80' : '#bbf7d0' }};">
                        Faible
                    </td>

                    <td class="border border-gray-300 p-3"
                        style="background-color: {{ $incident->probability === 'Faible' && $incident->impact === 'Moyen' ? '#4ade80' : '#bbf7d0' }};">
                        Faible
                    </td>

                    <td class="border border-gray-300 p-3"
                        style="background-color: {{ $incident->probability === 'Faible' && $incident->impact === 'Élevé' ? '#facc15' : '#fef08a' }};">
                        Moyen
                    </td>

                    <td class="border border-gray-300 p-3"
                        style="background-color: {{ $incident->probability === 'Faible' && $incident->impact === 'Critique' ? '#facc15' : '#fef08a' }};">
                        Moyen
                    </td>

                </tr>

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
                @selected($incident->compliance_control == $control->id)>

                {{ $control->framework }}
                - {{ $control->control_code }}
                : {{ $control->title }}

            </option>

        @endforeach

    </select>

</div>

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

