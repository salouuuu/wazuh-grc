<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->string('probability')->nullable()->after('severity');
            $table->string('impact')->nullable()->after('probability');
            $table->string('risk_level')->nullable()->after('impact');
            $table->text('compliance')->nullable()->after('risk_level');
        });
    }

    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->dropColumn([
                'probability',
                'impact',
                'risk_level',
                'compliance',
            ]);
        });
    }
};

