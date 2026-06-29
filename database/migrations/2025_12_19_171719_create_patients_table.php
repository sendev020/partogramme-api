<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id(); // ✅ doit être en premier

            // 🔁 Synchronisation
            $table->unsignedBigInteger('server_id')->nullable()->unique();

            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->enum('district', [
                'sedhiou',
                'goudomp',
                'bounkiling',
            ]);

            $table->string('poste_de_sante')->nullable();

            $table->integer('age');
            $table->string('name');
            $table->integer('parity');
            $table->integer('gestational_age');
            $table->text('risk_factors')->nullable();

            $table->boolean('synced')->default(false);

            $table->string('created_at')->nullable();
            $table->string('updated_at')->nullable();
            $table->string('deleted_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
