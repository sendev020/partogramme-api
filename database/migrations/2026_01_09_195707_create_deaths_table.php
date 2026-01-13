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
        Schema::create('deaths', function (Blueprint $table) {
            $table->id();
            // 🔁 Synchronisation
            $table->unsignedBigInteger('server_id')->nullable()->unique();
            $table->boolean('synced')->default(false);
            // 🔗 Relations
            $table->foreignId('labour_id')
                ->constrained('labours')
                ->onDelete('cascade');
            // ⚰️ Détails du décès
            $table->string('concerner')->nullable();
            $table->string('cause_deces')->nullable(); // e.g., 'complications', 'infection', etc.
            $table->dateTime('heure_deces')->nullable(); // Date and time of death
            $table->text('notes')->nullable(); // Additional notes about the death
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deaths');
    }
};
