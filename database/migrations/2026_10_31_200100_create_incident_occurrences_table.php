<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Historique de toutes les alertes Wazuh rattachées à un incident
     * (une alerte qui se répète n'ouvre plus un nouvel incident).
     */
    public function up(): void
    {
        Schema::create('incident_occurrences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained()->cascadeOnDelete();
            $table->string('wazuh_alert_id')->unique();
            $table->timestamp('detected_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_occurrences');
    }
};
