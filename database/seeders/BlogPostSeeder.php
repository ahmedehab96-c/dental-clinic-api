<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Doctor;
use Illuminate\Database\Seeder;

class BlogPostSeeder extends Seeder
{
    /**
     * Run the database seeds. Content mirrors
     * src/data/mock/blog.js from the React app.
     */
    public function run(): void
    {
        $posts = [
            [
                'slug' => 'daily-teeth-care-tips',
                'category_key' => 'preventive-care',
                'author_slug' => 'dr-sara-alamri',
                'title_ar' => '7 عادات يومية تحافظ على صحة أسنانك', 'title_en' => '7 Daily Habits That Protect Your Teeth',
                'excerpt_ar' => 'اكتشف العادات البسيطة التي يمكنك اتباعها يوميًا لحماية أسنانك من التسوس والتآكل.',
                'excerpt_en' => 'Discover simple habits you can follow every day to protect your teeth from decay and wear.',
                'content_ar' => [
                    'العناية اليومية بالأسنان لا تقتصر على تنظيفها بالفرشاة فقط، بل تشمل مجموعة من العادات التي تحمي مينا الأسنان واللثة على المدى الطويل.',
                    'ابدأ يومك بتنظيف الأسنان لمدة دقيقتين على الأقل باستخدام معجون يحتوي على الفلورايد، ولا تنسَ استخدام خيط الأسنان مرة يوميًا للوصول إلى المناطق التي لا تصلها الفرشاة.',
                    'قلل من المشروبات السكرية والحمضية، فهي من أكبر أسباب تآكل مينا الأسنان. واحرص على شرب كمية كافية من الماء بعد الوجبات لتنظيف الفم بشكل طبيعي.',
                    'وأخيرًا، لا تهمل الفحص الدوري كل ستة أشهر — فالاكتشاف المبكر لأي مشكلة يوفر عليك علاجًا أطول وأكثر تكلفة لاحقًا.',
                ],
                'content_en' => [
                    "Daily dental care goes far beyond brushing — it's a set of habits that protect your enamel and gums over the long run.",
                    "Start your day by brushing for at least two minutes with fluoride toothpaste, and don't skip flossing once a day to reach where the brush can't.",
                    'Cut back on sugary and acidic drinks, one of the biggest causes of enamel erosion, and drink enough water after meals to naturally rinse your mouth.',
                    'Finally, never skip your six-month checkup — catching a problem early saves you a longer, costlier treatment down the road.',
                ],
                'image_path' => 'https://images.pexels.com/photos/6627534/pexels-photo-6627534.jpeg?auto=compress&cs=tinysrgb&fm=webp&w=900&h=600&fit=crop',
                'published_at' => '2026-06-12', 'read_minutes' => 4,
            ],
            [
                'slug' => 'whitening-myths-facts',
                'category_key' => 'cosmetic-dentistry',
                'author_slug' => 'dr-lina-mansour',
                'title_ar' => 'تبييض الأسنان: حقائق وخرافات شائعة', 'title_en' => 'Teeth Whitening: Common Myths vs. Facts',
                'excerpt_ar' => 'هل تبييض الأسنان يضر المينا؟ نوضح الحقيقة العلمية وراء أكثر الخرافات شيوعًا.',
                'excerpt_en' => 'Does whitening damage your enamel? We break down the science behind the most common myths.',
                'content_ar' => [
                    'يعتقد كثيرون أن تبييض الأسنان يضعف طبقة المينا، لكن الحقيقة أن التبييض الاحترافي تحت إشراف طبيب آمن تمامًا عند اتباع التعليمات الصحيحة.',
                    'من الخرافات الشائعة أيضًا أن نتيجة التبييض دائمة، لكنها في الواقع تدوم من 6 إلى 12 شهرًا حسب نمط حياتك وعادات الأكل والشرب.',
                    'التبييض المنزلي بدون إشراف طبي قد يسبب حساسية مؤقتة، لذا يُفضل دائمًا استشارة طبيب الأسنان قبل البدء بأي برنامج تبييض.',
                ],
                'content_en' => [
                    'Many believe whitening weakens enamel, but professional, doctor-supervised whitening is completely safe when the instructions are followed.',
                    'Another common myth is that results are permanent — in reality, they typically last 6 to 12 months depending on your lifestyle and diet.',
                    "Unsupervised at-home whitening kits can cause temporary sensitivity, so it's always best to consult your dentist before starting any whitening program.",
                ],
                'image_path' => 'https://images.pexels.com/photos/8727447/pexels-photo-8727447.jpeg?auto=compress&cs=tinysrgb&fm=webp&w=900&h=600&fit=crop',
                'published_at' => '2026-05-28', 'read_minutes' => 5,
            ],
            [
                'slug' => 'choosing-right-orthodontic-treatment',
                'category_key' => 'orthodontics',
                'author_slug' => 'dr-sara-alamri',
                'title_ar' => 'كيف تختار نوع التقويم المناسب لك؟', 'title_en' => 'How to Choose the Right Type of Braces',
                'excerpt_ar' => 'من التقويم المعدني إلى الشفاف، إليك دليل مبسط يساعدك على اتخاذ القرار الصحيح.',
                'excerpt_en' => "From metal braces to clear aligners — here's a simple guide to help you decide.",
                'content_ar' => [
                    'يعتمد اختيار نوع التقويم على عدة عوامل، منها درجة تعقيد الحالة، والعمر، والميزانية، والأسلوب الذي يناسب حياتك اليومية.',
                    'التقويم المعدني التقليدي يظل الخيار الأكثر فعالية للحالات المعقدة، بينما يناسب التقويم الشفاف الحالات البسيطة إلى المتوسطة ويمنحك مظهرًا أقل وضوحًا.',
                    'استشر طبيب التقويم لتقييم حالتك بدقة عبر الأشعة والنماذج ثلاثية الأبعاد قبل اتخاذ القرار النهائي.',
                ],
                'content_en' => [
                    'Choosing the right braces depends on several factors: how complex your case is, your age, your budget, and what fits your daily lifestyle.',
                    'Traditional metal braces remain the most effective option for complex cases, while clear aligners suit mild to moderate cases and stay far less visible.',
                    'Consult your orthodontist for a precise evaluation using X-rays and 3D models before making your final decision.',
                ],
                'image_path' => 'https://images.pexels.com/photos/19147369/pexels-photo-19147369.jpeg?auto=compress&cs=tinysrgb&fm=webp&w=900&h=600&fit=crop',
                'published_at' => '2026-05-10', 'read_minutes' => 6,
            ],
            [
                'slug' => 'kids-first-dental-visit',
                'category_key' => 'pediatric-dentistry',
                'author_slug' => 'dr-maha-qasimi',
                'title_ar' => 'زيارة طفلك الأولى لطبيب الأسنان: ماذا تتوقع؟', 'title_en' => "Your Child's First Dental Visit: What to Expect",
                'excerpt_ar' => 'نصائح عملية لتحضير طفلك نفسيًا وجعل الزيارة الأولى تجربة إيجابية وممتعة.',
                'excerpt_en' => 'Practical tips to prepare your child and make the first visit a positive, fun experience.',
                'content_ar' => [
                    'ينصح الخبراء بأن تكون الزيارة الأولى لطبيب الأسنان عند ظهور أول سن لبني أو بعمر السنة الأولى كحد أقصى.',
                    'تحدث مع طفلك بإيجابية عن الزيارة، وتجنب استخدام كلمات مخيفة مثل "إبرة" أو "ألم". استخدم بدلاً منها كلمات بسيطة مثل "عد أسنانك".',
                    'اختيار عيادة متخصصة في طب أسنان الأطفال، مثل عيادتنا، يجعل التجربة أكثر راحة بفضل البيئة المصممة خصيصًا للأطفال والأطباء المدربين على التعامل معهم.',
                ],
                'content_en' => [
                    "Experts recommend a child's first dental visit happen when the first baby tooth appears, or by their first birthday at the latest.",
                    'Talk to your child positively about the visit, and avoid scary words like "needle" or "pain" — use simple phrases like "count your teeth" instead.',
                    'Choosing a clinic that specializes in pediatric dentistry, like ours, makes the experience far more comfortable thanks to a kid-friendly environment and specially trained doctors.',
                ],
                'image_path' => 'https://images.pexels.com/photos/7800657/pexels-photo-7800657.jpeg?auto=compress&cs=tinysrgb&fm=webp&w=900&h=600&fit=crop',
                'published_at' => '2026-04-22', 'read_minutes' => 4,
            ],
        ];

        foreach ($posts as $data) {
            $categoryId = BlogCategory::where('key', $data['category_key'])->value('id');
            $authorId = Doctor::where('slug', $data['author_slug'])->value('id');
            unset($data['category_key'], $data['author_slug']);

            BlogPost::updateOrCreate(['slug' => $data['slug']], [
                ...$data,
                'category_id' => $categoryId,
                'author_doctor_id' => $authorId,
            ]);
        }
    }
}
