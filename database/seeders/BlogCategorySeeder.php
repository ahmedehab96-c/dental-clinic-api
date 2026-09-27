<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use Illuminate\Database\Seeder;

class BlogCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['key' => 'preventive-care', 'label_ar' => 'نصائح وقائية', 'label_en' => 'Preventive Care'],
            ['key' => 'cosmetic-dentistry', 'label_ar' => 'تجميل الأسنان', 'label_en' => 'Cosmetic Dentistry'],
            ['key' => 'orthodontics', 'label_ar' => 'تقويم الأسنان', 'label_en' => 'Orthodontics'],
            ['key' => 'pediatric-dentistry', 'label_ar' => 'أسنان الأطفال', 'label_en' => 'Pediatric Dentistry'],
        ];

        foreach ($categories as $category) {
            BlogCategory::updateOrCreate(['key' => $category['key']], $category);
        }
    }
}
