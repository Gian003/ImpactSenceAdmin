<?php

use App\Models\Helmet;
use App\Models\Incident;
use App\Models\PatrolUnit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// ── PUBLIC ────────────────────────────────────────────────────────────────────
Route::get('/', fn () => view('welcome'));

// ── AUTH ──────────────────────────────────────────────────────────────────────
Route::get('/login', fn () => view('auth.login'))->name('login');

// ── FORGOT / RESET PASSWORD ────────────────────────────────────────────────────
Route::get('/forgot-password', fn () => view('auth.forgot-password'))
    ->name('password.request');

Route::post('/forgot-password', function (Request $request) {
    $request->validate(['email' => ['required', 'email']]);

    $email = $request->email;

    // Find the officer in either table
    $user  = \App\Models\TocPersonnel::where('email', $email)->first()
          ?? \App\Models\InvestigationOfficer::where('email', $email)->first();

    // Always show success — never reveal whether the email exists
    if ($user) {
        $token = \Illuminate\Support\Str::random(64);

        \Illuminate\Support\Facades\DB::table('password_reset_tokens')
            ->upsert(
                ['email' => $email, 'token' => \Illuminate\Support\Facades\Hash::make($token), 'created_at' => now()],
                ['email'],
                ['token', 'created_at']
            );

        $resetUrl = url('/reset-password/' . $token . '?email=' . urlencode($email));

        \Illuminate\Support\Facades\Mail::to($email)->send(
            new \App\Mail\ResetPasswordMail($user->full_name, $resetUrl)
        );
    }

    return back()->with('status', 'If that email exists in our system, a reset link has been sent.');
})->middleware('throttle:3,1')->name('password.email');

Route::get('/reset-password/{token}', function ($token) {
    return view('auth.reset-password', ['token' => $token]);
})->name('password.reset');

Route::post('/reset-password', function (Request $request) {
    $request->validate([
        'token'                 => ['required'],
        'email'                 => ['required', 'email'],
        'password'              => ['required', 'min:8', 'confirmed'],
        'password_confirmation' => ['required'],
    ]);

    $record = \Illuminate\Support\Facades\DB::table('password_reset_tokens')
        ->where('email', $request->email)
        ->first();

    // Validate token and expiry (60 minutes)
    if (! $record
        || ! \Illuminate\Support\Facades\Hash::check($request->token, $record->token)
        || now()->diffInMinutes($record->created_at) > 60
    ) {
        return back()->withErrors(['email' => 'The reset link is invalid or has expired.']);
    }

    // Update password in the correct table
    $updated = \App\Models\TocPersonnel::where('email', $request->email)
                   ->update(['password' => \Illuminate\Support\Facades\Hash::make($request->password)]);

    if (! $updated) {
        \App\Models\InvestigationOfficer::where('email', $request->email)
            ->update(['password' => \Illuminate\Support\Facades\Hash::make($request->password)]);
    }

    // Delete used token
    \Illuminate\Support\Facades\DB::table('password_reset_tokens')
        ->where('email', $request->email)
        ->delete();

    return redirect()->route('login')
        ->with('status', 'Password reset successfully. You can now log in.');
})->name('password.update');

Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email'    => ['required', 'email'],
        'password' => ['required'],
    ]);

    $remember = $request->boolean('remember');

    // Try TOC guard first
    if (Auth::guard('toc')->attempt($credentials, $remember)) {
        $request->session()->regenerate();
        return redirect()->intended(route('toc.dashboard'));
    }

    // Try Investigation guard
    if (Auth::guard('investigation')->attempt($credentials, $remember)) {
        $request->session()->regenerate();
        return redirect()->intended(route('investigation.dashboard'));
    }

    return back()->withErrors([
        'email' => 'The provided credentials do not match our records.',
    ])->onlyInput('email');
});

Route::post('/logout', function (Request $request) {
    Auth::guard('toc')->logout();
    Auth::guard('investigation')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect()->route('login');
})->name('logout');

// ── TOC — Tactical Operations Center ─────────────────────────────────────────
Route::prefix('toc')
    ->name('toc.')
    ->middleware('auth:toc')
    ->group(function () {
        Route::get('/dashboard', function () {
            return view('toc.dashboard.index', [
                'totalRiders'    => User::where('role', 'rider')->count(),
                'totalAccidents' => Incident::count(),
                'activeDevices'  => Helmet::where('is_active', true)->count(),
                'recentIncidents' => Incident::with('rider')->latest()->limit(5)->get(),
                'recentRiders'    => User::with('helmet')->where('role', 'rider')->latest()->limit(5)->get(),
            ]);
        })->name('dashboard');

        Route::get('/location-tracking', function () {
            // Speed Reports per Area — compares each police-defined speed
            // zone's posted limit against real observed GPS speed samples
            // that fall within it (see SpeedZone::distanceMeters). Done in
            // PHP rather than raw SQL trig so it behaves identically on
            // MySQL and SQLite.
            $zones        = \App\Models\SpeedZone::all();
            $speedSamples = \App\Models\SpeedReport::all(['latitude', 'longitude', 'speed_kph']);

            $speedZoneStats = $zones->map(function ($zone) use ($speedSamples) {
                $samplesInZone = $speedSamples->filter(
                    fn ($s) => \App\Models\SpeedZone::distanceMeters(
                        (float) $zone->latitude, (float) $zone->longitude,
                        (float) $s->latitude, (float) $s->longitude,
                    ) <= $zone->radius_meters
                );

                $avgSpeed = $samplesInZone->isNotEmpty()
                    ? (int) round($samplesInZone->avg('speed_kph'))
                    : null;

                return (object) [
                    'id'              => $zone->id,
                    'name'            => $zone->name,
                    'speed_limit_kph' => $zone->speed_limit_kph,
                    'sample_count'    => $samplesInZone->count(),
                    'avg_speed'       => $avgSpeed,
                    'is_violating'    => $avgSpeed !== null && $avgSpeed > $zone->speed_limit_kph,
                ];
            })->sortByDesc(fn ($z) => $z->is_violating ? 1 : 0)->values();

            // Accident Prone Area — real incidents ranked by density, grouped
            // into ~111m grid cells (as opposed to the heatmap below, which
            // plots every individual point rather than ranking areas).
            $incidentHotspots = \Illuminate\Support\Facades\DB::table('incidents')
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->selectRaw('ROUND(latitude, 3) as lat_group, ROUND(longitude, 3) as lng_group, COUNT(*) as incident_count, SUM(CASE WHEN severity IN (\'high\', \'critical\') THEN 1 ELSE 0 END) as severe_count')
                ->groupBy('lat_group', 'lng_group')
                ->orderByDesc('incident_count')
                ->limit(10)
                ->get();

            return view('toc.location.index', [
                'pendingIncidents' => Incident::with('rider')
                    ->whereIn('status', ['pending', 'dispatched'])
                    ->latest()->take(10)->get(),
                'patrollers' => PatrolUnit::all(),
                // All historical incident coordinates, used to plot the
                // accident-prone-area heatmap (as opposed to $pendingIncidents,
                // which only covers what's currently active).
                'allIncidentCoords' => Incident::whereNotNull('latitude')
                    ->whereNotNull('longitude')
                    ->get(['latitude', 'longitude']),
                'speedZoneStats'    => $speedZoneStats,
                'incidentHotspots'  => $incidentHotspots,
            ]);
        })->name('location.tracking');

        // ── Speed zones — police-maintained posted speed limits ────────────────
        Route::get('/speed-zones', function () {
            return view('toc.speed-zones.index', [
                'zones' => \App\Models\SpeedZone::with('creator')->latest()->get(),
            ]);
        })->name('speed-zones.index');

        Route::post('/speed-zones', function (Request $request) {
            $data = $request->validate([
                'name'             => ['required', 'string', 'max:255'],
                'latitude'         => ['required', 'numeric', 'between:-90,90'],
                'longitude'        => ['required', 'numeric', 'between:-180,180'],
                'radius_meters'    => ['required', 'integer', 'min:10', 'max:5000'],
                'speed_limit_kph'  => ['required', 'integer', 'min:1', 'max:200'],
            ]);

            \App\Models\SpeedZone::create($data + ['created_by' => Auth::guard('toc')->id()]);

            return back()->with('success', "Speed zone \"{$data['name']}\" added.");
        })->name('speed-zones.store');

        Route::delete('/speed-zones/{speedZone}', function (\App\Models\SpeedZone $speedZone) {
            $speedZone->delete();
            return back()->with('success', 'Speed zone removed.');
        })->name('speed-zones.destroy');

        Route::post('/incidents/{incident}/dispatch', function (
            Request $request, Incident $incident
        ) {
            $data = $request->validate([
                'patrol_unit_id' => ['required', 'exists:patrol_units,id'],
            ]);

            $patrol = PatrolUnit::find($data['patrol_unit_id']);

            $incident->update([
                'patrol_unit_id' => $patrol->id,
                'status'         => 'dispatched',
                'dispatched_at'  => now(),
            ]);

            // Patrol unit's own status was previously never updated on
            // dispatch, so it always read "off_duty" no matter what — the
            // Stand By / In Action split (and the map marker color) is only
            // meaningful once this actually flips.
            $patrol->update(['status' => 'dispatched']);

            $incident->load(['rider', 'patrolUnit']);

            try {
                broadcast(new \App\Events\PatrolDispatched($incident));
                broadcast(new \App\Events\IncidentStatusUpdated($incident));
                broadcast(new \App\Events\PatrolLocationUpdated($patrol));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Pusher broadcast failed (dispatch)', ['error' => $e->getMessage()]);
            }

            app(\App\Services\FcmService::class)->notifyPatrol(
                $patrol,
                'Dispatch Alert',
                "Respond to {$incident->type} at {$incident->address}",
                ['incident_id' => (string) $incident->id, 'type' => 'dispatch']
            );

            return back()->with('dispatched', "Patrol {$patrol->full_name} dispatched.");
        })->name('incidents.dispatch');

        Route::get('/helmet', function () {
            return view('toc.helmet.index', [
                'riders' => User::with('helmet')->where('role', 'rider')->latest()->get(),
            ]);
        })->name('helmet.index');

        // ── Patrol registrations ──────────────────────────────────────────────
        Route::get('/patrol-registrations', function () {
            return view('toc.patrol-registrations.index', [
                'pending'  => \App\Models\PatrolRegistration::where('status', 'pending')->latest()->get(),
                'reviewed' => \App\Models\PatrolRegistration::whereIn('status', ['approved','rejected'])
                                  ->with('reviewer')->latest()->limit(20)->get(),
            ]);
        })->name('patrol-registrations.index');

        Route::post('/patrol-registrations/{registration}/approve', function (
            Request $request, \App\Models\PatrolRegistration $registration
        ) {
            $data = $request->validate([
                'badge_number' => ['required', 'string', 'unique:patrol_units,badge_number'],
                'rank'         => ['required', 'string'],
            ]);

            if ($registration->status !== 'pending') {
                return back()->withErrors(['error' => 'Registration already reviewed.']);
            }

            // Create the patrol unit account
            $patrol = \App\Models\PatrolUnit::create([
                'full_name'    => $registration->first_name . ' ' . $registration->last_name,
                'badge_number' => $data['badge_number'],
                'email'        => $registration->email,
                'password'     => $registration->password, // already hashed
                'rank'         => $data['rank'],
                'mobile_number'=> $registration->phone_number,
                'fcm_token'    => $registration->fcm_token,
                'status'       => 'off_duty',
            ]);

            // Mark registration approved
            $registration->update([
                'status'      => 'approved',
                'badge_number'=> $data['badge_number'],
                'rank'        => $data['rank'],
                'reviewed_by' => Auth::guard('toc')->id(),
                'reviewed_at' => now(),
            ]);

            // FCM push to the patrol officer's phone
            if ($registration->fcm_token) {
                app(\App\Services\FcmService::class)->sendToToken(
                    $registration->fcm_token,
                    'Registration Approved',
                    'Your patrol account has been approved. You can now log in.',
                    ['type' => 'registration_approved', 'badge_number' => $data['badge_number']]
                );
            }

            return back()->with('success', "Account created for {$patrol->full_name} ({$patrol->badge_number}).");
        })->name('patrol-registrations.approve');

        Route::post('/patrol-registrations/{registration}/reject', function (
            Request $request, \App\Models\PatrolRegistration $registration
        ) {
            $data = $request->validate([
                'rejection_reason' => ['required', 'string', 'max:500'],
            ]);

            if ($registration->status !== 'pending') {
                return back()->withErrors(['error' => 'Registration already reviewed.']);
            }

            $registration->update([
                'status'           => 'rejected',
                'rejection_reason' => $data['rejection_reason'],
                'reviewed_by'      => Auth::guard('toc')->id(),
                'reviewed_at'      => now(),
            ]);

            // FCM push to the patrol officer
            if ($registration->fcm_token) {
                app(\App\Services\FcmService::class)->sendToToken(
                    $registration->fcm_token,
                    'Registration Not Approved',
                    'Reason: ' . $data['rejection_reason'],
                    ['type' => 'registration_rejected']
                );
            }

            return back()->with('success', "Registration for {$registration->full_name} rejected.");
        })->name('patrol-registrations.reject');

        Route::get('/patrollers', function () {
            return view('toc.patrollers.index', [
                'patrollers' => PatrolUnit::all(),
            ]);
        })->name('patrollers.index');
    });

// ── INVESTIGATION — Investigation────────────────────────────────────────
Route::prefix('investigation')
    ->name('investigation.')
    ->middleware('auth:investigation')
    ->group(function () {
        Route::get('/dashboard', function () {
            return view('investigation.dashboard.index', [
                'totalRiders'    => User::where('role', 'rider')->count(),
                'totalAccidents' => Incident::count(),
                'activeDevices'  => Helmet::where('is_active', true)->count(),
                'recentIncidents' => Incident::with('rider')->latest()->limit(5)->get(),
                'recentRiders'    => User::with('helmet')->where('role', 'rider')->latest()->limit(5)->get(),
            ]);
        })->name('dashboard');
        Route::get('/incidents', function () {
            return view('investigation.incidents.index', [
                'incidents' => Incident::with('rider')->latest()->get(),
            ]);
        })->name('incidents.index');
        // Latest 200 incidents for the "Link to Incident" picker on the IRF
        // form — shared by all three routes that render that form below.
        $irfIncidentOptions = fn () => Incident::with('rider')->latest()->limit(200)->get();

        // Blank, fast-to-fill IRF — the primary way investigators reach this
        // page, so it opens straight to the form rather than a list to click
        // through first.
        Route::get('/incident-records', fn () => view('investigation.incident-records.index', [
            'incident'  => null,
            'incidents' => $irfIncidentOptions(),
        ]))->name('incident-records.index');

        // Every saved Incident Record Form, linked or not — so a walk-in
        // report saved with no incident attached is still findable somewhere
        // instead of only ever being visible via a specific incident's
        // report page. Also registered before the {incident} wildcard.
        Route::get('/incident-records/all', function () {
            return view('investigation.incident-records.all', [
                'records' => \App\Models\IncidentRecord::with(['incident.rider', 'generatedBy'])->latest()->get(),
            ]);
        })->name('incident-records.all');

        // Reopens a previously-generated IRF with everything that was typed
        // in restored — registered before the {incident} wildcard below so
        // "history" isn't swallowed as an incident ID. Linked from the
        // "Generated Incident Records" list on the Incident Report page.
        Route::get('/incident-records/history/{incidentRecord}', function (\App\Models\IncidentRecord $incidentRecord) use ($irfIncidentOptions) {
            return view('investigation.incident-records.index', [
                'incident'  => $incidentRecord->incident?->load(['rider', 'patrolUnit']),
                'savedData' => $incidentRecord->data,
                'recordId'  => $incidentRecord->id,
                'incidents' => $irfIncidentOptions(),
            ]);
        })->name('incident-records.reprint');

        // Same form, prefilled from a real incident — reached via "Generate"
        // links on the Incidents list / Incident Report page.
        Route::get('/incident-records/{incident}', function (Incident $incident) use ($irfIncidentOptions) {
            $incident->load(['rider', 'patrolUnit']);
            return view('investigation.incident-records.index', [
                'incident'  => $incident,
                'incidents' => $irfIncidentOptions(),
            ]);
        })->name('incident-records.show');

        // Persists whatever was typed into the IRF when "Save" or "Save &
        // Print" is pressed, so it actually shows up on the Incident Report
        // page instead of only ever existing as a printout. incident_id is
        // optional — the form can be filled out for a walk-in report that
        // isn't linked to any incident in the system.
        //
        // record_id, when present, means this is a reopened ("reprint")
        // record being saved again — updates that same row instead of
        // creating a duplicate. printed_at is only ever set, never cleared:
        // once a record has been printed at least once, re-saving without
        // printing shouldn't erase that history.
        Route::post('/incident-records', function (Request $request) {
            $data = $request->except(['_token', 'incident_id', 'record_id', 'printed']);
            $printed = $request->boolean('printed');

            $record = $request->filled('record_id')
                ? \App\Models\IncidentRecord::find($request->input('record_id'))
                : null;

            $attributes = [
                'incident_id'  => $request->input('incident_id') ?: null,
                'generated_by' => Auth::guard('investigation')->id(),
                'data'         => $data,
            ];
            if ($printed) {
                $attributes['printed_at'] = now();
            }

            if ($record) {
                $record->update($attributes);
            } else {
                $record = \App\Models\IncidentRecord::create($attributes);
            }

            return response()->json(['success' => true, 'id' => $record->id]);
        })->name('incident-records.store');

        Route::get('/incident-report', function () {
            return view('investigation.incident-report.index', [
                'incidents' => Incident::with('rider')->latest()->get(),
            ]);
        })->name('incident-report.index');

        Route::get('/incident-report/{incident}', function (Incident $incident) {
            $incident->load(['rider', 'patrolUnit', 'helmet', 'incidentRecords.generatedBy']);
            return view('investigation.incident-report.show', [
                'incident'     => $incident,
                'incidentRecords' => $incident->incidentRecords->sortByDesc('created_at'),
                'fullName'     => $incident->rider?->full_name     ?? 'N/A',
                'datetime'     => $incident->created_at->format('F d, h:i A'),
                'coordinates'  => '('.$incident->latitude.'° N, '.$incident->longitude.'° E)',
                'reportedBy'   => $incident->patrolUnit?->full_name ?? 'TOC System',
                'unit'         => $incident->patrolUnit?->badge_number ?? 'N/A',
                'description'  => ucfirst($incident->type).' incident reported at '.$incident->address.'.',
                'vehicles'     => 1,
                'injured'      => 1,
                'severity'     => ucfirst($incident->severity),
                'roadCondition'=> 'N/A',
                'weather'      => 'N/A',
                'location'     => $incident->address ?? 'N/A',
                'lat'          => (float) $incident->latitude,
                'lng'          => (float) $incident->longitude,
                'timeline'     => collect([
                    ['time' => $incident->created_at->format('h:i A'),      'description' => 'Incident reported'],
                    ['time' => $incident->dispatched_at?->format('h:i A') ?? '—', 'description' => 'Patrol dispatched'],
                    ['time' => $incident->resolved_at?->format('h:i A')   ?? '—', 'description' => 'Incident resolved'],
                ])->filter(fn($e) => $e['time'] !== '—'),
            ]);
        })->name('incident-report.show');
        Route::get('/helmet', function () {
            return view('investigation.helmet.index', [
                'riders' => User::with('helmet')->where('role', 'rider')->latest()->get(),
            ]);
        })->name('helmet.index');
    });
