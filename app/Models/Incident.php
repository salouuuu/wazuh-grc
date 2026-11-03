<?php

namespace App\Models;
use App\Models\User;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Models\ComplianceControl;

class Incident extends Model
{
    /** Libellés des statuts (clé stockée en base => libellé affiché). */
    public const STATUSES = [
        'new' => 'Nouveau',
        'in_progress' => 'En cours',
        'resolved' => 'Résolu',
        'closed' => 'Clôturé',
    ];

    /** Statuts pour lesquels une nouvelle alerte identique est rattachée à l'incident. */
    public const GROUPABLE_STATUSES = ['new', 'in_progress'];

    public const CATEGORY_SECURITY = 'Sécurité';
    public const CATEGORY_COMPLIANCE = 'Conformité (SCA)';

    protected $fillable = [
        'wazuh_alert_id',
        'rule_id',
        'title',
        'description',
        'severity',
        'agent_id',
        'agent_name',
        'agent_ip',
        'source_user',
        'command',
        'status',
        'detected_at',
        'probability',
        'impact',
        'risk_level',
        'compliance',
        'comment',
        'action_corrective',
        'responsable',
        'due_date',
        'compliance_status',
        'compliance_control',
        'compliance_comment',
'responsable_user_id',
        'fingerprint',
        'category',
        'occurrences',
        'last_seen_at',
        'duplicate_of_id',
        'wazuh_data',
        'initial_state',
        'sca_history',
    ];

    protected $casts = [
        'severity' => 'integer',
        'detected_at' => 'datetime',
        'due_date' => 'date',
        'occurrences' => 'integer',
        'last_seen_at' => 'datetime',
        'wazuh_data' => 'array',
        'initial_state' => 'array',
        'sca_history' => 'array',
    ];

    protected static function booted(): void
    {
        // État initial figé à la création : sert au tableau de suivi (avant / après)
        static::creating(function (Incident $incident) {
            $incident->initial_state ??= $incident->stateSnapshot();
        });
    }

    /**
     * Photo de l'évaluation (valeurs actuelles, ou celles passées en $values).
     */
    public function stateSnapshot(array $values = []): array
    {
        $value = fn (string $key) => array_key_exists($key, $values) ? $values[$key] : $this->{$key};

        return [
            'date' => $this->detected_at?->toIso8601String(),
            'sca_result' => data_get($this->wazuh_data, 'data.sca.check.result'),
            'compliance_status' => $value('compliance_status'),
            'probability' => $value('probability'),
            'impact' => $value('impact'),
            'risk_level' => $value('risk_level'),
        ];
    }

    /**
     * Bilan du contrôle CIS d'après l'historique Wazuh :
     * dernier résultat, nombre de corrections (failed → passed) et de régressions (passed → failed).
     */
    public function scaSummary(): ?array
    {
        $history = $this->sca_history ?? [];

        if (! $history) {
            return null;
        }

        $corrections = $regressions = 0;
        $previous = $history[0]['previous'] ?? null;

        foreach ($history as $event) {
            if ($previous === 'failed' && $event['result'] === 'passed') {
                $corrections++;
            } elseif ($previous === 'passed' && $event['result'] === 'failed') {
                $regressions++;
            }

            $previous = $event['result'];
        }

        $last = end($history);

        return [
            'first_seen' => $history[0]['at'],
            'result' => $last['result'],
            'since' => $last['at'],
            'corrections' => $corrections,
            'regressions' => $regressions,
        ];
    }
public function complianceControl()
{
    return $this->belongsTo(ComplianceControl::class, 'compliance_control');
}

public function responsable()
{
    return $this->belongsTo(User::class, 'responsable_user_id');
}

public function occurrenceLog()
{
    return $this->hasMany(IncidentOccurrence::class)->latest('detected_at');
}

public function duplicateOf()
{
    return $this->belongsTo(Incident::class, 'duplicate_of_id');
}

public function duplicates()
{
    return $this->hasMany(Incident::class, 'duplicate_of_id');
}

/** Incidents "maîtres" : les doublons regroupés sont exclus. */
public function scopePrimary(Builder $query): void
{
    $query->whereNull('duplicate_of_id');
}

/** Incidents à traiter (ni résolus, ni clôturés). */
public function scopeOpen(Builder $query): void
{
    $query->whereIn('status', ['new', 'in_progress']);
}

public function statusLabel(): string
{
    return self::STATUSES[$this->status] ?? $this->status;
}
}
