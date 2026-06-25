<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('labours')) {
            return;
        }

        Schema::table('labours', function (Blueprint $table) {
            if (! Schema::hasColumn('labours', 'active_phase_start')) {
                $table->dateTime('active_phase_start')->nullable()->after('end_time');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('labours')) {
            return;
        }

        Schema::table('labours', function (Blueprint $table) {
            if (Schema::hasColumn('labours', 'active_phase_start')) {
                $table->dropColumn('active_phase_start');
            }
        });
    }
};
