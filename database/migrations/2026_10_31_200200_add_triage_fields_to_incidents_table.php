<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->string('fingerprint', 40)->nullable()->index();
            $table->string('category')->nullable();
            $table->unsignedInteger('occurrences')->default(1);
            $table->timestamp('last_seen_at')->nullable();
            $table->foreignId('duplicate_of_id')->nullable()->constrained('incidents')->nullOnDelete();
            $table->json('wazuh_data')->nullable();
            $table->text('false_positive_reason')->nullable();
            $table->foreignId('suppression_rule_id')->nullable()->constrained('suppression_rules')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('suppression_rule_id');
            $table->dropConstrainedForeignId('duplicate_of_id');
            $table->dropIndex(['fingerprint']);
            $table->dropColumn(['fingerprint', 'category', 'occurrences', 'last_seen_at', 'wazuh_data', 'false_positive_reason']);
        });
    }
};
