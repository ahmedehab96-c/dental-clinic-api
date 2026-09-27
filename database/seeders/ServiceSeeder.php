<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds. Content mirrors the React app's
     * src/data/mock/services.js so the seeded API matches the live site.
     */
    public function run(): void
    {
        $services = [
            [
                'slug' => 'general-checkup', 'icon' => 'checkup',
                'name_ar' => 'الفحص الدوري', 'name_en' => 'General Checkup',
                'short_description_ar' => 'فحص شامل للأسنان واللثة لاكتشاف أي مشاكل مبكرًا.',
                'short_description_en' => 'A comprehensive teeth and gum exam to catch issues early.',
                'description_ar' => 'فحص دوري شامل يتضمن تقييمًا كاملاً لصحة الأسنان واللثة باستخدام أحدث أجهزة التصوير الرقمي، مع خطة علاجية واضحة إن لزم الأمر.',
                'description_en' => 'A full oral health assessment using modern digital imaging, with a clear treatment plan if needed.',
                'image_path' => 'https://images.pexels.com/photos/6627483/pexels-photo-6627483.jpeg?auto=compress&cs=tinysrgb&fm=webp&w=900&h=700&fit=crop',
                'duration_ar' => '30 دقيقة', 'duration_en' => '30 minutes', 'price_from' => 150,
                'features' => [
                    ['ar' => 'فحص شامل بالأشعة الرقمية', 'en' => 'Full digital X-ray exam'],
                    ['ar' => 'تقييم صحة اللثة', 'en' => 'Gum health assessment'],
                    ['ar' => 'استشارة طبيب مجانية', 'en' => 'Free doctor consultation'],
                ],
            ],
            [
                'slug' => 'teeth-cleaning', 'icon' => 'cleaning',
                'name_ar' => 'تنظيف الأسنان', 'name_en' => 'Teeth Cleaning',
                'short_description_ar' => 'إزالة الجير والبلاك للحفاظ على لثة صحية وابتسامة نظيفة.',
                'short_description_en' => 'Plaque and tartar removal for healthier gums and a cleaner smile.',
                'description_ar' => 'جلسة تنظيف احترافية تزيل الجير والبلاك المتراكم، وتلمّع الأسنان لمنع تسوسها وتحسين رائحة الفم.',
                'description_en' => 'A professional cleaning session that removes built-up tartar and plaque, then polishes teeth to prevent decay.',
                'image_path' => 'https://images.pexels.com/photos/6528909/pexels-photo-6528909.jpeg?auto=compress&cs=tinysrgb&fm=webp&w=900&h=700&fit=crop',
                'duration_ar' => '45 دقيقة', 'duration_en' => '45 minutes', 'price_from' => 200,
                'features' => [
                    ['ar' => 'إزالة الجير والبلاك', 'en' => 'Tartar & plaque removal'],
                    ['ar' => 'تلميع الأسنان', 'en' => 'Teeth polishing'],
                    ['ar' => 'نصائح للعناية اليومية', 'en' => 'Daily care guidance'],
                ],
            ],
            [
                'slug' => 'teeth-whitening', 'icon' => 'whitening',
                'name_ar' => 'تبييض الأسنان', 'name_en' => 'Teeth Whitening',
                'short_description_ar' => 'ابتسامة أكثر إشراقًا خلال جلسة واحدة بتقنية آمنة وفعالة.',
                'short_description_en' => 'A brighter smile in a single visit with safe, effective technology.',
                'description_ar' => 'تقنية تبييض متطورة تمنحك أسنانًا أكثر بياضًا بعدة درجات خلال جلسة واحدة، مع الحفاظ الكامل على سلامة طبقة المينا.',
                'description_en' => 'Advanced whitening technology that lifts your shade by several levels in one session — fully safe for enamel.',
                'image_path' => 'https://images.pexels.com/photos/5622235/pexels-photo-5622235.jpeg?auto=compress&cs=tinysrgb&fm=webp&w=900&h=700&fit=crop',
                'duration_ar' => '60 دقيقة', 'duration_en' => '60 minutes', 'price_from' => 450,
                'features' => [
                    ['ar' => 'نتائج فورية وملحوظة', 'en' => 'Instant, visible results'],
                    ['ar' => 'آمن على مينا الأسنان', 'en' => 'Enamel-safe formula'],
                    ['ar' => 'يدوم حتى 12 شهرًا', 'en' => 'Lasts up to 12 months'],
                ],
            ],
            [
                'slug' => 'dental-implants', 'icon' => 'implants',
                'name_ar' => 'زراعة الأسنان', 'name_en' => 'Dental Implants',
                'short_description_ar' => 'حل دائم لتعويض الأسنان المفقودة بمظهر ووظيفة طبيعية.',
                'short_description_en' => 'A permanent solution for missing teeth with a natural look and function.',
                'description_ar' => 'زراعة أسنان بأحدث التقنيات ثلاثية الأبعاد لضمان دقة الزرعة وثباتها، مع نتائج تدوم لعشرات السنين.',
                'description_en' => 'Implants placed with precise 3D-guided technology for a secure fit and results that last decades.',
                'image_path' => 'https://images.pexels.com/photos/6502340/pexels-photo-6502340.jpeg?auto=compress&cs=tinysrgb&fm=webp&w=900&h=700&fit=crop',
                'duration_ar' => 'جلسات متعددة', 'duration_en' => 'Multiple sessions', 'price_from' => 2500,
                'features' => [
                    ['ar' => 'تخطيط ثلاثي الأبعاد دقيق', 'en' => 'Precise 3D planning'],
                    ['ar' => 'مواد بتيتانيوم عالية الجودة', 'en' => 'High-grade titanium material'],
                    ['ar' => 'ضمان يصل إلى 10 سنوات', 'en' => 'Up to 10-year warranty'],
                ],
            ],
            [
                'slug' => 'orthodontics-braces', 'icon' => 'braces',
                'name_ar' => 'تقويم الأسنان', 'name_en' => 'Orthodontics & Braces',
                'short_description_ar' => 'تقويم معدني أو شفاف لمحاذاة الأسنان بدقة وثقة.',
                'short_description_en' => 'Metal or clear aligners for precise, confident teeth alignment.',
                'description_ar' => 'خيارات تقويم متعددة تشمل التقويم المعدني والشفاف، مع متابعة دورية لضمان أفضل نتيجة لمحاذاة أسنانك.',
                'description_en' => 'Multiple options including metal braces and clear aligners, with regular follow-ups for the best alignment.',
                'image_path' => 'https://images.pexels.com/photos/5524024/pexels-photo-5524024.jpeg?auto=compress&cs=tinysrgb&fm=webp&w=900&h=700&fit=crop',
                'duration_ar' => '12 – 24 شهرًا', 'duration_en' => '12 – 24 months', 'price_from' => 3200,
                'features' => [
                    ['ar' => 'خيارات شفافة وغير مرئية', 'en' => 'Clear, near-invisible options'],
                    ['ar' => 'متابعة شهرية مجانية', 'en' => 'Free monthly follow-ups'],
                    ['ar' => 'خطة دفع مرنة', 'en' => 'Flexible payment plan'],
                ],
            ],
            [
                'slug' => 'root-canal', 'icon' => 'rootcanal',
                'name_ar' => 'علاج العصب', 'name_en' => 'Root Canal Treatment',
                'short_description_ar' => 'علاج فعّال وغير مؤلم لإنقاذ السن المصاب بالتهاب العصب.',
                'short_description_en' => 'Effective, painless treatment to save a tooth with an infected nerve.',
                'description_ar' => 'علاج عصب دقيق باستخدام أدوات دورانية حديثة يقلل من زمن الجلسة والألم، ويحافظ على السن الطبيعي.',
                'description_en' => 'Precise root canal therapy using modern rotary tools to reduce chair time and discomfort while saving the natural tooth.',
                'image_path' => 'https://images.pexels.com/photos/6627519/pexels-photo-6627519.jpeg?auto=compress&cs=tinysrgb&fm=webp&w=900&h=700&fit=crop',
                'duration_ar' => '60 – 90 دقيقة', 'duration_en' => '60 – 90 minutes', 'price_from' => 600,
                'features' => [
                    ['ar' => 'تخدير موضعي مريح', 'en' => 'Comfortable local anesthesia'],
                    ['ar' => 'أدوات دورانية حديثة', 'en' => 'Modern rotary instruments'],
                    ['ar' => 'الحفاظ على السن الطبيعي', 'en' => 'Preserves the natural tooth'],
                ],
            ],
            [
                'slug' => 'veneers-cosmetic', 'icon' => 'veneers',
                'name_ar' => 'قشور وابتسامة هوليوود', 'name_en' => 'Veneers & Hollywood Smile',
                'short_description_ar' => 'تصميم ابتسامة مثالية بقشور خزفية رفيعة وطبيعية المظهر.',
                'short_description_en' => 'A picture-perfect smile with thin, natural-looking porcelain veneers.',
                'description_ar' => 'تصميم رقمي للابتسامة قبل التنفيذ يتيح لك رؤية النتيجة النهائية، تليها قشور خزفية عالية الجودة تمنحك ابتسامة هوليوود.',
                'description_en' => 'A digital smile design preview lets you see the final result before we start, followed by premium porcelain veneers.',
                'image_path' => 'https://images.pexels.com/photos/3762408/pexels-photo-3762408.jpeg?auto=compress&cs=tinysrgb&fm=webp&w=900&h=700&fit=crop',
                'duration_ar' => 'جلستان', 'duration_en' => '2 sessions', 'price_from' => 1200,
                'features' => [
                    ['ar' => 'تصميم رقمي مسبق للابتسامة', 'en' => 'Digital smile preview'],
                    ['ar' => 'خزف طبيعي المظهر', 'en' => 'Natural-looking porcelain'],
                    ['ar' => 'مقاومة للبقع والتصبغ', 'en' => 'Stain-resistant finish'],
                ],
            ],
            [
                'slug' => 'pediatric-dentistry', 'icon' => 'pediatric',
                'name_ar' => 'طب أسنان الأطفال', 'name_en' => 'Pediatric Dentistry',
                'short_description_ar' => 'رعاية أسنان لطيفة ومخصصة لأصغر أفراد العائلة.',
                'short_description_en' => 'Gentle, kid-friendly dental care for the youngest smiles.',
                'description_ar' => 'بيئة ودودة ومريحة للأطفال، مع أطباء متخصصين في طب أسنان الأطفال لجعل كل زيارة تجربة إيجابية وممتعة.',
                'description_en' => 'A warm, welcoming environment with pediatric specialists who make every visit a positive experience.',
                'image_path' => 'https://images.pexels.com/photos/7800568/pexels-photo-7800568.jpeg?auto=compress&cs=tinysrgb&fm=webp&w=900&h=700&fit=crop',
                'duration_ar' => '30 – 40 دقيقة', 'duration_en' => '30 – 40 minutes', 'price_from' => 180,
                'features' => [
                    ['ar' => 'أطباء متخصصون بالأطفال', 'en' => 'Pediatric specialists'],
                    ['ar' => 'عيادة مصممة لراحة الطفل', 'en' => 'Kid-friendly clinic design'],
                    ['ar' => 'برنامج وقائي للأسنان اللبنية', 'en' => 'Preventive care program'],
                ],
            ],
        ];

        foreach ($services as $service) {
            Service::updateOrCreate(['slug' => $service['slug']], $service);
        }
    }
}
