<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('observations')) {
            return;
        }

        Schema::table('observations', function (Blueprint $table) {
            if (Schema::hasColumn('observations', 'fetal_position ')) {
                $table->renameColumn('fetal_position ', 'fetal_position');
            }

            if (Schema::hasColumn('observations', 'caput ')) {
                $table->renameColumn('caput ', 'caput');
            }

            if (! Schema::hasColumn('observations', 'urines')) {
                $table->string('urines')->nullable()->after('moulding');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('observations')) {
            return;
        }

        Schema::table('observations', function (Blueprint $table) {
            if (Schema::hasColumn('observations', 'fetal_position')) {
                $table->renameColumn('fetal_position', 'fetal_position ');
            }

            if (Schema::hasColumn('observations', 'caput')) {
                $table->renameColumn('caput', 'caput ');
            }

            if (Schema::hasColumn('observations', 'urines')) {
                $table->dropColumn('urines');
            }
        });
    }
};
