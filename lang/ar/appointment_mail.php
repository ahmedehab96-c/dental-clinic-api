<?php

return [
    'brand' => 'رادينت لطب الأسنان',
    'greeting' => 'مرحبًا :name،',
    'greeting_guest' => 'مرحبًا،',

    'created' => [
        'patient' => [
            'subject' => 'تم استلام طلب موعدك (:reference)',
            'intro' => 'شكرًا لحجزك معنا. تم استلام طلب موعدك وهو بانتظار التأكيد — سنبلغك فور تأكيده.',
        ],
        'doctor' => [
            'subject' => 'موعد جديد مُسند إليك (:reference)',
            'intro' => 'تم حجز موعد جديد معك. إليك التفاصيل:',
        ],
    ],

    'status' => [
        'patient' => [
            'confirmed' => [
                'subject' => 'تم تأكيد موعدك (:reference)',
                'intro' => 'أخبار سارة — تم تأكيد موعدك. نتطلع لرؤيتك.',
            ],
            'completed' => [
                'subject' => 'شكرًا لزيارتك (:reference)',
                'intro' => 'تم تسجيل موعدك كمكتمل. شكرًا لاختيارك لنا، ونتمنى رؤيتك مجددًا.',
            ],
            'cancelled' => [
                'subject' => 'تم إلغاء موعدك (:reference)',
                'intro' => 'تم إلغاء موعدك. إذا لم تكن تتوقع ذلك أو ترغب في حجز موعد جديد، يرجى التواصل معنا.',
            ],
            'other' => [
                'subject' => 'تم تحديث موعدك (:reference)',
                'intro' => 'تم تحديث حالة موعدك إلى «:status».',
            ],
        ],
        'doctor' => [
            'cancelled_by_patient' => [
                'subject' => 'ألغى المريض الموعد (:reference)',
                'intro' => 'ألغى المريض الموعد التالي، وأصبح هذا الوقت متاحًا الآن.',
            ],
            'cancelled' => [
                'subject' => 'تم إلغاء الموعد (:reference)',
                'intro' => 'تم إلغاء الموعد التالي.',
            ],
            'other' => [
                'subject' => 'تغيّرت حالة الموعد (:reference)',
                'intro' => 'تم تغيير حالة الموعد التالي إلى «:status».',
            ],
        ],
    ],

    'labels' => [
        'reference' => 'رقم المرجع',
        'patient' => 'المريض',
        'doctor' => 'الطبيب',
        'service' => 'الخدمة',
        'date' => 'التاريخ',
        'time' => 'الوقت',
        'status' => 'الحالة',
        'any_doctor' => 'أي طبيب متاح',
    ],

    'statuses' => [
        'pending' => 'قيد الانتظار',
        'confirmed' => 'مؤكد',
        'completed' => 'مكتمل',
        'cancelled' => 'ملغى',
    ],

    'cta' => [
        'patient' => 'عرض مواعيدي',
        'doctor' => 'فتح جدولي',
    ],

    'contact' => 'لديك استفسار؟ اتصل بنا على :phone أو راسلنا على :email.',
    'footer_reason' => 'وصلتك هذه الرسالة بسبب موعد لدى :brand.',
];
