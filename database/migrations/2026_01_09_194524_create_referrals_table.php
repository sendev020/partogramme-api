<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
   // 🔁 Synchronisation
            $table->unsignedBigInteger('server_id')->nullable()->unique();
            $table->boolean('synced')->default(false);
            // 🔗 Relations
            $table->foreignId('labour_id')
                ->constrained('labours')
                ->onDelete('cascade');

            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // ✅ une seule fois

            $table->enum('district', [
                'sedhiou',
                'goudomp',
                'bounkiling',
            ]);

            $table->string('poste_de_sante')->nullable();

            // 🏥 Informations de référence
            $table->string('hospital'); // ex: Hôpital régional
            $table->text('reason'); // raison de la référence
            $table->dateTime('referral_time'); // ex: urgence, programmé, etc.
            $table->string('transport_mode'); // ex: ambulance, taxi, etc.
            $table->timestamp('referred_at')->useCurrent();

            // 👨‍⚕️ Personnel
            $table->string('referred_by')->nullable(); // sage-femme / médecin

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
