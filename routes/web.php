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
use Illuminate\Support\Facades\Storage;

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
            // Shared by the alert panel's triage sort. Unknown severities sort
            // last rather than blowing up — a missing value must not push an
            // incident to the top of an emergency board.
            $severityRank = fn (?string $s) => match ($s) {
                'critical' => 4,
                'high'     => 3,
                'medium'   => 2,
                'low'      => 1,
                default    => 0,
            };

            // Speed Reports per Area — compares each police-defined speed
            // zone's posted limit against real observed GPS speed samples
            // that fall within it. The aggregation lives in
            // SpeedZone::statsFor() — it used to load the whole speed_reports
            // table into memory here and run a PHP haversine per sample per
            // zone, which is fine at zero rows and falls over at real volume
            // on what is also the live dispatch board.
            $zones = \App\Models\SpeedZone::all();
            $stats = \App\Models\SpeedZone::statsFor($zones);

            $speedZoneStats = $zones->map(function ($zone) use ($stats) {
                $stat = $stats[$zone->id];

                return (object) [
                    'id'               => $zone->id,
                    'name'             => $zone->name,
                    'speed_limit_kph'  => $zone->speed_limit_kph,
                    'sample_count'     => $stat->sample_count,
                    'avg_speed'        => $stat->avg_speed,
                    'percentile_speed' => $stat->percentile_speed,
                    'violation_rate'   => $stat->violation_rate,
                    'is_violating'     => $stat->is_violating,
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
                // The alert panel answers "what is happening now". It was
                // answering "what has never been closed": 57 incidents were
                // open, some more than a month old, and the ten newest were
                // rendered as live alerts while the other 47 were invisible
                // behind the cap with nothing saying so. A month-old
                // unclosed case and a call from ninety seconds ago are two
                // different problems and cannot share a panel.
                //
                // "arrived" belongs in the active set: a unit on scene is the
                // point the desk most needs to see the incident, not the
                // point it should drop off the board.
                'pendingIncidents' => $liveIncidents = Incident::with(['rider', 'patrolUnit'])
                    ->whereIn('status', ['pending', 'dispatched', 'arrived'])
                    ->where('created_at', '>=', now()->subHours($liveWindowHours = Incident::LIVE_WINDOW_HOURS))
                    ->get()
                    // Triage order, not arrival order. Anything still pending
                    // has nobody going to it, so it outranks everything that
                    // has a unit assigned; within that, severity decides; and
                    // the newest of equals sits on top.
                    ->sortBy([
                        fn ($a, $b) => ($a->status === 'pending' ? 0 : 1) <=> ($b->status === 'pending' ? 0 : 1),
                        fn ($a, $b) => $severityRank($b->severity) <=> $severityRank($a->severity),
                        fn ($a, $b) => $b->created_at <=> $a->created_at,
                    ])
                    ->take(12)
                    ->values(),

                'liveWindowHours' => $liveWindowHours,

                // Everything still open from before the window. Counted and
                // linked rather than drawn, because these need closing, not
                // dispatching.
                'backlogCount' => Incident::whereIn('status', ['pending', 'dispatched', 'arrived'])
                    ->where('created_at', '<', now()->subHours($liveWindowHours))
                    ->count(),
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

        // ── Desk activity ──────────────────────────────────────────────────────
        // What the desk has actually done, across every incident, from the
        // append-only incident_events log. Per-incident history answers "what
        // happened to this call"; this answers "what happened on this shift",
        // which nothing could before because the record only existed as four
        // timestamp columns per incident.
        Route::get('/activity', function (Request $request) {
            $ranges = [
                'today' => 'Today',
                '24h'   => 'Last 24 hours',
                '7d'    => 'Last 7 days',
                '30d'   => 'Last 30 days',
                'all'   => 'All time',
            ];

            $range = array_key_exists($request->input('range'), $ranges)
                ? $request->input('range')
                : '7d';

            $since = match ($range) {
                'today' => now()->startOfDay(),
                '24h'   => now()->subDay(),
                '7d'    => now()->subDays(7),
                '30d'   => now()->subDays(30),
                default => null,
            };

            $events = \App\Models\IncidentEvent::with('incident.rider')
                ->when($since, fn ($q) => $q->where('occurred_at', '>=', $since))
                ->when($request->input('type'), fn ($q, $t) => $q->where('type', $t))
                ->when($request->input('actor'), fn ($q, $a) => $q->where('actor_type', $a))
                ->orderByDesc('occurred_at')
                ->orderByDesc('id')
                ->paginate(50)
                ->withQueryString();

            // Response times come from the incidents table rather than the log:
            // those columns are exact, indexed, and — now that reassignment no
            // longer overwrites dispatched_at — actually mean "first dispatch".
            $timed = Incident::query()
                ->when($since, fn ($q) => $q->where('created_at', '>=', $since))
                ->whereNotNull('dispatched_at')
                ->get(['created_at', 'dispatched_at', 'arrived_at']);

            $median = function (array $values): ?int {
                if (! $values) {
                    return null;
                }
                sort($values);
                $mid = intdiv(count($values), 2);

                // Median rather than mean: one incident dispatched three weeks
                // late would drag an average into meaninglessness, and the
                // backlog says that has happened.
                return (int) round(count($values) % 2
                    ? $values[$mid]
                    : ($values[$mid - 1] + $values[$mid]) / 2);
            };

            $toDispatch = $timed
                ->map(fn ($i) => $i->created_at->diffInSeconds($i->dispatched_at, absolute: true))
                ->all();

            $toArrive = $timed->filter(fn ($i) => $i->arrived_at !== null)
                ->map(fn ($i) => $i->dispatched_at->diffInSeconds($i->arrived_at, absolute: true))
                ->values()->all();

            return view('toc.activity.index', [
                'events'          => $events,
                'ranges'          => $ranges,
                'range'           => $range,
                'type'            => $request->input('type'),
                'actor'           => $request->input('actor'),
                'types'           => \App\Models\IncidentEvent::query()
                    ->select('type')->distinct()->orderBy('type')->pluck('type'),
                'medianToDispatch' => $median($toDispatch),
                'medianToArrive'   => $median($toArrive),
                'dispatchSample'   => count($toDispatch),
                'arriveSample'     => count($toArrive),
                // Reconstructed rows carry real timestamps but no actor, so
                // the page has to say how much of what it shows was inferred.
                'reconstructedCount' => (clone $events)->getCollection()
                    ->where('reconstructed', true)->count(),
            ]);
        })->name('activity.index');

        // ── Incidents (TOC view) ───────────────────────────────────────────────
        // Deliberately not the investigation incident-report page, which the
        // alert panel used to link to. That page has grown to hold responder
        // field reports with crash-scene photographs and generated IRF
        // records — evidentiary case material. The TOC desk dispatches these
        // incidents and needs to see who, where, when, how bad and who is
        // going; it does not need photographs of injured people to do that.
        Route::get('/incidents', function (Request $request) {
            $query = Incident::with(['rider', 'patrolUnit', 'device']);

            // Defaults to what is still open, because the reason an operator
            // arrives here is the backlog notice on the tracking board.
            $status = $request->input('status', 'open');
            if ($status === 'open') {
                $query->whereIn('status', ['pending', 'dispatched', 'arrived']);
            } elseif ($status === 'ageing') {
                // Still open, and older than the tracking board's live window.
                // A pseudo-status rather than a separate parameter, because to
                // an operator "ageing" is a kind of open incident, not an extra
                // switch to remember to combine with one.
                $query->whereIn('status', ['pending', 'dispatched', 'arrived'])
                    ->where('created_at', '<', now()->subHours(Incident::LIVE_WINDOW_HOURS));
            } elseif ($status !== 'all') {
                $query->where('status', $status);
            }

            if ($severity = $request->input('severity')) {
                $query->where('severity', $severity);
            }

            if ($search = trim((string) $request->input('q'))) {
                $query->where(function ($q) use ($search) {
                    $q->where('address', 'like', "%{$search}%")
                        ->orWhereHas('rider', fn ($r) => $r->where('full_name', 'like', "%{$search}%"));
                });
            }

            // Oldest first when looking at open incidents: the ones that have
            // been sitting longest are the ones nobody has dealt with.
            $incidents = $query
                ->orderBy('created_at', in_array($status, ['open', 'ageing'], true) ? 'asc' : 'desc')
                ->paginate(25)
                ->withQueryString();

            // Counts for the triage strip. Every tile is a one-click filter,
            // which is what an operator arriving at a 57-item backlog actually
            // wants — not to read a table and work out where to start.
            $openStatuses = ['pending', 'dispatched', 'arrived'];
            $byStatus = Incident::whereIn('status', $openStatuses)
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            return view('toc.incidents.index', [
                'incidents'       => $incidents,
                'status'          => $status,
                'severity'        => $request->input('severity'),
                'q'               => $search,
                'liveWindowHours' => Incident::LIVE_WINDOW_HOURS,
                'openCount'       => $byStatus->sum(),
                'counts'          => [
                    'pending'    => $byStatus['pending'] ?? 0,
                    'dispatched' => $byStatus['dispatched'] ?? 0,
                    'arrived'    => $byStatus['arrived'] ?? 0,
                    'critical'   => Incident::whereIn('status', $openStatuses)
                        ->where('severity', 'critical')->count(),
                    'ageing'     => Incident::whereIn('status', $openStatuses)
                        ->where('created_at', '<', now()->subHours(Incident::LIVE_WINDOW_HOURS))
                        ->count(),
                ],
            ]);
        })->name('incidents.index');

        Route::get('/incidents/{incident}', function (Incident $incident) {
            $incident->load(['rider', 'patrolUnit', 'device', 'fieldReports.patrolUnit', 'events']);

            return view('toc.incidents.show', [
                'incident'   => $incident,
                'patrollers' => PatrolUnit::all(),
                // The append-only log, not four mutable columns. It can hold
                // the same event twice — a reassignment — and it records who
                // acted, which no column ever could.
                'events'     => $incident->events,
            ]);
        })->name('incidents.show');

        // ── Speed zones — police-maintained posted speed limits ────────────────

        // Shared by store and update so the two can't validate differently.
        $speedZoneRules = [
            'name'             => ['required', 'string', 'max:255'],
            // The road centreline, drawn on the map. A single point is still
            // valid and behaves as the circle zones used to.
            'path'             => ['required', 'array', 'min:1', 'max:200'],
            'path.*.lat'       => ['required', 'numeric', 'between:-90,90'],
            'path.*.lng'       => ['required', 'numeric', 'between:-180,180'],
            // Half-width of the corridor, not a circle radius. Capped tighter
            // than before: 500 m either side of a centreline is already far
            // wider than any carriageway, and the old 5 km ceiling only made
            // sense when a zone was a blob covering a district.
            'radius_meters'    => ['required', 'integer', 'min:5', 'max:500'],
            'speed_limit_kph'  => ['required', 'integer', 'min:1', 'max:200'],
            'allow_overlap'    => ['sometimes', 'boolean'],
        ];

        // A hidden input can only carry a string, so the traced path arrives as
        // JSON text and has to become an array before the array rules above
        // can look at it. Without this every save failed with "The path field
        // must be an array" — the rules were right, the form simply cannot
        // send what they wanted.
        $speedZoneNormalise = function (Request $request): void {
            $path = $request->input('path');

            if (! is_string($path)) {
                return;   // already an array (an API client, or a test)
            }

            $decoded = json_decode($path, true);

            // A malformed or empty value becomes an empty array rather than
            // null, so it fails the "required" rule with a message about
            // tracing the road instead of a JSON parse error.
            $request->merge([
                'path' => is_array($decoded) ? $decoded : [],
            ]);
        };

        // Phrased for whoever is drawing the zone, not for whoever wrote the
        // validation rules.
        $speedZoneMessages = [
            'path.required' => 'Trace the road on the map before saving — press "Trace road", then click along it.',
            'path.array'    => 'The traced road could not be read. Press "Clear" and trace it again.',
            'path.min'      => 'Trace the road on the map before saving — press "Trace road", then click along it.',
            'path.max'      => 'That corridor has too many points. Trace it with fewer clicks, or split it into two zones.',
            'path.*.lat.required'  => 'One of the traced points is missing its latitude. Press "Clear" and trace it again.',
            'path.*.lng.required'  => 'One of the traced points is missing its longitude. Press "Clear" and trace it again.',
            'radius_meters.min' => 'A corridor needs at least a 5 m half-width.',
            'radius_meters.max' => 'A corridor half-width above 500 m is wider than any carriageway — use a smaller number.',
        ];

        // latitude/longitude stay on the row as the corridor's midpoint —
        // every map that plots a zone as a single marker still has somewhere
        // to put it, and nothing downstream had to learn about paths.
        $speedZoneAttributes = function (array $data): array {
            $path = array_values(array_map(
                fn ($p) => ['lat' => (float) $p['lat'], 'lng' => (float) $p['lng']],
                $data['path']
            ));

            $mid = $path[intdiv(count($path), 2)];

            return [
                'name'            => $data['name'],
                'path'            => $path,
                'latitude'        => $mid['lat'],
                'longitude'       => $mid['lng'],
                'radius_meters'   => $data['radius_meters'],
                'speed_limit_kph' => $data['speed_limit_kph'],
            ];
        };

        // Refuses a zone that overlaps an existing one, unless the operator
        // has deliberately allowed it. Blocking by default because the damage
        // is silent: a sample inside the shared area is counted toward both
        // zones' averages, and a flagged street gives no clue which posted
        // limit the reading was judged against. The escape hatch exists for
        // the legitimate case of a small zone deliberately sitting inside a
        // larger stretch.
        $speedZoneOverlap = function (Request $request, array $data, ?int $ignoreId) {
            if ($request->boolean('allow_overlap')) {
                return null;
            }

            $path = array_map(
                fn ($p) => ['lat' => (float) $p['lat'], 'lng' => (float) $p['lng']],
                $data['path']
            );

            $clash = \App\Models\SpeedZone::overlapping(
                $path,
                (int) $data['radius_meters'],
                $ignoreId,
            );

            if (! $clash) {
                return null;
            }

            $candidate = new \App\Models\SpeedZone([
                'path'          => $path,
                'radius_meters' => (int) $data['radius_meters'],
            ]);
            $metres = (int) round($candidate->distanceToZoneMeters($clash));

            return back()->withInput()->withErrors([
                'path' => "This corridor comes within {$metres} m of \"{$clash->name}\", which has a "
                    . "{$clash->radius_meters} m half-width — closer than the {$data['radius_meters']} m "
                    . "+ {$clash->radius_meters} m needed to keep them apart. Samples in the shared "
                    . "stretch would count toward both zones. Redraw it, narrow the corridor, or tick "
                    . "\"allow overlap\" if the two genuinely share road.",
            ]);
        };
        Route::get('/speed-zones', function () {
            $zones = \App\Models\SpeedZone::with('creator')->latest()->get();

            return view('toc.speed-zones.index', [
                'zones'          => $zones,
                'speedZoneStats' => \App\Models\SpeedZone::statsFor($zones),
                'windowDays'     => \App\Models\SpeedZone::WINDOW_DAYS,
                'percentile'     => \App\Models\SpeedZone::PERCENTILE,
                // Whether anything is actually feeding the table. An empty
                // comparison reads as broken unless the page says why.
                'totalSamples'   => \App\Models\SpeedReport::count(),
                // Edit reuses the add form — same fields, same map picker —
                // rather than a second form that would drift out of step
                // with it. ?edit={id} switches it into update mode.
                'editing'        => $editingZone = request()->filled('edit')
                    ? $zones->firstWhere('id', (int) request('edit'))
                    : null,
                // Shapes for the map picker. Built here rather than in a Blade
                // loop so the view ships one JSON blob instead of generating
                // JavaScript per zone.
                'zoneOverlays'   => $zones->map(fn ($z) => [
                    'name'    => $z->name,
                    'path'    => collect($z->pathPoints())
                        ->map(fn ($pt) => ['lat' => $pt[0], 'lng' => $pt[1]])->all(),
                    'radius'  => $z->radius_meters,
                    'editing' => $editingZone !== null && $editingZone->id === $z->id,
                ])->values(),
            ]);
        })->name('speed-zones.index');

        Route::post('/speed-zones', function (Request $request) use (
            $speedZoneRules, $speedZoneOverlap, $speedZoneAttributes,
            $speedZoneNormalise, $speedZoneMessages
        ) {
            $speedZoneNormalise($request);
            $data = $request->validate($speedZoneRules, $speedZoneMessages);

            if ($clash = $speedZoneOverlap($request, $data, null)) {
                return $clash;
            }

            \App\Models\SpeedZone::create(
                $speedZoneAttributes($data) + ['created_by' => Auth::guard('toc')->id()]
            );

            return back()->with('success', "Speed zone \"{$data['name']}\" added.");
        })->name('speed-zones.store');

        // Zones were previously add-and-delete only, so correcting a mistyped
        // radius meant destroying the row and losing who created it and when.
        Route::put('/speed-zones/{speedZone}', function (
            Request $request, \App\Models\SpeedZone $speedZone
        ) use (
            $speedZoneRules, $speedZoneOverlap, $speedZoneAttributes,
            $speedZoneNormalise, $speedZoneMessages
        ) {
            $speedZoneNormalise($request);
            $data = $request->validate($speedZoneRules, $speedZoneMessages);

            if ($clash = $speedZoneOverlap($request, $data, $speedZone->id)) {
                return $clash;
            }

            $speedZone->update($speedZoneAttributes($data));

            return redirect()
                ->route('toc.speed-zones.index')
                ->with('success', "Speed zone \"{$speedZone->name}\" updated.");
        })->name('speed-zones.update');

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

            $previousUnit  = $incident->patrolUnit;
            $isReassign    = $previousUnit !== null && $previousUnit->id !== $patrol->id;
            $statusFrom    = $incident->status;

            $incident->update([
                'patrol_unit_id' => $patrol->id,
                'status'         => 'dispatched',
                // Only stamped on the first dispatch. It used to be
                // overwritten every time, so reassigning a call destroyed the
                // original dispatch time and the timeline then showed the new
                // unit at the new time as though nothing else had happened.
                // The reassignment itself is a row in incident_events.
                'dispatched_at'  => $incident->dispatched_at ?? now(),
            ]);

            \App\Models\IncidentEvent::record(
                $incident,
                $isReassign
                    ? \App\Models\IncidentEvent::REASSIGNED
                    : \App\Models\IncidentEvent::DISPATCHED,
                [
                    'status_from' => $statusFrom,
                    'status_to'   => 'dispatched',
                    'payload'     => array_filter([
                        'patrol_unit'   => $patrol->full_name,
                        'badge'         => $patrol->badge_number,
                        'previous_unit' => $isReassign ? $previousUnit->full_name : null,
                    ]),
                ],
            );

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
                [
                    'incident_id' => (string) $incident->id,
                    'type'        => 'dispatch',
                    // See the note in IncidentController::updateStatus.
                    'severity'    => (string) $incident->severity,
                    'address'     => (string) $incident->address,
                ]
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

        // Update device_code, model, or firmware on an existing device
        Route::patch('/devices/{device}', function (Request $request, Device $device) {
            $data = $request->validate([
                'device_code'      => ['required', 'string', 'max:50', 'unique:devices,device_code,' . $device->id],
                'model'            => ['nullable', 'string', 'max:100'],
                'firmware_version' => ['nullable', 'string', 'max:20'],
            ]);

            $device->update($data);

            return redirect()->route('toc.devices.index')
                ->with('success', 'Device "' . $device->device_code . '" updated.');
        })->name('devices.update');

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
            // Which roster entries have actually been claimed. The roster says
            // who is *allowed* an account; these two say who has one, who is
            // waiting on review, and — by omission — who never registered at
            // all, which is the set worth chasing. Matched on badge_number,
            // the same key PatrolRegistrationController validates against.
            //
            // flip() turns each list into a badge_number-keyed set so the view
            // does an isset() per row rather than a query or a search.
            // filter() drops nulls before flipping: patrol_registrations
            // .badge_number is nullable, and array_flip() warns and silently
            // skips a null entry. A null can't match a roster badge anyway.
            $withAccount = PatrolUnit::pluck('badge_number')->filter()->flip();
            $awaitingReview = \App\Models\PatrolRegistration::where('status', 'pending')
                ->pluck('badge_number')->filter()->flip();

            return view('toc.personnel-roster.index', [
                'roster'         => \App\Models\PersonnelRoster::latest()->get(),
                'ranks'          => \App\Models\PersonnelRoster::RANKS,
                'withAccount'    => $withAccount,
                'awaitingReview' => $awaitingReview,
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

            // Deactivating is the switch TOC reaches for when someone is
            // transferred, suspended or leaves, so it has to end the access
            // they already have — not merely block a registration they've
            // already completed. Sanctum tokens are what keep the patrol app
            // signed in, so an officer whose roster entry is switched off
            // would otherwise keep working from an already-issued token
            // indefinitely; PatrolAuthController::login() authenticates
            // against patrol_units alone and never consults the roster.
            //
            // Note this closes the door on an account that exists, but does
            // not yet stop a deactivated person from logging in again if they
            // still know their password — that needs a roster check at login,
            // which can't be switched on until every patrol account has a
            // matching roster entry.
            $signedOut = false;

            if (! $roster->is_active) {
                $unit = PatrolUnit::where('badge_number', $roster->badge_number)->first();

                if ($unit) {
                    $unit->tokens()->delete();
                    // Don't leave them sitting on the dispatch board as though
                    // they were still available to take a call.
                    $unit->update(['status' => 'off_duty']);
                    $signedOut = true;
                }
            }

            return back()->with('success', $roster->full_name
                . ($roster->is_active ? ' reactivated.' : ' deactivated.')
                . ($signedOut ? ' Their patrol app has been signed out.' : ''));
        })->name('personnel-roster.toggle');

        // ── Twilio alert-call recordings ──────────────────────────────────
        // The TTS alert calls placed to the TOC hotline (see
        // EmergencyNotificationService) are recorded by Twilio; this surfaces
        // them here instead of making someone dig through Twilio's console.
        Route::get('/call-recordings', function (\App\Services\CallRecordingService $recordings) {
            $list = $recordings->recent();

            // Twilio recordings carry the call_sid they belong to, and
            // incidents store the SID of the alert call placed for them —
            // that's the only link between the two, so resolve it here in
            // one query keyed by SID rather than per row.
            $incidents = Incident::with('rider')
                ->whereIn('twilio_call_sid', array_filter(array_map(
                    fn ($recording) => $recording->callSid,
                    $list,
                )))
                ->get()
                ->keyBy('twilio_call_sid');

            return view('toc.call-recordings.index', [
                'recordings'   => $list,
                'incidents'    => $incidents,
                'isConfigured' => $recordings->isConfigured(),
            ]);
        })->name('call-recordings.index');

        // Streams the audio through this app so Twilio's credentials stay
        // server-side (their media URLs need Basic auth - see
        // CallRecordingService::media). The SID pattern is constrained to
        // Twilio's real recording-SID format so this can't be pointed at an
        // arbitrary path.
        Route::get('/call-recordings/{sid}/audio', function (string $sid, \Illuminate\Http\Request $request, \App\Services\CallRecordingService $recordings) {
            $audio = $recordings->media($sid);

            abort_if($audio === null, 404, 'Recording audio unavailable.');

            return response($audio, 200, [
                'Content-Type'        => 'audio/mpeg',
                'Content-Disposition' => ($request->boolean('download') ? 'attachment' : 'inline')
                    . '; filename="ImpactSense-call-' . $sid . '.mp3"',
            ]);
        })->where('sid', 'RE[0-9a-fA-F]{32}')->name('call-recordings.audio');

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

            // Month *and* year, newest first. This was previously the month
            // name alone, which silently merged September 2025 with September
            // 2026 into a single filter option — the data already spans two
            // years, so that bucket was mixing unrelated cases together.
            $incidentMonths = $incidents
                ->pluck('created_at')
                ->sortDesc()
                ->map(fn ($d) => $d->format('F Y'))
                ->unique()
                ->values();
            // Only the types actually present, so the dropdown never offers a
            // filter that can only ever return nothing.
            $incidentTypes = $incidents->pluck('type')->unique()->sort()->values();

            return view('investigation.incidents.index', [
                'incidents'      => $incidents,
                'incidentMonths' => $incidentMonths,
                'incidentTypes'  => $incidentTypes,
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
            // Was an unbounded ->get() — fine while this page was a buried
            // link, not once it's a primary nav destination expected to
            // accumulate records indefinitely.
            return view('investigation.incident-records.all', [
                'records' => \App\Models\IncidentRecord::with(['incident.rider', 'generatedBy'])
                    ->latest()->paginate(25),
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

        Route::get('/incident-report/{incident}', function (Incident $incident) {
            $incident->load([
                'rider', 'patrolUnit', 'device', 'incidentRecords.generatedBy',
                'fieldReports.patrolUnit', 'fieldReports.photos',
            ]);
            return view('investigation.incident-report.show', [
                'incident'     => $incident,
                'incidentRecords' => $incident->incidentRecords->sortByDesc('created_at'),
                // What the responding unit actually said, kept separate from
                // the investigator's IRF — see IncidentFieldReport.
                'fieldReports' => $incident->fieldReports,
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
                    ['time' => $incident->arrived_at?->format('h:i A')    ?? '—', 'description' => 'Patrol arrived on scene'],
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

// ── SCENE PHOTOGRAPHS ─────────────────────────────────────────────────────────
// Field photos live on the private disk and are only ever readable through
// here. They show injured people, faces and plate numbers, and the dashboard
// is served over a public tunnel — an unguessable URL is not access control.
//
// Open to both desks: the TOC watches the incident come in and investigation
// writes it up, and both legitimately need to see what the responder saw.
Route::get('/incident-field-photos/{photo}', function (\App\Models\IncidentFieldPhoto $photo) {
    abort_unless($photo->exists(), 404, 'The photograph is no longer on disk.');

    return Storage::disk(\App\Models\IncidentFieldPhoto::DISK)->response(
        $photo->path,
        $photo->original_filename ?: "scene-photo-{$photo->id}.jpg",
        [
            'Content-Type'           => $photo->mime_type ?: 'image/jpeg',
            'Content-Disposition'    => 'inline',
            // Nothing about a crash-scene photograph should sit in a shared
            // proxy cache or on disk after the tab closes.
            'Cache-Control'          => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]
    );
})->middleware('auth:toc,investigation')->name('incident-field-photos.show');
