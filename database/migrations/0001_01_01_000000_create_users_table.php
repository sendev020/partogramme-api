<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {

    $table->id();

    $table->string('name', 150);
    $table->string('email', 150)->unique();
    $table->string('password');

    $table->enum('role', [
        'sage_femme',
        'superviseur',
        'superviseur_regional',
        'admin'
    ])->default('sage_femme');

    $table->enum('district', [
        'sedhiou',
        'goudomp',
        'bounkiling'
    ])->nullable();

    $table->string('poste_de_sante')->nullable();

    $table->string('phone')->nullable();

    $table->boolean('is_active')->default(true);

    $table->timestamp('last_login_at')->nullable();

    $table->string('created_at')->nullable();
    $table->string('updated_at')->nullable();
    $table->string('deleted_at')->nullable();

    $table->rememberToken();

    $table->timestamps();
});
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
