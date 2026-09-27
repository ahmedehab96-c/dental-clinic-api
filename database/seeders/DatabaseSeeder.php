<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database. Order follows FK dependencies:
     * settings/categories/services first, then doctors (link to services),
     * then everything that references doctors/services, then demo accounts
     * and a couple of sample bookings.
     */
    public function run(): void
    {
        $this->call([
            ClinicSettingSeeder::class,
            BlogCategorySeeder::class,
            ServiceSeeder::class,
            DoctorSeeder::class,
            BlogPostSeeder::class,
            TestimonialSeeder::class,
            GalleryCaseSeeder::class,
            FaqSeeder::class,
        ]);

        $admin = User::factory()->admin()->create([
            'name' => 'Clinic Admin',
            'email' => 'admin@radiantdental.care',
        ]);

        $patient = User::factory()->create([
            'name' => 'Ahmed Mohammed',
            'email' => 'patient@example.com',
            'phone' => '0550000001',
        ]);

        $checkup = Service::where('slug', 'general-checkup')->first();
        $whitening = Service::where('slug', 'teeth-whitening')->first();
        $drSara = Doctor::where('slug', 'dr-sara-alamri')->first();
        $drLina = Doctor::where('slug', 'dr-lina-mansour')->first();

        // Demo doctor login, linked to Dr. Sara's profile so the doctor panel has real data.
        $doctorUser = User::factory()->doctor()->create([
            'name' => 'Dr. Sara Alamri',
            'email' => 'doctor@radiantdental.care',
        ]);
        $drSara->update(['user_id' => $doctorUser->id]);

        Appointment::factory()->create([
            'reference' => 'APT-'.strtoupper(Str::random(6)),
            'service_id' => $checkup->id,
            'doctor_id' => $drSara->id,
            'user_id' => $patient->id,
            'patient_name' => $patient->name,
            'patient_phone' => $patient->phone,
            'patient_email' => $patient->email,
            'date' => now()->addDays(3)->toDateString(),
            'time' => '10:00',
            'status' => 'confirmed',
        ]);

        Appointment::factory()->create([
            'reference' => 'APT-'.strtoupper(Str::random(6)),
            'service_id' => $whitening->id,
            'doctor_id' => $drLina->id,
            'user_id' => null,
            'patient_name' => 'Sara Al-Fahad',
            'patient_phone' => '0559876543',
            'patient_email' => 'sara.alfahad@example.com',
            'date' => now()->addDays(7)->toDateString(),
            'time' => '16:00',
            'status' => 'pending',
        ]);

        $this->command?->info("Demo admin: {$admin->email} / password: password");
        $this->command?->info("Demo doctor: {$doctorUser->email} / password: password");
        $this->command?->info("Demo patient: {$patient->email} / password: password");
    }
}
