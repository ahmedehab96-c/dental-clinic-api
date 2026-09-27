<?php

namespace App\Http\Controllers\Api\V1\Doctor;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Aggregate counts for the doctor overview — every query starts from the
     * authenticated doctor's own appointments relation, so another doctor's
     * data can never leak into these numbers.
     */
    public function index(Request $request): JsonResponse
    {
        $doctor = $request->user()->doctor;

        if (! $doctor) {
            return $this->error('No doctor profile is linked to this account yet.', 404);
        }

        $byStatus = $doctor->appointments()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $today = today()->toDateString();

        return $this->success([
            'total_appointments' => (int) $byStatus->sum(),
            'today_appointments' => $doctor->appointments()->whereDate('date', $today)->count(),
            'upcoming_appointments' => $doctor->appointments()
                ->whereDate('date', '>', $today)
                ->whereIn('status', ['pending', 'confirmed'])
                ->count(),
            'pending_appointments' => (int) ($byStatus['pending'] ?? 0),
            'confirmed_appointments' => (int) ($byStatus['confirmed'] ?? 0),
            'completed_appointments' => (int) ($byStatus['completed'] ?? 0),
            'cancelled_appointments' => (int) ($byStatus['cancelled'] ?? 0),
            'services_count' => $doctor->services()->count(),
        ]);
    }
}
