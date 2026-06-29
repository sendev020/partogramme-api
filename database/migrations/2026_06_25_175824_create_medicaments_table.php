<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicaments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('labour_id')
                ->constrained('labours')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('server_id')->nullable()->unique();

            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('dose')->nullable();
            $table->string('route')->nullable(); // IV, IM, PO...
            $table->dateTime('administered_at')->nullable();

            $table->text('indication')->nullable();
            $table->text('notes')->nullable();

            $table->boolean('synced')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicaments');
    }
};
