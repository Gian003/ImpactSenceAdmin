<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthenticate
{
    public function handle(Request $request, Closure $next)
    {
        if (! Auth::guard('admin')->check()) {
            return redirect()->route('admin.login');
        }

        if (! Auth::guard('admin')->user()->is_active) {
            Auth::guard('admin')->logout();
            return redirect()->route('admin.login')
                ->withErrors(['email' => 'Your account has been deactivated.']);
        }

        return $next($request);
    }
}
