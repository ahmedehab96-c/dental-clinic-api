<?php

return [
    'brand' => 'Radiant Dental Care',
    'greeting' => 'Hello :name,',
    'greeting_guest' => 'Hello,',

    'created' => [
        'patient' => [
            'subject' => 'We received your appointment request (:reference)',
            'intro' => 'Thank you for booking with us. Your appointment request has been received and is pending confirmation — we will let you know as soon as it is confirmed.',
        ],
        'doctor' => [
            'subject' => 'New appointment assigned to you (:reference)',
            'intro' => 'A new appointment has been booked with you. Here are the details:',
        ],
    ],

    'status' => [
        'patient' => [
            'confirmed' => [
                'subject' => 'Your appointment is confirmed (:reference)',
                'intro' => 'Good news — your appointment has been confirmed. We look forward to seeing you.',
            ],
            'completed' => [
                'subject' => 'Thank you for your visit (:reference)',
                'intro' => 'Your appointment has been marked as completed. Thank you for choosing us — we hope to see you again.',
            ],
            'cancelled' => [
                'subject' => 'Your appointment was cancelled (:reference)',
                'intro' => 'Your appointment has been cancelled. If you did not expect this, or would like to book a new time, please contact us.',
            ],
            'other' => [
                'subject' => 'Your appointment was updated (:reference)',
                'intro' => 'The status of your appointment has been updated to “:status”.',
            ],
        ],
        'doctor' => [
            'cancelled_by_patient' => [
                'subject' => 'Appointment cancelled by the patient (:reference)',
                'intro' => 'The patient has cancelled the appointment below. The time slot is now free.',
            ],
            'cancelled' => [
                'subject' => 'Appointment cancelled (:reference)',
                'intro' => 'The appointment below has been cancelled.',
            ],
            'other' => [
                'subject' => 'Appointment status changed (:reference)',
                'intro' => 'The status of the appointment below has been changed to “:status”.',
            ],
        ],
    ],

    'labels' => [
        'reference' => 'Reference',
        'patient' => 'Patient',
        'doctor' => 'Doctor',
        'service' => 'Service',
        'date' => 'Date',
        'time' => 'Time',
        'status' => 'Status',
        'any_doctor' => 'Any available doctor',
    ],

    'statuses' => [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],

    'cta' => [
        'patient' => 'View my appointments',
        'doctor' => 'Open my schedule',
    ],

    'contact' => 'Questions? Call us at :phone or email :email.',
    'footer_reason' => 'You are receiving this email because of an appointment at :brand.',
];
