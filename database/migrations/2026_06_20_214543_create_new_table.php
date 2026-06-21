<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->softDeletes(); // ajoute deleted_at nullable
        });

        Schema::table('labours', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('observations', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('alerts', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('referrals', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('deaths', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('deliveries', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('labours', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('observations', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('alerts', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('referrals', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('deaths', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
