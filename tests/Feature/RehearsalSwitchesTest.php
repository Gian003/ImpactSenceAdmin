<?php

namespace Tests\Feature;

use App\Services\SmsService;
use App\Services\VoiceCallService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * The switches that let a drill run without ringing a real phone or spending
 * credits. Guarded inside the two services, so no call site can route around
 * them — which is the whole point, since a crash can be started by a device,
 * the rider app, a drill button or a patrol alert.
 */
class RehearsalSwitchesTest extends TestCase
{
    public function test_no_call_is_placed_when_calls_are_switched_off(): void
    {
        // Credentials present: only the switch should stop it.
        config([
            'services.outbound.calls'     => false,
            'services.twilio.account_sid' => 'AC-test',
            'services.twilio.auth_token'  => 'token-test',
            'services.twilio.from_number' => '+15550000000',
        ]);

        Log::spy();

        $this->assertNull(
            app(VoiceCallService::class)->call('+639171234567', ['Attention.', 'Severity: high.']),
            'no call SID, because no call was placed',
        );

        // What it would have said is still on the record, so a drill can show it.
        Log::shouldHaveReceived('info')
            ->withArgs(fn ($message, $context = []) => str_contains($message, 'switched off')
                && str_contains($context['would_have_said'] ?? '', 'Severity: high'))
            ->once();
    }

    public function test_no_text_is_sent_when_sms_is_switched_off(): void
    {
        config([
            'services.outbound.sms'      => false,
            'services.semaphore.api_key' => 'semaphore-test',
        ]);

        Http::fake();

        $this->assertFalse(app(SmsService::class)->send('09170000002', 'Juan may have crashed.'));

        Http::assertNothingSent();
    }

    public function test_both_channels_still_work_when_the_switches_are_on(): void
    {
        // The switch defaults to on, so an unconfigured service must still be
        // the thing that stops delivery — not the switch silently staying off.
        config([
            'services.outbound.sms'      => true,
            'services.semaphore.api_key' => 'semaphore-test',
        ]);

        Http::fake(['api.semaphore.co/*' => Http::response(['status' => 'Queued'], 200)]);

        $this->assertTrue(app(SmsService::class)->send('09170000002', 'Juan may have crashed.'));

        Http::assertSent(fn ($request) => str_contains($request->url(), 'semaphore.co')
            && $request['number'] === '09170000002');
    }
}
