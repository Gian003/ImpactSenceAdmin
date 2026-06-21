<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Helmet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HelmetController extends Controller
{
    // Rider: get their paired helmet
    public function show(Request $request): JsonResponse
    {
        $helmet = $request->user()->helmet;

        if (! $helmet) {
            return $this->apiResponse(false, 'No helmet paired', null, 404);
        }

        return $this->apiResponse(true, 'Helmet retrieved', $helmet);
    }

    // Rider: pair a helmet by device code
    public function pair(Request $request): JsonResponse
    {
        $request->validate([
            'device_code' => ['required', 'string'],
        ]);

        $helmet = Helmet::where('device_code', $request->device_code)->first();

        if (! $helmet) {
            return $this->apiResponse(false, 'Device not found', null, 404);
        }

        if ($helmet->rider_id !== null && $helmet->rider_id !== $request->user()->id) {
            return $this->apiResponse(false, 'Device is already paired to another rider', null, 409);
        }

        // Unpair any helmet previously assigned to this rider
        Helmet::where('rider_id', $request->user()->id)
            ->where('id', '!=', $helmet->id)
            ->update(['rider_id' => null, 'is_active' => false, 'paired_at' => null]);

        $helmet->update([
            'rider_id'  => $request->user()->id,
            'is_active' => true,
            'paired_at' => now(),
        ]);

        return $this->apiResponse(true, 'Helmet paired successfully', $helmet->fresh());
    }

    // Rider: unpair their helmet
    public function unpair(Request $request): JsonResponse
    {
        $helmet = $request->user()->helmet;

        if (! $helmet) {
            return $this->apiResponse(false, 'No helmet paired', null, 404);
        }

        $helmet->update([
            'rider_id'  => null,
            'is_active' => false,
            'paired_at' => null,
        ]);

        return $this->apiResponse(true, 'Helmet unpaired');
    }

    // IoT device: push battery level + active status
    public function updateStatus(Request $request): JsonResponse
    {
        $request->validate([
            'device_code'   => ['required', 'string'],
            'battery_level' => ['required', 'integer', 'between:0,100'],
            'is_active'     => ['sometimes', 'boolean'],
        ]);

        $helmet = Helmet::where('device_code', $request->device_code)->first();

        if (! $helmet) {
            return $this->apiResponse(false, 'Device not found', null, 404);
        }

        $helmet->update($request->only('battery_level', 'is_active'));

        return $this->apiResponse(true, 'Status updated', $helmet->fresh());
    }
}
