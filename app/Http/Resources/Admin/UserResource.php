<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\ApiResource;
use Illuminate\Http\Request;

/**
 * Admin-facing account shape. Explicit allow-list — password hashes,
 * remember tokens and Sanctum tokens are never part of it.
 */
class UserResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'appointments_count' => $this->whenCounted('appointments'),
            'recent_appointments' => $this->whenLoaded('appointments', fn () => $this->appointments->map(fn ($appointment) => [
                'id' => $appointment->id,
                'reference' => $appointment->reference,
                'status' => $appointment->status,
                'date' => $appointment->date->format('Y-m-d'),
                'time' => substr((string) $appointment->time, 0, 5),
                'service' => ['ar' => $appointment->service->name_ar, 'en' => $appointment->service->name_en],
                'doctor' => $appointment->doctor
                    ? ['ar' => $appointment->doctor->name_ar, 'en' => $appointment->doctor->name_en]
                    : null,
            ])),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
