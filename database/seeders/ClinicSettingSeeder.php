<?php

namespace Database\Seeders;

use App\Models\ClinicSetting;
use Illuminate\Database\Seeder;

class ClinicSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ClinicSetting::updateOrCreate(['id' => 1], [
            'address_ar' => 'الرياض، المملكة العربية السعودية — طريق الملك فهد',
            'address_en' => 'Riyadh, Saudi Arabia — King Fahd Rd.',
            'phone' => '+966550000000',
            'whatsapp' => '966550000000',
            'email' => 'hello@radiantdental.care',
            'working_hours_ar' => 'السبت – الخميس: 9 صباحًا – 9 مساءً',
            'working_hours_en' => 'Sat – Thu: 9:00 AM – 9:00 PM',
            'social_links' => [
                'facebook' => null,
                'instagram' => null,
                'whatsapp' => 'https://wa.me/966550000000',
                'tiktok' => null,
            ],
            'default_og_image_path' => 'https://images.pexels.com/photos/19879741/pexels-photo-19879741.jpeg?auto=compress&cs=tinysrgb&fm=webp&w=1200&h=630&fit=crop',
            'map_lat' => 24.7136,
            'map_lng' => 46.6753,
        ]);
    }
}
