<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Guest;
use App\Models\Tenant;
use App\Models\User;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('id_ID'); // Menggunakan format data Indonesia

        // 1. Buat Master Event Dummy
        $event = Event::create([
            'name' => 'Gala Dinner & Business Gathering 2026',
            'location' => 'Hotel Danau Toba International, Medan',
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-10',
            'is_active' => true,
        ]);

        // 2. Daftar Tenant Sesuai Request
        $tenantList = ['Bank Mandiri', 'Bank BRI', 'Bank BTN', 'Bank Permata', "L'VIORs"];
        $tenantIds = [];

        foreach ($tenantList as $tenantName) {
            // Hilangkan spasi dan tanda kutip untuk dijadikan username (contoh: bankmandiri, lviors)
            $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $tenantName));

            // Buat User untuk Tenant
            $user = User::create([
                'name' => 'Admin '.$tenantName,
                'username' => $username,
                'password' => Hash::make('password'),
                'role' => 'tenant',
            ]);

            // Buat Data Tenant
            $tenant = Tenant::create([
                'user_id' => $user->id,
                'name' => $tenantName,
                'contact_person' => $faker->name,
            ]);

            $tenantIds[] = $tenant->id;
        }

        // 3. Generate 200 Data Tamu (Disebar acak ke 5 tenant)
        for ($i = 0; $i < 200; $i++) {
            Guest::create([
                'tenant_id' => $faker->randomElement($tenantIds),
                'event_id' => $event->id,
                'name' => $faker->name,
                'phone' => $faker->phoneNumber,
                'gender' => $faker->randomElement(['L', 'P']),
                'job_type' => $faker->jobTitle,
                // qr_code tidak perlu diisi manual karena sudah auto-generate (UUID) di Model Guest
                'is_attended' => false,
            ]);
        }
    }
}
