<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('observations', function (Blueprint $table) {
            $table->id(); // MySQL ID

            // 🔁 Synchronisation
            $table->unsignedBigInteger('server_id')->nullable()->unique();
            $table->boolean('synced')->default(false);

            // 🔗 Relations
            $table->foreignId('labour_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // ✅ une seule fois

            $table->enum('district', [
                'sedhiou',
                'goudomp',
                'bounkiling',
            ]);

            $table->string('poste_de_sante')->nullable();

            // 🟠 PARTOGRAMME OMS
            $table->decimal('dilation', 4, 1)->nullable(); // cm
            $table->integer('contractions')->nullable();  // /10 min
            $table->integer('fcf')->nullable();           // bpm
            $table->integer('station')->nullable();       // -3 à +3

            // 🟡 ÉTAT MATERNEL
            $table->integer('systolic_bp')->nullable();
            $table->integer('diastolic_bp')->nullable();
            $table->decimal('temperature', 4, 1)->nullable();
            $table->integer('pulse')->nullable();
            // Liquide amniotique
            $table->enum('amniotic_fluid', ['Intact', 'Clair', 'Meconial+', 'Meconial++', 'Meconial+++','Sanglant'])->nullable();

            // Ralentissement RCF
            $table->enum('fetal_heart_deceleration', ['Aucun', 'Precoce', 'Tardif', 'Variable'])->nullable();

            // Position fœtale
            $table->enum('fetal_position', ['Anterieure', 'Posterieure', 'Transverse'])->nullable();

            // Bosse sérosanguine
            $table->enum('caput', ['0', '+', '++','+++'])->nullable();

            // Modelage
            $table->enum('moulding', ['0', '+', '++','+++'])->nullable();


            // urines
            $table->string('urines')->nullable();

            // 📝 NOTES
            $table->text('notes')->nullable();

            // ⏱️ Date réelle de l'observation
            $table->dateTime('observed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('observations');
    }
};
