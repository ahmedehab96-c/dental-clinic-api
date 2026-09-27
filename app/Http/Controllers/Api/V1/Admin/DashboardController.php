<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    /**
     * Cheap aggregate counts for the admin overview page — six plain
     * COUNT queries rather than the frontend paginating through every
     * list just to read a total.
     */
    public function index(): JsonResponse
    {
        return $this->success([
            'total_patients' => User::where('role', 'patient')->count(),
            'total_doctors' => Doctor::count(),
            'total_services' => Service::count(),
            'today_appointments' => Appointment::whereDate('date', today())->count(),
            'pending_appointments' => Appointment::where('status', 'pending')->count(),
            'confirmed_appointments' => Appointment::where('status', 'confirmed')->count(),
        ]);
    }
}
