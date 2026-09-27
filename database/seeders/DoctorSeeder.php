<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\Service;
use Illuminate\Database\Seeder;

class DoctorSeeder extends Seeder
{
    /**
     * Run the database seeds. Content mirrors
     * src/data/mock/doctors.js from the React app.
     */
    public function run(): void
    {
        $doctors = [
            [
                'slug' => 'dr-sara-alamri',
                'name_ar' => 'د. سارة العمري', 'name_en' => 'Dr. Sara Alamri',
                'specialty_ar' => 'استشارية تقويم أسنان', 'specialty_en' => 'Orthodontics Consultant',
                'bio_ar' => 'خبرة تزيد عن 12 عامًا في تقويم الأسنان للبالغين والأطفال، حاصلة على الزمالة الأمريكية في تقويم الأسنان.',
                'bio_en' => 'Over 12 years of experience in adult and pediatric orthodontics, with an American fellowship in orthodontics.',
                'photo_path' => 'https://images.pexels.com/photos/31043312/pexels-photo-31043312.jpeg?auto=compress&cs=tinysrgb&fm=webp&w=600&h=700&fit=crop',
                'experience_years' => 12, 'rating' => 4.9, 'reviews_count' => 214,
                'education' => [
                    ['ar' => 'بكالوريوس طب وجراحة الفم والأسنان — جامعة الملك سعود', 'en' => 'BDS — King Saud University'],
                    ['ar' => 'زمالة تقويم الأسنان — الولايات المتحدة', 'en' => 'Orthodontics Fellowship — USA'],
                ],
                'featured' => true,
                'service_slugs' => ['orthodontics-braces', 'general-checkup'],
            ],
            [
                'slug' => 'dr-omar-hassan',
                'name_ar' => 'د. عمر حسن', 'name_en' => 'Dr. Omar Hassan',
                'specialty_ar' => 'استشاري زراعة الأسنان', 'specialty_en' => 'Dental Implants Consultant',
                'bio_ar' => 'متخصص في زراعة الأسنان الرقمية الموجهة بالحاسوب، أجرى أكثر من 3000 عملية زراعة ناجحة.',
                'bio_en' => 'Specialist in computer-guided digital implants, with over 3,000 successful implant procedures.',
                'photo_path' => 'https://images.pexels.com/photos/4687340/pexels-photo-4687340.jpeg?auto=compress&cs=tinysrgb&fm=webp&w=600&h=700&fit=crop',
                'experience_years' => 15, 'rating' => 4.8, 'reviews_count' => 312,
                'education' => [
                    ['ar' => 'دكتوراه جراحة الفم والوجه والفكين — ألمانيا', 'en' => 'PhD Oral & Maxillofacial Surgery — Germany'],
                ],
                'featured' => true,
                'service_slugs' => ['dental-implants', 'root-canal'],
            ],
            [
                'slug' => 'dr-lina-mansour',
                'name_ar' => 'د. لينا منصور', 'name_en' => 'Dr. Lina Mansour',
                'specialty_ar' => 'استشارية تجميل الأسنان', 'specialty_en' => 'Cosmetic Dentistry Consultant',
                'bio_ar' => 'رائدة في تصميم الابتسامة الرقمي وابتسامة هوليوود، شغوفة بمنح كل مريض ابتسامة تليق به.',
                'bio_en' => 'A pioneer in digital smile design and Hollywood smiles, passionate about a smile tailored to every patient.',
                'photo_path' => 'https://images.pexels.com/photos/37458097/pexels-photo-37458097.jpeg?auto=compress&cs=tinysrgb&fm=webp&w=600&h=700&fit=crop',
                'experience_years' => 9, 'rating' => 5.0, 'reviews_count' => 178,
                'education' => [
                    ['ar' => 'ماجستير طب الأسنان التجميلي — إيطاليا', 'en' => "Master's in Cosmetic Dentistry — Italy"],
                ],
                'featured' => true,
                'service_slugs' => ['veneers-cosmetic', 'teeth-whitening'],
            ],
            [
                'slug' => 'dr-yousef-najjar',
                'name_ar' => 'د. يوسف النجار', 'name_en' => 'Dr. Yousef Najjar',
                'specialty_ar' => 'استشاري علاج الجذور', 'specialty_en' => 'Endodontics Consultant',
                'bio_ar' => 'متخصص في علاج العصب بالتقنيات الحديثة الخالية من الألم تقريبًا، حريص على راحة المريض في كل خطوة.',
                'bio_en' => 'Specialist in near-painless modern root canal techniques, focused on patient comfort at every step.',
                'photo_path' => 'https://images.pexels.com/photos/37458054/pexels-photo-37458054.jpeg?auto=compress&cs=tinysrgb&fm=webp&w=600&h=700&fit=crop',
                'experience_years' => 11, 'rating' => 4.7, 'reviews_count' => 156,
                'education' => [
                    ['ar' => 'ماجستير علاج الجذور — مصر', 'en' => "Master's in Endodontics — Egypt"],
                ],
                'featured' => false,
                'service_slugs' => ['root-canal', 'general-checkup'],
            ],
            [
                'slug' => 'dr-maha-qasimi',
                'name_ar' => 'د. مها القاسمي', 'name_en' => 'Dr. Maha Al Qasimi',
                'specialty_ar' => 'استشارية طب أسنان الأطفال', 'specialty_en' => 'Pediatric Dentistry Consultant',
                'bio_ar' => 'متخصصة في جعل زيارة الطفل للعيادة تجربة مريحة وممتعة، مع خبرة واسعة في الوقاية المبكرة.',
                'bio_en' => "Focused on making every child's clinic visit calm and enjoyable, with deep expertise in early prevention.",
                'photo_path' => 'https://images.pexels.com/photos/32205053/pexels-photo-32205053.jpeg?auto=compress&cs=tinysrgb&fm=webp&w=600&h=700&fit=crop',
                'experience_years' => 8, 'rating' => 4.9, 'reviews_count' => 201,
                'education' => [
                    ['ar' => 'دبلوم عالي طب أسنان الأطفال — بريطانيا', 'en' => 'Postgraduate Diploma in Pediatric Dentistry — UK'],
                ],
                'featured' => false,
                'service_slugs' => ['pediatric-dentistry', 'teeth-cleaning'],
            ],
            [
                'slug' => 'dr-khalid-otaibi',
                'name_ar' => 'د. خالد العتيبي', 'name_en' => 'Dr. Khalid Alotaibi',
                'specialty_ar' => 'طبيب أسنان عام', 'specialty_en' => 'General Dentist',
                'bio_ar' => 'يقدم رعاية شاملة للأسنان من الفحص الدوري إلى الحشوات التجميلية بأسلوب هادئ ومطمئن.',
                'bio_en' => 'Provides comprehensive care from routine checkups to cosmetic fillings, with a calm, reassuring approach.',
                'photo_path' => 'https://images.pexels.com/photos/37458046/pexels-photo-37458046.jpeg?auto=compress&cs=tinysrgb&fm=webp&w=600&h=700&fit=crop',
                'experience_years' => 7, 'rating' => 4.8, 'reviews_count' => 132,
                'education' => [
                    ['ar' => 'بكالوريوس طب وجراحة الفم والأسنان — جامعة الملك عبدالعزيز', 'en' => 'BDS — King Abdulaziz University'],
                ],
                'featured' => false,
                'service_slugs' => ['general-checkup', 'teeth-cleaning'],
            ],
        ];

        foreach ($doctors as $data) {
            $serviceSlugs = $data['service_slugs'];
            unset($data['service_slugs']);

            $doctor = Doctor::updateOrCreate(['slug' => $data['slug']], $data);

            $serviceIds = Service::whereIn('slug', $serviceSlugs)->pluck('id');
            $doctor->services()->sync($serviceIds);
        }
    }
}
