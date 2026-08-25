<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\SpeedReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RiderDeviceController extends Controller
{
    // Rider: get their paired device
    public function show(Request $request): JsonResponse
    {
        $device = $request->user()->device;

        if (! $device) {
            return $this->apiResponse(false, 'No device paired', null, 404);
        }

        return $this->apiResponse(true, 'Device retrieved', $device);
    }

    // Rider: pair a device by device code + its secret pairing key.
    // device_code is public (like a serial number) - pairing_key is the actual
    // secret that must match, so someone guessing/enumerating device codes
    // can't hijack a device they don't physically possess.
    public function pair(Request $request): JsonResponse
    {
        $request->validate([
            'device_code'      => ['required', 'string'],
            'pairing_key'      => ['required', 'string'],
            'sim_phone_number' => ['sometimes', 'nullable', 'string', 'max:20'],
        ]);

        $device = Device::where('device_code', $request->device_code)->first();

        if (! $device) {
            return $this->apiResponse(false, 'Device not found', null, 404);
        }

        if (strtoupper($request->pairing_key) !== strtoupper($device->pairing_key)) {
            return $this->apiResponse(false, 'Invalid pairing key', null, 422);
        }

        if ($device->rider_id !== null && $device->rider_id !== $request->user()->id) {
            return $this->apiResponse(false, 'Device is already paired to another rider', null, 409);
        }

        // Unpair any device previously assigned to this rider
        Device::where('rider_id', $request->user()->id)
            ->where('id', '!=', $device->id)
            ->update(['rider_id' => null, 'is_active' => false, 'paired_at' => null]);

        $update = [
            'rider_id'  => $request->user()->id,
            'is_active' => true,
            'paired_at' => now(),
        ];

        if ($request->filled('sim_phone_number')) {
            $update['sim_phone_number'] = $request->sim_phone_number;
        }

        $device->update($update);

        return $this->apiResponse(true, 'Device paired successfully', $device->fresh());
    }

    // Rider: unpair their device
    public function unpair(Request $request): JsonResponse
    {
        $device = $request->user()->device;

        if (! $device) {
            return $this->apiResponse(false, 'No device paired', null, 404);
        }

        $device->update([
            'rider_id'  => null,
            'is_active' => false,
            'paired_at' => null,
        ]);

        return $this->apiResponse(true, 'Device unpaired');
    }

    // IoT device: push battery level + active status, piggybacking the same
    // heartbeat with an optional GPS speed sample (lets Speed Reports per Area
    // aggregate real data without a second periodic call from the device).
    public function updateStatus(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_code'   => ['required', 'string'],
            'battery_level' => ['required', 'integer', 'between:0,100'],
            'is_active'     => ['sometimes', 'boolean'],
            'latitude'      => ['sometimes', 'numeric', 'between:-90,90'],
            'longitude'     => ['sometimes', 'numeric', 'between:-180,180'],
            'speed_kph'     => ['sometimes', 'integer', 'min:0'],
        ]);

        $device = Device::where('device_code', $data['device_code'])->first();

        if (! $device) {
            return $this->apiResponse(false, 'Device not found', null, 404);
        }

        $device->update($request->only('battery_level', 'is_active'));

        if (isset($data['latitude'], $data['longitude'], $data['speed_kph'])) {
            SpeedReport::create([
                'device_id'  => $device->id,
                'latitude'   => $data['latitude'],
                'longitude'  => $data['longitude'],
                'speed_kph'  => $data['speed_kph'],
            ]);
        }

        return $this->apiResponse(true, 'Status updated', $device->fresh());
    }
}
