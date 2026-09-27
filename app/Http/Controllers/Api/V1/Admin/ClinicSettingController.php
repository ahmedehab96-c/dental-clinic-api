<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\ClinicSettingRequest;
use App\Http\Resources\ClinicSettingResource;
use App\Models\ClinicSetting;
use Illuminate\Http\JsonResponse;

class ClinicSettingController extends Controller
{
    public function update(ClinicSettingRequest $request): JsonResponse
    {
        $settings = ClinicSetting::current();
        $settings->update($request->validated());

        return $this->success(new ClinicSettingResource($settings));
    }
}
