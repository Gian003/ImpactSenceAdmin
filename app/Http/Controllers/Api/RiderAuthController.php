<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rider\LoginRequest;
use App\Http\Requests\Rider\RegisterRequest;
use App\Http\Requests\Rider\UpdateProfileRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class RiderAuthController extends Controller
{
    /**
     * Guesses a single OTP will tolerate before it is destroyed.
     *
     * Five is enough for a genuine mistyping and nowhere near enough to
     * search six digits.
     */
    private const MAX_OTP_ATTEMPTS = 5;

    public function register(RegisterRequest $request): JsonResponse
    {
        $email = $request->validated()['email'];

        if (! Cache::get('otp_verified:' . $email)) {
            return $this->apiResponse(false, 'Email not verified. Please complete OTP verification first.', null, 422);
        }

        $user = User::create($request->validated());

        Cache::forget('otp_verified:' . $email);

        $token = $user->createToken('rider-app')->plainTextToken;

        return $this->apiResponse(true, 'Registration successful', [
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return $this->apiResponse(false, 'Invalid credentials', null, 401);
        }

        if ($request->filled('fcm_token')) {
            $user->update(['fcm_token' => $request->fcm_token]);
        }

        $user->tokens()->where('name', 'rider-app')->delete();
        $token = $user->createToken('rider-app')->plainTextToken;

        return $this->apiResponse(true, 'Login successful', [
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->apiResponse(true, 'Logged out successfully');
    }

    public function profile(Request $request): JsonResponse
    {
        return $this->apiResponse(true, 'Profile retrieved', $request->user());
    }

    // Send a 6-digit OTP to the given email before registration
    public function otpSend(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $key  = 'otp:' . $request->email;

        Cache::put($key, $code, now()->addMinutes(10));

        $html = '
            <div style="font-family:sans-serif; max-width:480px; margin:0 auto; padding:32px 24px; background:#fff; border-radius:12px; border:1px solid #e8d5d9;">
                <div style="text-align:center; margin-bottom:24px;">
                    <div style="display:inline-block; background:#7B1A2E; color:#fff; font-weight:700; font-size:1.1rem; padding:10px 24px; border-radius:8px; letter-spacing:.05em;">IMPACTSENSE</div>
                </div>
                <p style="margin:0 0 8px;">Hello,</p>
                <p style="margin:0 0 20px; color:#475569;">Your ImpactSense verification code is:</p>
                <div style="text-align:center; margin:0 0 24px;">
                    <span style="display:inline-block; font-family:monospace; font-size:2rem; font-weight:900; letter-spacing:.4em; color:#7B1A2E; background:#fce7f3; padding:12px 28px; border-radius:10px;">' . $code . '</span>
                </div>
                <p style="margin:0; color:#94a3b8; font-size:12px; text-align:center;">
                    This code expires in 10 minutes. Do not share it with anyone.
                </p>
            </div>
        ';

        try {
            Mail::html($html, function ($msg) use ($request) {
                $msg->to($request->email)
                    ->subject('ImpactSense — Your Verification Code');
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('OTP mail failed', ['error' => $e->getMessage(), 'email' => $request->email]);
            return $this->apiResponse(false, 'Failed to send verification code. Please try again.', null, 500);
        }

        return $this->apiResponse(true, 'Verification code sent to your email.');
    }

    // Verify the OTP code before allowing the registration to proceed
    public function otpVerify(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'code'  => ['required', 'string', 'size:6'],
        ]);

        $key         = 'otp:' . $request->email;
        $attemptsKey = 'otp_attempts:' . $request->email;
        $stored      = Cache::get($key);

        if ($stored === null) {
            return $this->apiResponse(false, 'Code expired. Please request a new one.', null, 422);
        }

        // A six-digit code with a ten-minute life and no attempt limit is a
        // million guesses against a target that never locks. The route
        // throttle caps how fast an address can try; this caps how many
        // guesses a single code will ever tolerate, which is what makes the
        // search space matter. Counted against the code, not the caller, so
        // rotating IPs buys nothing.
        $attempts = (int) Cache::get($attemptsKey, 0);

        if ($attempts >= self::MAX_OTP_ATTEMPTS) {
            Cache::forget($key);
            Cache::forget($attemptsKey);

            return $this->apiResponse(
                false,
                'Too many incorrect attempts. Please request a new code.',
                null,
                429
            );
        }

        if ($stored !== $request->code) {
            // Expires with the code itself, so a fresh code starts clean.
            Cache::put($attemptsKey, $attempts + 1, now()->addMinutes(10));

            $left = self::MAX_OTP_ATTEMPTS - ($attempts + 1);

            // Destroyed here rather than on the next request, so the code is
            // actually dead at the moment the response says it is. Leaving it
            // cached until someone tries again made the message true only in
            // the sense that the next attempt would be refused.
            if ($left <= 0) {
                Cache::forget($key);
                Cache::forget($attemptsKey);
            }

            return $this->apiResponse(
                false,
                $left > 0
                    ? "Incorrect code. {$left} " . ($left === 1 ? 'attempt' : 'attempts') . ' remaining.'
                    : 'Too many incorrect attempts. Please request a new code.',
                null,
                422
            );
        }

        Cache::forget($key);
        Cache::forget($attemptsKey);

        // Store a short-lived flag so the registration endpoint can confirm
        // this email was actually verified before allowing account creation.
        Cache::put('otp_verified:' . $request->email, true, now()->addMinutes(30));

        return $this->apiResponse(true, 'Code verified.');
    }

    // Change password for the authenticated rider
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required'],
            'password'         => ['required', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($request->current_password, $request->user()->password)) {
            return $this->apiResponse(false, 'Current password is incorrect.', null, 422);
        }

        $request->user()->update(['password' => Hash::make($request->password)]);

        return $this->apiResponse(true, 'Password changed successfully.');
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'full_name'     => ['sometimes', 'string', 'max:255'],
            'email'         => ['sometimes', 'email', 'unique:users,email,' . $user->id],
            'phone_number'  => ['sometimes', 'nullable', 'string', 'max:20'],
            'address'       => ['sometimes', 'nullable', 'string', 'max:500'],
            'date_of_birth' => ['sometimes', 'nullable', 'date', 'before:today'],
        ]);

        $user->update($data);

        return $this->apiResponse(true, 'Profile updated successfully', $user->fresh());
    }

    public function updateFcmToken(Request $request): JsonResponse
    {
        $request->validate(['fcm_token' => ['nullable', 'string']]);
        $request->user()->update(['fcm_token' => $request->fcm_token]);
        return $this->apiResponse(true, $request->fcm_token === null
            ? 'Push notifications disabled'
            : 'FCM token updated');
    }
}
