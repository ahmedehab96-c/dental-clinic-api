<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class AppointmentResource extends ApiResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'status' => $this->status,
            'service' => [
                'id' => $this->service->id,
                'slug' => $this->service->slug,
            ],
            'doctor' => $this->doctor ? [
                'id' => $this->doctor->id,
                'slug' => $this->doctor->slug,
            ] : null,
            'date' => $this->date->format('Y-m-d'),
            'time' => substr((string) $this->time, 0, 5),
            'patient' => [
                'name' => $this->patient_name,
                'phone' => $this->patient_phone,
                'email' => $this->patient_email,
            ],
            'notes' => $this->notes,
            // Only present for admins, who load this relation explicitly —
            // the guest/patient booking response never loads it.
            'user_id' => $this->whenLoaded('user', fn () => $this->user?->id),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
