<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    /**
     * Run the database seeds. Content mirrors the `faqSection.items`
     * entries in src/data/i18n/{en,ar}.js from the React app.
     */
    public function run(): void
    {
        $faqs = [
            [
                'question_ar' => 'كيف يمكنني حجز موعد؟',
                'question_en' => 'How do I book an appointment?',
                'answer_ar' => 'يمكنك الحجز مباشرة من صفحة "احجز موعدك"، أو الاتصال بالعيادة، أو مراسلتنا عبر واتساب — أيهما أسهل بالنسبة لك.',
                'answer_en' => 'You can book online through our "Book Appointment" page, call the clinic directly, or message us on WhatsApp — whichever is easiest for you.',
            ],
            [
                'question_ar' => 'هل تقبلون التأمين الطبي؟',
                'question_en' => 'Do you accept dental insurance?',
                'answer_ar' => 'نعم، نتعامل مع معظم شركات التأمين الرئيسية. أحضر بطاقة التأمين في زيارتك الأولى وسيتحقق فريقنا من التغطية.',
                'answer_en' => 'Yes, we work with most major insurance providers. Bring your insurance card to your first visit and our team will verify your coverage.',
            ],
            [
                'question_ar' => 'هل تبييض الأسنان آمن على المينا؟',
                'question_en' => 'Is teeth whitening safe for enamel?',
                'answer_ar' => 'بالتأكيد. تبييضنا الاحترافي تحت إشراف طبيب مصمم لحماية المينا مع رفع درجة البياض بأمان.',
                'answer_en' => 'Absolutely. Our professional, doctor-supervised whitening is formulated to protect enamel while lifting your shade safely.',
            ],
            [
                'question_ar' => 'هل تعالجون الأطفال؟',
                'question_en' => 'Do you treat children?',
                'answer_ar' => 'نعم، أطباء الأطفال المتخصصون لدينا يقدمون رعاية لطيفة في بيئة مصممة لتبقي المرضى الصغار هادئين ومرتاحين.',
                'answer_en' => 'Yes, our pediatric specialists provide gentle, kid-friendly care in an environment designed to keep young patients calm and comfortable.',
            ],
            [
                'question_ar' => 'ماذا لو كانت لدي حالة طوارئ؟',
                'question_en' => 'What if I have a dental emergency?',
                'answer_ar' => 'اتصل بنا فورًا — نخصص مواعيد في نفس اليوم لحالات الألم المفاجئ أو الإصابات أو التورم لنراك في أسرع وقت.',
                'answer_en' => "Call us right away — we reserve same-day slots for urgent pain, trauma, or swelling so you're seen as quickly as possible.",
            ],
            [
                'question_ar' => 'هل تتوفر خطط دفع مرنة؟',
                'question_en' => 'Do you offer payment plans?',
                'answer_ar' => 'نعم، نوفر خطط دفع مرنة لعلاجات مثل الزراعة والتقويم والقشور. اسأل موظفي الاستقبال للتفاصيل.',
                'answer_en' => 'Yes, we offer flexible payment plans for treatments like implants, orthodontics, and veneers. Ask our front desk for details.',
            ],
        ];

        foreach ($faqs as $index => $data) {
            Faq::updateOrCreate(
                ['question_en' => $data['question_en']],
                [...$data, 'sort_order' => $index, 'is_published' => true],
            );
        }
    }
}
