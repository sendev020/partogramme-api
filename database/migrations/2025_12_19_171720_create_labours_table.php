<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('labours', function (Blueprint $table) {
            $table->id();

            // 🔁 Synchronisation
            $table->unsignedBigInteger('server_id')->nullable()->unique();

            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // ✅ une seule fois

            $table->enum('district', [
                'sedhiou',
                'goudomp',
                'bounkiling',
            ]);

            $table->string('poste_de_sante')->nullable();

            $table->dateTime('start_time');
            $table->dateTime('end_time')->nullable();

            $table->enum('status', ['en_cours', 'termine', 'refere', 'delivery', 'death'])->default('en_cours');

            $table->string('hospital_referred_to')->nullable();

            $table->boolean('synced')->default(false);

            $table->string('labor_onset')->nullable();

            $table->dateTime('active_phase_diagnosis_at')->nullable();

            $table->boolean('membranes_ruptured')->nullable();

            $table->dateTime('membranes_rupture_at')->nullable();

            $table->boolean('membranes_rupture_unknown')->nullable();

            $table->dateTime('active_phase_start')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('labours');
    }
};
