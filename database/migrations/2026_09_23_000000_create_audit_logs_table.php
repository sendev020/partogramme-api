<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            // Keep the actor id without a foreign key so audit history survives
            // account deletion. Never store clinical values in this table.
            $table->unsignedBigInteger('actor_user_id')->nullable()->index();
            $table->string('entity_type', 64);
            $table->unsignedBigInteger('entity_id');
            $table->string('action', 32);
            $table->json('changed_fields');
            $table->timestamp('occurred_at');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['entity_type', 'entity_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
