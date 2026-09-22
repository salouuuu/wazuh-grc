<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->string('compliance_status')->nullable();
            $table->string('compliance_control')->nullable();
            $table->text('compliance_comment')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->dropColumn([
                'compliance_status',
                'compliance_control',
                'compliance_comment',
            ]);
        });
    }
};
