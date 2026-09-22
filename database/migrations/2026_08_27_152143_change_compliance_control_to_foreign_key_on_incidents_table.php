<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->unsignedBigInteger('compliance_control')
                ->nullable()
                ->change();

            $table->foreign('compliance_control')
                ->references('id')
                ->on('compliance_controls')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->dropForeign(['compliance_control']);

            $table->string('compliance_control')
                ->nullable()
                ->change();
        });
    }
};
