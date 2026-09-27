<?php

namespace Database\Seeders;

use App\Models\Testimonial;
use Illuminate\Database\Seeder;

class TestimonialSeeder extends Seeder
{
    /**
     * Run the database seeds. Content mirrors
     * src/data/mock/testimonials.js from the React app.
     */
    public function run(): void
    {
        $testimonials = [
            [
                'patient_name_ar' => 'نورة الشهري', 'patient_name_en' => 'Noura Alshehri',
                'role_ar' => 'مريضة تقويم أسنان', 'role_en' => 'Orthodontics Patient',
                'photo_path' => 'https://images.pexels.com/photos/37159572/pexels-photo-37159572.jpeg?auto=compress&cs=tinysrgb&fm=webp&w=200&h=200&fit=crop',
                'rating' => 5,
                'quote_ar' => 'تجربة رائعة من أول زيارة. الفريق شرح لي كل خطوة بصبر، والنتيجة فاقت توقعاتي تمامًا.',
                'quote_en' => 'An amazing experience from the very first visit. The team patiently explained every step, and the result exceeded my expectations.',
            ],
            [
                'patient_name_ar' => 'فيصل القحطاني', 'patient_name_en' => 'Faisal Alqahtani',
                'role_ar' => 'مريض زراعة أسنان', 'role_en' => 'Dental Implants Patient',
                'photo_path' => 'https://images.pexels.com/photos/7562179/pexels-photo-7562179.jpeg?auto=compress&cs=tinysrgb&fm=webp&w=200&h=200&fit=crop',
                'rating' => 5,
                'quote_ar' => 'كنت خائفًا جدًا من فكرة زراعة الأسنان، لكن د. عمر جعل الأمر سهلاً وغير مؤلم إطلاقًا. أنصح الجميع بهذه العيادة.',
                'quote_en' => 'I was terrified of getting implants, but Dr. Omar made it easy and completely painless. I recommend this clinic to everyone.',
            ],
            [
                'patient_name_ar' => 'ريم العتيبي', 'patient_name_en' => 'Reem Alotaibi',
                'role_ar' => 'مريضة تبييض أسنان', 'role_en' => 'Whitening Patient',
                'photo_path' => 'https://images.pexels.com/photos/36764752/pexels-photo-36764752.jpeg?auto=compress&cs=tinysrgb&fm=webp&w=200&h=200&fit=crop',
                'rating' => 5,
                'quote_ar' => 'العيادة نظيفة جدًا ومريحة، والموظفون ودودون. تبييض الأسنان أعطاني نتيجة فورية خلال جلسة واحدة فقط!',
                'quote_en' => 'The clinic is spotless and comfortable, and the staff are so friendly. The whitening gave me instant results in just one session!',
            ],
            [
                'patient_name_ar' => 'سلطان المطيري', 'patient_name_en' => 'Sultan Almutairi',
                'role_ar' => 'مريض علاج جذور', 'role_en' => 'Root Canal Patient',
                'photo_path' => 'https://images.pexels.com/photos/10616827/pexels-photo-10616827.jpeg?auto=compress&cs=tinysrgb&fm=webp&w=200&h=200&fit=crop',
                'rating' => 4,
                'quote_ar' => 'كنت أؤجل علاج العصب لسنوات بسبب الخوف من الألم، لكن الجلسة كانت أسهل بكثير مما توقعت. شكرًا للفريق الرائع.',
                'quote_en' => 'I put off my root canal for years out of fear, but the session was far easier than I expected. Thank you to the amazing team.',
            ],
        ];

        foreach ($testimonials as $data) {
            Testimonial::updateOrCreate(
                ['patient_name_en' => $data['patient_name_en']],
                [...$data, 'is_published' => true],
            );
        }
    }
}
