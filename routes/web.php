<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InvitationController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Models\Device;
use App\Models\Incident;
use App\Models\PatrolUnit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// ── PUBLIC ────────────────────────────────────────────────────────────────────
Route::get('/', function () {
    return view('welcome', [
        'statRiders'  => \App\Models\User::where('role', 'rider')->count(),
        'statAccidents' => \App\Models\Incident::count(),
        'statDevices' => \App\Models\Device::where('is_active', true)->count(),
    ]);
});

// ── SUPERADMIN ────────────────────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->group(function () {

    // Public — login + invitation accept
    Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout',[AuthController::class, 'logout'])->name('logout');

    Route::get('/invite/{token}',  [InvitationController::class, 'showAccept'])->name('invitations.accept');
    Route::post('/invite/{token}', [InvitationController::class, 'accept']);

    // Protected — require admin guard
    Route::middleware('admin')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/',                                [UserManagementController::class, 'index'])->name('index');
            Route::patch('/toc/{officer}/toggle',          [UserManagementController::class, 'toggleToc'])->name('toggle-toc');
            Route::patch('/investigation/{officer}/toggle',[UserManagementController::class, 'toggleInvestigation'])->name('toggle-investigation');
        });

        Route::prefix('invitations')->name('invitations.')->group(function () {
            Route::get('/',           [InvitationController::class, 'index'])->name('index');
            Route::post('/',          [InvitationController::class, 'store'])->name('store');
            Route::delete('/{invitation}', [InvitationController::class, 'destroy'])->name('destroy');
        });
    });
});

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
                'accidentsToday' => Incident::whereDate('created_at', today())->count(),
                'activeDevices'  => Device::where('is_active', true)->count(),
                'pendingPatrolRegistrations' => \App\Models\PatrolRegistration::where('status', 'pending')->count(),
                'recentIncidents' => Incident::with('rider')->latest()->limit(5)->get(),
                'recentRiders'    => User::with('device')->where('role', 'rider')->latest()->limit(5)->get(),
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

            // Ties each row to the High/Average/Low legend the panel already
            // shows (previously decorative — it never reflected the actual
            // rows). Relative to the busiest area in *this* result set rather
            // than a hardcoded incident count, so the tiers stay meaningful
            // whether the city's had 3 accidents this month or 300.
            $maxHotspotCount = $incidentHotspots->max('incident_count') ?: 1;
            $incidentHotspots = $incidentHotspots->map(function ($h) use ($maxHotspotCount) {
                $ratio = $h->incident_count / $maxHotspotCount;
                $h->tier = $ratio >= 0.66 ? 'high' : ($ratio >= 0.33 ? 'average' : 'low');
                return $h;
            });

            return view('toc.location.index', [
                'pendingIncidents' => Incident::with(['rider', 'patrolUnit'])
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
            $zones        = \App\Models\SpeedZone::with('creator')->latest()->get();
            $speedSamples = \App\Models\SpeedReport::all(['latitude', 'longitude', 'speed_kph']);

            $speedZoneStats = $zones->mapWithKeys(function ($zone) use ($speedSamples) {
                $samplesInZone = $speedSamples->filter(
                    fn ($s) => \App\Models\SpeedZone::distanceMeters(
                        (float) $zone->latitude, (float) $zone->longitude,
                        (float) $s->latitude,    (float) $s->longitude,
                    ) <= $zone->radius_meters
                );

                $avgSpeed = $samplesInZone->isNotEmpty()
                    ? (int) round($samplesInZone->avg('speed_kph'))
                    : null;

                return [$zone->id => (object) [
                    'avg_speed'    => $avgSpeed,
                    'sample_count' => $samplesInZone->count(),
                    'is_violating' => $avgSpeed !== null && $avgSpeed > $zone->speed_limit_kph,
                ]];
            });

            return view('toc.speed-zones.index', [
                'zones'          => $zones,
                'speedZoneStats' => $speedZoneStats,
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

        Route::get('/devices', function () {
            $days    = 7;
            $trend   = function ($query, $col = 'created_at') use ($days) {
                $raw = $query
                    ->selectRaw("DATE($col) as day, COUNT(*) as total")
                    ->where($col, '>=', now()->subDays($days - 1)->startOfDay())
                    ->groupBy('day')
                    ->pluck('total', 'day');
                $out = [];
                for ($i = $days - 1; $i >= 0; $i--) {
                    $out[] = (int) ($raw[now()->subDays($i)->format('Y-m-d')] ?? 0);
                }
                return $out;
            };

            $labels = collect(range($days - 1, 0))->map(
                fn($i) => now()->subDays($i)->format('D')
            )->values()->all();

            return view('toc.devices.index', [
                // withCount('incidents') on the device relation — a device
                // racking up an unusual number of incidents (especially
                // false_alarm-flagged ones) is a hardware/calibration signal
                // worth flagging, independent of which rider has it paired.
                'riders'          => User::with(['device' => fn ($q) => $q->withCount('incidents')])
                    ->where('role', 'rider')->latest()->get(),
                'unlinkedDevices' => Device::whereNull('rider_id')->latest()->get(),
                'totalRiders'     => User::where('role', 'rider')->count(),
                'totalDevices'    => Device::count(),
                'activeDevices'   => Device::where('is_active', true)->count(),
                'pairedDevices'   => Device::whereNotNull('paired_at')->count(),
                'chartLabels'     => $labels,
                'trendRiders'     => $trend(User::where('role', 'rider')),
                'trendDevices'    => $trend(Device::query()),
                'trendIncidents'  => $trend(Incident::query()),
            ]);
        })->name('devices.index');

        // Register a new physical device (TOC admin)
        Route::post('/devices', function (Request $request) {
            $data = $request->validate([
                'device_code'      => ['required', 'string', 'max:50', 'unique:devices,device_code'],
                'model'            => ['nullable', 'string', 'max:100'],
                'firmware_version' => ['nullable', 'string', 'max:20'],
            ]);

            Device::create($data);

            return redirect()->route('toc.devices.index')
                ->with('success', 'Device registered. Share the auto-generated pairing key with the rider.');
        })->name('devices.store');

        // ── Patrol registrations ──────────────────────────────────────────────
        Route::get('/patrol-registrations', function () {
            return view('toc.patrol-registrations.index', [
                'pending'  => \App\Models\PatrolRegistration::where('status', 'pending')->with('roster')->latest()->get(),
                'reviewed' => \App\Models\PatrolRegistration::whereIn('status', ['approved','rejected'])
                                  ->with('reviewer')->latest()->limit(20)->get(),
            ]);
        })->name('patrol-registrations.index');

        Route::post('/patrol-registrations/{registration}/approve', function (
            Request $request, \App\Models\PatrolRegistration $registration
        ) {
            if ($registration->status !== 'pending') {
                return back()->withErrors(['error' => 'Registration already reviewed.']);
            }

            // badge_number and rank were already validated against
            // personnel_roster at registration time (PatrolRegistrationController::store)
            // — nothing left for the admin to type in here, only confirm.
            // Still re-check uniqueness against patrol_units in case another
            // registration for the same badge somehow got approved first.
            if (\App\Models\PatrolUnit::where('badge_number', $registration->badge_number)->exists()) {
                return back()->withErrors(['error' => 'This badge number already has an active patrol account.']);
            }

            // Create the patrol unit account
            $patrol = \App\Models\PatrolUnit::create([
                'full_name'    => $registration->first_name . ' ' . $registration->last_name,
                'badge_number' => $registration->badge_number,
                'email'        => $registration->email,
                'password'     => $registration->password, // already hashed
                'rank'         => $registration->rank,
                'mobile_number'=> $registration->phone_number,
                'fcm_token'    => $registration->fcm_token,
                'status'       => 'off_duty',
            ]);

            // Mark registration approved
            $registration->update([
                'status'      => 'approved',
                'reviewed_by' => Auth::guard('toc')->id(),
                'reviewed_at' => now(),
            ]);

            // FCM push to the patrol officer's phone
            if ($registration->fcm_token) {
                app(\App\Services\FcmService::class)->sendToToken(
                    $registration->fcm_token,
                    'Registration Approved',
                    'Your patrol account has been approved. You can now log in.',
                    ['type' => 'registration_approved', 'badge_number' => $registration->badge_number]
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

        // ── Personnel Roster ────────────────────────────────────────────────
        // The authoritative list new patrol registrations are checked
        // against (see PatrolRegistrationController::store) — populated here
        // by TOC staff from real PNP Urdaneta personnel records, kept
        // separate from the registration flow itself.
        Route::get('/personnel-roster', function () {
            return view('toc.personnel-roster.index', [
                'roster' => \App\Models\PersonnelRoster::latest()->get(),
                'ranks'  => \App\Models\PersonnelRoster::RANKS,
            ]);
        })->name('personnel-roster.index');

        Route::post('/personnel-roster', function (Request $request) {
            $data = $request->validate([
                'badge_number' => ['required', 'string', 'max:50', 'unique:personnel_roster,badge_number'],
                'full_name'    => ['required', 'string', 'max:150'],
                'rank'         => ['required', 'string', \Illuminate\Validation\Rule::in(\App\Models\PersonnelRoster::RANKS)],
                'photo'        => ['nullable', 'image', 'max:5120'],
            ]);

            if ($request->hasFile('photo')) {
                $data['reference_photo_path'] = $request->file('photo')->store('personnel-roster-photos', 'public');
            }
            unset($data['photo']);

            \App\Models\PersonnelRoster::create($data);

            return back()->with('success', "{$data['full_name']} added to the personnel roster.");
        })->name('personnel-roster.store');

        Route::post('/personnel-roster/{roster}/toggle', function (\App\Models\PersonnelRoster $roster) {
            $roster->update(['is_active' => ! $roster->is_active]);
            return back()->with('success', $roster->full_name . ($roster->is_active ? ' reactivated.' : ' deactivated.'));
        })->name('personnel-roster.toggle');

        Route::get('/patrollers', function () {
            return view('toc.patrollers.index', [
                'patrollers' => PatrolUnit::all(),
            ]);
        })->name('patrollers.index');

        // ── Accident Analytics ────────────────────────────────────────────
        Route::get('/analytics', function () {
            $total = Incident::count();

            $pronestAreas = Incident::select('address', DB::raw('count(*) as total'))
                ->whereNotNull('address')->where('address', '!=', '')
                ->groupBy('address')->orderByDesc('total')->limit(10)->get();

            $byMonth = Incident::select(
                    DB::raw("DATE_FORMAT(created_at,'%Y-%m') as month"),
                    DB::raw('count(*) as total')
                )
                ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
                ->groupBy('month')->orderBy('month')->get();

            $bySeverity = Incident::select('severity', DB::raw('count(*) as total'))
                ->groupBy('severity')->get();

            $byDayOfWeek = Incident::select(
                    DB::raw('DAYOFWEEK(created_at) as day'),
                    DB::raw('count(*) as total')
                )
                ->groupBy('day')->orderBy('day')->get()->keyBy('day');

            $byHour = Incident::select(
                    DB::raw('HOUR(created_at) as hour'),
                    DB::raw('count(*) as total')
                )
                ->groupBy('hour')->orderBy('hour')->get()->keyBy('hour');

            $heatmapPoints = Incident::select('latitude', 'longitude', 'severity', 'type', 'address', 'created_at')
                ->whereNotNull('latitude')->whereNotNull('longitude')->get();

            $criticalCount  = $bySeverity->where('severity', 'critical')->sum('total');
            $resolvedCount  = Incident::where('status', 'resolved')->count();

            return view('toc.analytics.index', compact(
                'total', 'pronestAreas', 'byMonth', 'bySeverity',
                'byDayOfWeek', 'byHour', 'heatmapPoints', 'criticalCount', 'resolvedCount'
            ));
        })->name('analytics.index');
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
                'accidentsToday' => Incident::whereDate('created_at', today())->count(),
                'activeDevices'  => Device::where('is_active', true)->count(),
                'recentIncidents' => Incident::with('rider')->latest()->limit(5)->get(),
                'recentRiders'    => User::with('device')->where('role', 'rider')->latest()->limit(5)->get(),
            ]);
        })->name('dashboard');
        Route::get('/incidents', function () {
            $incidents = Incident::with('rider')->latest()->get();
            // Distinct months present in the data for the date filter dropdown
            $incidentMonths = $incidents
                ->pluck('created_at')
                ->map(fn ($d) => $d->format('F'))
                ->unique()
                ->values();
            return view('investigation.incidents.index', [
                'incidents'      => $incidents,
                'incidentMonths' => $incidentMonths,
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

        // Real PDF instead of relying on the browser's own Print dialog to
        // turn the HTML page into a printout — same view, same JS prefill
        // (Browsershot runs actual Chromium, so the savedData-filling script
        // executes exactly like it would in a normal browser tab), just
        // captured server-side into a document with consistent output
        // regardless of whoever's browser/printer is involved.
        Route::get('/incident-records/{incidentRecord}/pdf', function (\App\Models\IncidentRecord $incidentRecord) use ($irfIncidentOptions) {
            $html = view('investigation.incident-records.index', [
                'incident'  => $incidentRecord->incident?->load(['rider', 'patrolUnit']),
                'savedData' => $incidentRecord->data,
                'recordId'  => $incidentRecord->id,
                'incidents' => $irfIncidentOptions(),
            ])->render();

            // setNodeBinary/setNpmBinary explicitly, and setBinPath for
            // puppeteer's own CLI — on Windows, node.exe lives under
            // "C:\Program Files\nodejs\", and Browsershot's own
            // auto-detection doesn't reliably re-quote a path containing a
            // space when it shells out via Symfony Process. Left
            // unspecified, that's what actually produced the "opens a
            // PowerShell window and hangs forever" symptom — the spawned
            // process was being invoked against a mis-parsed path, not
            // running the real binary at all. A bounded timeout also means
            // a failure surfaces as an actual error instead of a page that
            // never finishes loading.
            $pdf = \Spatie\Browsershot\Browsershot::html($html)
                ->setNodeBinary('C:\\Program Files\\nodejs\\node.exe')
                ->setNpmBinary('C:\\Program Files\\nodejs\\npm.cmd')
                ->setIncludePath('C:\\Program Files\\nodejs;' . getenv('PATH'))
                ->noSandbox()
                ->timeout(60)
                ->showBackground()
                ->waitUntilNetworkIdle()
                ->format('Legal')
                ->pdf();

            return response($pdf, 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="IRF-'.$incidentRecord->id.'.pdf"',
            ]);
        })->name('incident-records.pdf');

        // Simpler entry form — same store() endpoint and same saved data as
        // the official form, just grouped by real-world concept (who/what
        // happened/who else was involved) instead of the dense ITEM
        // A/B/C/D/E layout, so filling it in doesn't mean facing all ~60
        // official fields at once. The official form is untouched and still
        // where review/print/PDF happens. Registered before the {incident}
        // wildcard below for the same reason "all"/"history" are — otherwise
        // "simple" would get swallowed as an incident ID.
        Route::get('/incident-records/simple', fn () => view('investigation.incident-records.simple', [
            'incident'  => null,
            'incidents' => $irfIncidentOptions(),
        ]))->name('incident-records.simple.index');

        Route::get('/incident-records/simple/history/{incidentRecord}', function (\App\Models\IncidentRecord $incidentRecord) use ($irfIncidentOptions) {
            return view('investigation.incident-records.simple', [
                'incident'  => $incidentRecord->incident?->load(['rider', 'patrolUnit']),
                'savedData' => $incidentRecord->data,
                'recordId'  => $incidentRecord->id,
                'incidents' => $irfIncidentOptions(),
            ]);
        })->name('incident-records.simple.reprint');

        Route::get('/incident-records/simple/{incident}', function (Incident $incident) use ($irfIncidentOptions) {
            $incident->load(['rider', 'patrolUnit']);
            return view('investigation.incident-records.simple', [
                'incident'  => $incident,
                'incidents' => $irfIncidentOptions(),
            ]);
        })->name('incident-records.simple.show');

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
            $incident->load(['rider', 'patrolUnit', 'device', 'incidentRecords.generatedBy']);
            return view('investigation.incident-report.show', [
                'incident'     => $incident,
                'incidentRecords' => $incident->incidentRecords->sortByDesc('created_at'),
                'fullName'     => $incident->rider?->full_name     ?? 'N/A',
                'datetime'     => $incident->created_at->format('F d, h:i A'),
                'coordinates'  => '('.$incident->latitude.'° N, '.$incident->longitude.'° E)',
                'reportedBy'   => $incident->patrolUnit?->full_name ?? 'TOC System',
                'unit'         => $incident->patrolUnit?->badge_number ?? 'N/A',
                'description'  => ucfirst($incident->type).' incident reported at '.$incident->address.'.',
                // Null until investigation staff fill them in via the form
                // below — an IoT crash-detection report can't know any of
                // these at the moment it's created, so there's no honest
                // default to fall back to.
                'vehicles'     => $incident->vehicles_involved,
                'injured'      => $incident->injured_count,
                'severity'     => ucfirst($incident->severity),
                'roadCondition'=> $incident->road_condition,
                'weather'      => $incident->weather_condition,
                // Real column, previously never shown anywhere on this page
                // — including "false_alarm", which is exactly the kind of
                // thing someone reading a report needs to see at a glance.
                'status'       => $incident->status,
                'notes'        => $incident->notes,
                'deviceCode'   => $incident->device?->device_code,
                'deviceModel'  => $incident->device?->model,
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

        Route::post('/incident-report/{incident}/details', function (Request $request, Incident $incident) {
            $data = $request->validate([
                'vehicles_involved' => ['nullable', 'integer', 'min:0', 'max:255'],
                'injured_count'     => ['nullable', 'integer', 'min:0', 'max:255'],
                'road_condition'    => ['nullable', 'string', 'in:Dry,Wet,Icy,Under Repair'],
                'weather_condition' => ['nullable', 'string', 'in:Clear,Cloudy,Rainy,Foggy,Stormy'],
            ]);
            $incident->update($data);
            return back()->with('success', 'Incident details updated.');
        })->name('incident-report.update-details');
        Route::get('/devices', function () {
            // withCount('incidents') on the device relation, not the rider —
            // a device racking up an unusual number of incidents (especially
            // false_alarm-flagged ones) is a hardware/calibration signal
            // worth an investigator's attention, independent of which rider
            // currently has it paired.
            $riders = User::with(['device' => fn ($q) => $q->withCount('incidents')])
                ->where('role', 'rider')->latest()->get();
            return view('investigation.devices.index', [
                'riders'         => $riders,
                'totalRiders'    => $riders->count(),
                'totalDevices'   => Device::count(),
                'activeDevices'  => Device::where('is_active', true)->count(),
                'pairedDevices'  => Device::whereNotNull('paired_at')->count(),
            ]);
        })->name('devices.index');

        // ── Accident Analytics (read-only) ────────────────────────────────
        Route::get('/analytics', function () {
            $total = Incident::count();

            $pronestAreas = Incident::select('address', DB::raw('count(*) as total'))
                ->whereNotNull('address')->where('address', '!=', '')
                ->groupBy('address')->orderByDesc('total')->limit(10)->get();

            $byMonth = Incident::select(
                    DB::raw("DATE_FORMAT(created_at,'%Y-%m') as month"),
                    DB::raw('count(*) as total')
                )
                ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
                ->groupBy('month')->orderBy('month')->get();

            $bySeverity = Incident::select('severity', DB::raw('count(*) as total'))
                ->groupBy('severity')->get();

            $byDayOfWeek = Incident::select(
                    DB::raw('DAYOFWEEK(created_at) as day'),
                    DB::raw('count(*) as total')
                )
                ->groupBy('day')->orderBy('day')->get()->keyBy('day');

            $byHour = Incident::select(
                    DB::raw('HOUR(created_at) as hour'),
                    DB::raw('count(*) as total')
                )
                ->groupBy('hour')->orderBy('hour')->get()->keyBy('hour');

            $heatmapPoints = Incident::select('latitude', 'longitude', 'severity', 'type', 'address', 'created_at')
                ->whereNotNull('latitude')->whereNotNull('longitude')->get();

            $criticalCount = $bySeverity->where('severity', 'critical')->sum('total');
            $resolvedCount = Incident::where('status', 'resolved')->count();

            return view('investigation.analytics.index', compact(
                'total', 'pronestAreas', 'byMonth', 'bySeverity',
                'byDayOfWeek', 'byHour', 'heatmapPoints', 'criticalCount', 'resolvedCount'
            ));
        })->name('analytics.index');
    });
