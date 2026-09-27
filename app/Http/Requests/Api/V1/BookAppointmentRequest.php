<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class BookAppointmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Guest booking is intentional — matches the current React flow,
        // which never requires login to book an appointment.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'doctor_id' => ['nullable', 'integer', 'exists:doctors,id'],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'time' => ['required', 'date_format:H:i'],
            'patient_name' => ['required', 'string', 'min:2', 'max:255'],
            'patient_phone' => ['required', 'regex:/^(\+?966|0)?5\d{8}$/'],
            'patient_email' => ['required', 'email', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
