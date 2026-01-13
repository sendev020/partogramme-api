<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id(); // MySQL ID
            // 🔁 Synchronisation
            $table->unsignedBigInteger('server_id')->nullable()->unique();

            $table->integer('age');
            $table->string('name');
            $table->integer('parity');
            $table->integer('gestational_age');
            $table->text('risk_factors')->nullable();

            $table->boolean('synced')->default(false);

            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
