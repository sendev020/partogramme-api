<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_care', function (Blueprint $table) {
            $table->id();

            $table->foreignId('labour_id')
                ->constrained('labours')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('server_id')->nullable()->unique();

            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            //$table->boolean('companion_present')->default(false);
            //cet table pain_relief est un booléen qui indique si la patiente a reçu un soulagement de la douleur ou non. Il peut être utilisé pour suivre l'utilisation des méthodes de soulagement de la douleur pendant le travail et pour évaluer l'efficacité de ces méthodes.
            //$table->boolean('pain_relief')->default(false);
            //$table->boolean('oral_fluids')->default(false);

            $table->enum('companion_present', [
                'oui',
                'non',
                'refuser',
            ])->default('non');

            $table->enum('oral_fluids', [
                'oui',
                'non',
                'refuser',
            ])->default('non');

            $table->enum('pain_relief', [
                'oui',
                'non',
                'refuser',
            ])->default('non');

            $table->string('position')->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('recorded_at')->nullable();

            $table->boolean('synced')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_care');
    }
};
