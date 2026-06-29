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
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
             // 🔁 Synchronisation
            $table->unsignedBigInteger('server_id')->nullable()->unique();
            $table->boolean('synced')->default(false);

            // 🔗 Relations
            $table->foreignId('labour_id')
                ->constrained('labours')
                ->onDelete('cascade');

            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // ✅ une seule fois

            $table->enum('district', [
                'sedhiou',
                'goudomp',
                'bounkiling',
            ]);

            $table->string('poste_de_sante')->nullable();

            // 🚼 Détails de l'accouchement
             $table->string('voie'); // e.g., 'vaginal', 'cesarean'
            $table->string('sexe'); // e.g., 'male', 'female'
            $table->float('poids'); // in kilograms
             $table->dateTime('heure_naissance'); // in hours (e.g., 14.5 for 2:30 PM)
            $table->string('notes')->nullable(); // Additional notes about the delivery
            $table->string('complications')->nullable(); // e.g., 'none', 'breech_birth', etc.
            $table->string('soins_administres')->nullable(); // e.g., '
            $table->string('uterotonic_given')->nullable(); // e.g., 'yes', 'no'
            $table->string('uterotonic_type')->nullable();
            $table->string('cord_clamping_time')->nullable();
            $table->integer('controlled_cord_traction')->nullable();
            $table->integer('uterine_massage')->nullable();
            $table->integer('uterine_tone_checked')->nullable();
            $table->string('placenta_complete')->nullable();
            $table->integer('estimated_blood_loss_ml')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};
