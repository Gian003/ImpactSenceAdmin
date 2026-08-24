<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AdminInvitationMail;
use App\Models\AdminInvitation;
use App\Models\InvestigationOfficer;
use App\Models\TocPersonnel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class InvitationController extends Controller
{
    public function index()
    {
        $invitations = AdminInvitation::with('invitedBy')
            ->latest()->get();

        return view('admin.invitations.index', compact('invitations'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'role'  => ['required', 'in:toc,investigation'],
        ]);

        // Prevent duplicate pending invitations for the same email + role
        $exists = AdminInvitation::where('email', $data['email'])
            ->where('role', $data['role'])
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->exists();

        if ($exists) {
            return back()->withErrors(['email' => 'A pending invitation already exists for this email and role.']);
        }

        $invitation = AdminInvitation::create([
            'invited_by' => Auth::guard('admin')->id(),
            'email'      => $data['email'],
            'role'       => $data['role'],
            'token'      => Str::random(64),
            'expires_at' => now()->addHours(24),
        ]);

        Mail::to($data['email'])->send(new AdminInvitationMail($invitation));

        $roleLabel = $data['role'] === 'toc' ? 'TOC Officer' : 'Investigation Officer';
        return back()->with('success', "Invitation sent to {$data['email']} as {$roleLabel}.");
    }

    public function showAccept(string $token)
    {
        $invitation = AdminInvitation::where('token', $token)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->firstOrFail();

        return view('admin.invitations.accept', compact('invitation'));
    }

    public function accept(Request $request, string $token)
    {
        $invitation = AdminInvitation::where('token', $token)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->firstOrFail();

        $data = $request->validate([
            'full_name'    => ['required', 'string', 'max:255'],
            'badge_number' => ['required', 'string', 'max:50'],
            'rank'         => ['required', 'string', 'max:100'],
            'password'     => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $attributes = [
            'full_name'    => $data['full_name'],
            'badge_number' => $data['badge_number'],
            'email'        => $invitation->email,
            'password'     => Hash::make($data['password']),
            'rank'         => $data['rank'],
        ];

        if ($invitation->role === 'toc') {
            TocPersonnel::create($attributes);
        } else {
            InvestigationOfficer::create($attributes);
        }

        $invitation->update(['accepted_at' => now()]);

        $loginRoute = route('login');
        return redirect($loginRoute)->with(
            'status',
            'Account created successfully. You can now log in.'
        );
    }

    public function destroy(AdminInvitation $invitation)
    {
        if ($invitation->isAccepted()) {
            return back()->withErrors(['error' => 'Cannot revoke an accepted invitation.']);
        }
        $invitation->delete();
        return back()->with('success', 'Invitation revoked.');
    }
}
