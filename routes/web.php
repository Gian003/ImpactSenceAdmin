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

        \Illuminate\Support\Facades\Mail::send([], [], function ($message) use ($email, $resetUrl, $user) {
            $message->to($email)
                ->subject('ImpactSense — Reset Your Password')
                ->html('
                    <p>Hello <strong>' . e($user->full_name) . '</strong>,</p>
                    <p>You requested a password reset for your ImpactSense admin account.</p>
                    <p>Click the button below to set a new password. This link expires in <strong>60 minutes</strong>.</p>
                    <p style="margin:24px 0;">
                        <a href="' . $resetUrl . '"
                           style="background:#1b3d52; color:#fff; padding:12px 28px;
                                  text-decoration:none; border-radius:6px; font-weight:bold;">
                            Reset Password
                        </a>
                    </p>
                    <p style="color:#666; font-size:12px;">
                        If you did not request this, no action is needed.<br>
                        Direct link: ' . $resetUrl . '
                    </p>
                ');
        });
    }

    return back()->with('status', 'If that email exists in our system, a reset link has been sent.');
})->name('password.email');

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

            try {
                broadcast(new \App\Events\PatrolDispatched($incident));
                broadcast(new \App\Events\IncidentStatusUpdated($incident));
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
