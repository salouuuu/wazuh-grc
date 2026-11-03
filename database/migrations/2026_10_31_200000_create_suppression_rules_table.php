<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Règles de faux positifs : les alertes Wazuh qui correspondent
     * sont importées directement avec le statut "false_positive".
     */
    public function up(): void
    {
        Schema::create('suppression_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('rule_id')->nullable();
            $table->string('rule_group')->nullable();
            $table->string('agent_name')->nullable();
            $table->string('match_field')->nullable();
            $table->string('match_value')->nullable();
            $table->text('reason');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('hits')->default(0);
            $table->timestamp('last_hit_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppression_rules');
    }
};
