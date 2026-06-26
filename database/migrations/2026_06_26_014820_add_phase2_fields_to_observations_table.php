<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::table('observations', function (Blueprint $table) {
            $table->enum('amniotic_fluid', ['Intact', 'Clair', 'Meconial+', 'Meconial++', 'Meconial+++','Sanglant'])->nullable();

            // Ralentissement RCF
            $table->enum('fetal_heart_deceleration', ['Aucun', 'Precoce', 'Tardif', 'Variable'])->nullable();

            // Position fœtale
            $table->enum('fetal_position', ['Anterieure', 'Posterieure', 'Transverse'])->nullable();

            // Bosse sérosanguine
            $table->enum('caput', ['0', '+', '++','+++'])->nullable();

            // Modelage
            $table->enum('moulding', ['0', '+', '++','+++'])->nullable();


            // // urines
            // $table->string('urines')->nullable();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('observations', function (Blueprint $table) {
            if (Schema::hasColumn('observations', 'amniotic_fluid')) {
                $table->dropColumn('amniotic_fluid');
            }
            if (Schema::hasColumn('observations', 'fetal_heart_deceleration')) {
                $table->dropColumn('fetal_heart_deceleration');
            }
            if (Schema::hasColumn('observations', 'fetal_position')) {
                $table->dropColumn('fetal_position');
            }
            if (Schema::hasColumn('observations', 'caput')) {
                $table->dropColumn('caput');
            }
            if (Schema::hasColumn('observations', 'moulding')) {
                $table->dropColumn('moulding');
            }
            // if (Schema::hasColumn('observations', 'urines')) {
            //     $table->dropColumn('urines');
            // }
        });
    }
};
