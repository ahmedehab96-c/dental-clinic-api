<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClinicSettingResource;
use App\Models\ClinicSetting;
use Illuminate\Http\JsonResponse;

class ClinicSettingController extends Controller
{
    public function show(): JsonResponse
    {
        return $this->success(new ClinicSettingResource(ClinicSetting::current()));
    }
}
