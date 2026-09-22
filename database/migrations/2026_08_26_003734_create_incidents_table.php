
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();

            $table->string('wazuh_alert_id')->unique();

            $table->string('rule_id');
            $table->string('title');
            $table->text('description')->nullable();

            $table->unsignedTinyInteger('severity');

            $table->string('agent_id')->nullable();
            $table->string('agent_name')->nullable();
            $table->ipAddress('agent_ip')->nullable();

            $table->string('source_user')->nullable();
            $table->text('command')->nullable();

            $table->string('status')->default('new');

            $table->timestamp('detected_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
