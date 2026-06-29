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

            $table->boolean('companion_present')->default(false);
            $table->boolean('pain_relief')->default(false);
            $table->boolean('oral_fluids')->default(false);

            $table->string('position')->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('recorded_at')->nullable();

            $table->boolean('synced')->default(false);

            $table->string('created_at')->nullable();
            $table->string('updated_at')->nullable();
            $table->string('deleted_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_care');
    }
};
