<?php

namespace App\Http\Controllers\Api;

use App\Events\PatrolRegistrationSubmitted;
use App\Http\Controllers\Controller;
use App\Models\PatrolRegistration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatrolRegistrationController extends Controller
{
    /**
     * Patrol officer submits a registration request.
     * Public — no Sanctum token needed yet.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'first_name'            => ['required', 'string', 'max:100'],
            'last_name'             => ['required', 'string', 'max:100'],
            'email'                 => ['required', 'email', 'unique:patrol_registrations,email',
                                        'unique:patrol_units,email'],
            'phone_number'          => ['nullable', 'string', 'max:20'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
            'fcm_token'             => ['nullable', 'string'],
        ]);

        $registration = PatrolRegistration::create($data);

        // Broadcast to TOC dashboard so they see the badge immediately
        broadcast(new PatrolRegistrationSubmitted($registration));

        return $this->apiResponse(true,
            'Registration submitted. Please wait for TOC admin approval.',
            ['status' => 'pending', 'id' => $registration->id],
            201
        );
    }

    /**
     * Patrol officer polls their registration status.
     */
    public function status(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $reg = PatrolRegistration::where('email', $request->email)->first();

        if (! $reg) {
            return $this->apiResponse(false, 'No registration found for this email.', null, 404);
        }

        return $this->apiResponse(true, 'Status retrieved', [
            'status'           => $reg->status,
            'rejection_reason' => $reg->rejection_reason,
        ]);
    }
}
