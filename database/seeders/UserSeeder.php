<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ✅ ADMIN — voit tout, tous districts
        User::create([
            'name' => 'Admin Système',
            'email' => 'admin@partogramme.test',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'district' => null,
            'poste_de_sante' => null,
            'phone' => '770000001',
            'is_active' => true,
        ]);

        // ✅ SUPERVISEURS — un par district
        User::create([
            'name' => 'Superviseur Sédhiou',
            'email' => 'superviseur.sedhiou@partogramme.test',
            'password' => Hash::make('password123'),
            'role' => 'superviseur',
            'district' => 'sedhiou',
            'poste_de_sante' => null, // un superviseur supervise tout le district, pas un poste précis
            'phone' => '770000002',
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Superviseur Goudomp',
            'email' => 'superviseur.goudomp@partogramme.test',
            'password' => Hash::make('password123'),
            'role' => 'superviseur',
            'district' => 'goudomp',
            'poste_de_sante' => null,
            'phone' => '770000003',
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Superviseur Bounkiling',
            'email' => 'superviseur.bounkiling@partogramme.test',
            'password' => Hash::make('password123'),
            'role' => 'superviseur',
            'district' => 'bounkiling',
            'poste_de_sante' => null,
            'phone' => '770000004',
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Superviseur Régional',
            'email' => 'superviseur.regional@partogramme.test',
            'password' => Hash::make('password123'),
            'role' => 'superviseur_regional',
            'district' => null,
            'poste_de_sante' => null,
            'phone' => '770000004',
            'is_active' => true,
        ]);

        // ✅ SAGES-FEMMES — chacune dans un poste de santé précis
        User::create([
            'name' => 'Fatou Diallo',
            'email' => 'fatou.diallo@partogramme.test',
            'password' => Hash::make('password123'),
            'role' => 'sage_femme',
            'district' => 'sedhiou',
            'poste_de_sante' => 'Poste de Santé de Diende',
            'phone' => '770000005',
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Awa Sané',
            'email' => 'awa.sane@partogramme.test',
            'password' => Hash::make('password123'),
            'role' => 'sage_femme',
            'district' => 'sedhiou',
            'poste_de_sante' => 'Poste de Santé de Marsassoum', // même district, poste différent de Fatou
            'phone' => '770000006',
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Mariama Ba',
            'email' => 'mariama.ba@partogramme.test',
            'password' => Hash::make('password123'),
            'role' => 'sage_femme',
            'district' => 'goudomp',
            'poste_de_sante' => 'Poste de Santé de Goudomp Centre',
            'phone' => '770000007',
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Kiné Cissé',
            'email' => 'kine.cisse@partogramme.test',
            'password' => Hash::make('password123'),
            'role' => 'sage_femme',
            'district' => 'bounkiling',
            'poste_de_sante' => 'Poste de Santé de Bounkiling Centre',
            'phone' => '770000008',
            'is_active' => true,
        ]);

        // ✅ Compte désactivé pour tester le blocage is_active
        User::create([
            'name' => 'Ancienne Employée',
            'email' => 'inactive@partogramme.test',
            'password' => Hash::make('password123'),
            'role' => 'sage_femme',
            'district' => 'sedhiou',
            'poste_de_sante' => 'Poste de Santé de Diende',
            'phone' => '770000009',
            'is_active' => false,
        ]);

        $this->command->info('✅ 9 utilisateurs créés (1 admin, 3 superviseurs, 4 sages-femmes, 1 désactivé)');
     }
}
