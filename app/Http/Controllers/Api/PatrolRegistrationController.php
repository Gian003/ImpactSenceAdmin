<?php

namespace App\Http\Controllers\Api;

use App\Events\PatrolRegistrationSubmitted;
use App\Http\Controllers\Controller;
use App\Models\PatrolRegistration;
use App\Models\PersonnelRoster;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatrolRegistrationController extends Controller
{
    /**
     * Patrol officer submits a registration request.
     * Public — no Sanctum token needed yet.
     *
     * badge_number and the registration photo are now required and checked
     * at submission time, not typed in blind by the TOC admin at approval
     * time: badge_number must match an active personnel_roster entry (the
     * authoritative record TOC staff populate from real PNP Urdaneta
     * personnel data, separately from this endpoint), and must not already
     * be claimed by an existing patrol account or another pending request.
     * The photo is compared by TOC staff against the roster entry's
     * reference photo during review — see toc/patrol-registrations.
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
            'badge_number'          => ['required', 'string', 'max:50',
                                        'unique:patrol_units,badge_number',
                                        'unique:patrol_registrations,badge_number'],
            'photo'                 => ['required', 'image', 'max:5120'], // 5MB
            'fcm_token'             => ['nullable', 'string'],
        ]);

        $roster = PersonnelRoster::active()
            ->where('badge_number', $data['badge_number'])
            ->first();

        if (! $roster) {
            return $this->apiResponse(
                false,
                'This badge number is not on file with TOC. Contact your supervisor to confirm it before registering.',
                null,
                422
            );
        }

        $photoPath = $request->file('photo')->store('patrol-registration-photos', 'public');

        $registration = PatrolRegistration::create([
            ...collect($data)->except(['photo'])->all(),
            // Rank comes from the roster, not the applicant — same principle
            // as badge_number: authoritative source, not self-reported.
            'rank'       => $roster->rank,
            'photo_path' => $photoPath,
        ]);

        // Broadcast to TOC dashboard so they see the badge immediately — non-fatal
        try {
            broadcast(new PatrolRegistrationSubmitted($registration));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Pusher broadcast failed (PatrolRegistrationSubmitted)', ['error' => $e->getMessage()]);
        }

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
