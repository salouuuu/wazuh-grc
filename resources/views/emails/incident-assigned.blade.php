
<h2>Nouvel incident GRC à traiter</h2>

<p>Bonjour {{ $responsable->name }},</p>

<p>Un incident de sécurité vous a été affecté.</p>

<ul>
    <li><strong>Numéro :</strong> #{{ $incident->id }}</li>
    <li><strong>Titre :</strong> {{ $incident->title }}</li>
    <li><strong>Description :</strong> {{ $incident->description ?? 'Non renseignée' }}</li>
    <li><strong>Niveau de risque :</strong> {{ $incident->risk_level ?? 'Non évalué' }}</li>
    <li><strong>Action corrective :</strong> {{ $incident->action_corrective ?? 'Non renseignée' }}</li>
    <li><strong>Date limite :</strong> {{ $incident->due_date ? \Carbon\Carbon::parse($incident->due_date)->format('d/m/Y') : 'Non définie' }}</li>
</ul>

<p>Merci de prendre en charge cet incident et de suivre les actions correctives nécessaires.</p>

<p>
    <a href="{{ route('incidents.show', $incident) }}">
        Consulter l'incident dans GRC
    </a>
</p>

<p>Notification automatique — GRC Wazuh</p>
