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

            // 🚼 Détails de l'accouchement
             $table->string('voie'); // e.g., 'vaginal', 'cesarean'
            $table->string('sexe'); // e.g., 'male', 'female'
            $table->float('poids'); // in kilograms
             $table->dateTime('heure_naissance'); // in hours (e.g., 14.5 for 2:30 PM)
            $table->string('notes')->nullable(); // Additional notes about the delivery
            $table->string('complications'); // e.g., 'none', 'breech_birth', etc.
            $table->string('soins_administres'); // e.g., '
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
