<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compliance_controls', function (Blueprint $table) {
            $table->id();

            $table->string('framework');
            $table->string('control_code');
            $table->string('title');
            $table->text('description')->nullable();

            $table->timestamps();

            $table->unique(['framework', 'control_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_controls');
    }
};
