<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// ── PUBLIC ────────────────────────────────────────────────────────────────────
Route::get('/', fn () => view('welcome'));

// ── AUTH ──────────────────────────────────────────────────────────────────────
Route::get('/login', fn () => view('auth.login'))->name('login');

Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email'    => ['required', 'email'],
        'password' => ['required'],
    ]);

    if (Auth::attempt($credentials, $request->boolean('remember'))) {
        $request->session()->regenerate();

        // Redirect to the user's department dashboard based on their role
        $role = Auth::user()->role;
        return redirect()->intended(route($role . '.dashboard'));
    }

    return back()->withErrors([
        'email' => 'The provided credentials do not match our records.',
    ])->onlyInput('email');
});

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect()->route('login');
})->name('logout');

// ── TOC — Tactical Operations Center ─────────────────────────────────────────
Route::prefix('toc')
    ->name('toc.')
    ->middleware(['auth', 'role:toc'])
    ->group(function () {
        Route::get('/dashboard',         fn () => view('toc.dashboard.index'))   ->name('dashboard');
        Route::get('/location-tracking', fn () => view('toc.location.index'))    ->name('location.tracking');
        Route::get('/helmet',            fn () => view('toc.helmet.index'))       ->name('helmet.index');
        Route::get('/patrollers',        fn () => view('toc.patrollers.index'))   ->name('patrollers.index');
    });

// ── INVESTIGATION — Investigation PCO ────────────────────────────────────────
Route::prefix('investigation')
    ->name('investigation.')
    ->middleware(['auth', 'role:investigation'])
    ->group(function () {
        Route::get('/dashboard',          fn () => view('investigation.dashboard.index'))         ->name('dashboard');
        Route::get('/incidents',          fn () => view('investigation.incidents.index'))         ->name('incidents.index');
        Route::get('/incident-records',   fn () => view('investigation.incident-records.index'))  ->name('incident-records.index');
        Route::get('/incident-report',    fn () => view('investigation.incident-report.index'))   ->name('incident-report.index');
        Route::get('/helmet',             fn () => view('investigation.helmet.index'))            ->name('helmet.index');
    });

// ── ADD NEW DEPARTMENTS BELOW ─────────────────────────────────────────────────
// Example: Field Operations Unit
// Route::prefix('field')
//     ->name('field.')
//     ->middleware(['auth', 'role:field'])
//     ->group(function () {
//         Route::get('/dashboard', fn () => view('field.dashboard.index'))->name('dashboard');
//     });
