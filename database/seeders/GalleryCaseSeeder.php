<?php

namespace Database\Seeders;

use App\Models\GalleryCase;
use App\Models\Service;
use Illuminate\Database\Seeder;

class GalleryCaseSeeder extends Seeder
{
    /**
     * Run the database seeds. Content mirrors
     * src/data/mock/gallery.js from the React app.
     */
    public function run(): void
    {
        $cases = [
            [
                'title_ar' => 'ابتسامة هوليوود كاملة', 'title_en' => 'Full Hollywood Smile',
                'service_slug' => 'veneers-cosmetic',
                'before_image_path' => 'https://images.pexels.com/photos/3762400/pexels-photo-3762400.jpeg?auto=compress&cs=tinysrgb&fm=webp&w=700&h=700&fit=crop',
                'after_image_path' => 'https://images.pexels.com/photos/3762402/pexels-photo-3762402.jpeg?auto=compress&cs=tinysrgb&fm=webp&w=700&h=700&fit=crop',
            ],
            [
                'title_ar' => 'تقويم أسنان لمدة 14 شهرًا', 'title_en' => '14-Month Orthodontic Case',
                'service_slug' => 'orthodontics-braces',
                'before_image_path' => 'https://images.pexels.com/photos/3762405/pexels-photo-3762405.jpeg?auto=compress&cs=tinysrgb&fm=webp&w=700&h=700&fit=crop',
                'after_image_path' => 'https://images.pexels.com/photos/3762407/pexels-photo-3762407.jpeg?auto=compress&cs=tinysrgb&fm=webp&w=700&h=700&fit=crop',
            ],
            [
                'title_ar' => 'تبييض احترافي بجلسة واحدة', 'title_en' => 'Single-Session Professional Whitening',
                'service_slug' => 'teeth-whitening',
                'before_image_path' => 'https://images.pexels.com/photos/3762439/pexels-photo-3762439.jpeg?auto=compress&cs=tinysrgb&fm=webp&w=700&h=700&fit=crop',
                'after_image_path' => 'https://images.pexels.com/photos/3762441/pexels-photo-3762441.jpeg?auto=compress&cs=tinysrgb&fm=webp&w=700&h=700&fit=crop',
            ],
            [
                'title_ar' => 'زراعة أسنان أمامية', 'title_en' => 'Front Tooth Implant',
                'service_slug' => 'dental-implants',
                'before_image_path' => 'https://images.pexels.com/photos/12474261/pexels-photo-12474261.jpeg?auto=compress&cs=tinysrgb&fm=webp&w=700&h=700&fit=crop',
                'after_image_path' => 'https://images.pexels.com/photos/11515380/pexels-photo-11515380.jpeg?auto=compress&cs=tinysrgb&fm=webp&w=700&h=700&fit=crop',
            ],
            [
                'title_ar' => 'قشور خزفية لست أسنان', 'title_en' => 'Six-Tooth Porcelain Veneers',
                'service_slug' => 'veneers-cosmetic',
                'before_image_path' => 'https://images.pexels.com/photos/6627573/pexels-photo-6627573.jpeg?auto=compress&cs=tinysrgb&fm=webp&w=700&h=700&fit=crop',
                'after_image_path' => 'https://images.pexels.com/photos/36763563/pexels-photo-36763563.jpeg?auto=compress&cs=tinysrgb&fm=webp&w=700&h=700&fit=crop',
            ],
            [
                'title_ar' => 'ترميم بعد علاج الجذور', 'title_en' => 'Restoration After Root Canal',
                'service_slug' => 'root-canal',
                'before_image_path' => 'https://images.pexels.com/photos/28110692/pexels-photo-28110692.jpeg?auto=compress&cs=tinysrgb&fm=webp&w=700&h=700&fit=crop',
                'after_image_path' => 'https://images.pexels.com/photos/11956948/pexels-photo-11956948.jpeg?auto=compress&cs=tinysrgb&fm=webp&w=700&h=700&fit=crop',
            ],
        ];

        foreach ($cases as $data) {
            $serviceId = Service::where('slug', $data['service_slug'])->value('id');
            unset($data['service_slug']);

            GalleryCase::updateOrCreate(
                ['title_en' => $data['title_en']],
                [...$data, 'service_id' => $serviceId, 'is_published' => true],
            );
        }
    }
}
