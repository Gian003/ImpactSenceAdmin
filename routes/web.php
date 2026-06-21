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
            return view('toc.location.index', [
                'pendingIncidents' => Incident::with('rider')
                    ->whereIn('status', ['pending', 'dispatched'])
                    ->latest()->take(10)->get(),
                'patrollers' => PatrolUnit::all(),
            ]);
        })->name('location.tracking');

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

            $incident->load(['rider', 'patrolUnit']);

            broadcast(new \App\Events\PatrolDispatched($incident));
            broadcast(new \App\Events\IncidentStatusUpdated($incident));

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

        Route::get('/patrollers', function () {
            return view('toc.patrollers.index', [
                'patrollers' => PatrolUnit::all(),
            ]);
        })->name('patrollers.index');
    });

// ── INVESTIGATION — Investigation PCO ────────────────────────────────────────
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
        Route::get('/incident-records',   fn () => view('investigation.incident-records.index'))  ->name('incident-records.index');
        Route::get('/incident-report', fn () => view('investigation.incident-report.index'))->name('incident-report.index');

        Route::get('/incident-report/{incident}', function (Incident $incident) {
            $incident->load(['rider', 'patrolUnit', 'helmet']);
            return view('investigation.incident-report.index', [
                'incident'     => $incident,
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
        Route::get('/helmet',             fn () => view('investigation.helmet.index'))            ->name('helmet.index');
    });
