<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
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

    // IoT device: push battery level + active status.
    //
    // The firmware also sends latitude, longitude and speed_kph on any
    // heartbeat where it has a satellite fix, and backend.cpp still says those
    // land in speed_reports. They do not, deliberately: speed reporting was
    // dropped because the PNP does not operate it, so the fields are ignored
    // here and the table is dead. Remove the sending from reportDeviceStatus()
    // rather than reviving this.
    public function updateStatus(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_code'   => ['required', 'string'],
            'battery_level' => ['required', 'integer', 'between:0,100'],
            'is_active'     => ['sometimes', 'boolean'],
        ]);

        $device = Device::where('device_code', $data['device_code'])->first();

        if (! $device) {
            return $this->apiResponse(false, 'Device not found', null, 404);
        }

        $device->update($request->only('battery_level', 'is_active'));

        return $this->apiResponse(true, 'Status updated', $device->fresh());
    }
}
