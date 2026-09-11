<?php

namespace App\Jobs;

use App\Services\FcmService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * One FCM push, off the request thread.
 *
 * The crash-report endpoint used to make this call inline: an ESP32 that had
 * just detected an impact waited for Google to answer before it was told its
 * report had been saved. A slow or unreachable FCM cost the device seconds on
 * the one request in the system where seconds matter.
 *
 * Deliberately takes a raw token rather than a model. The token is a snapshot
 * of where to send at the moment the event happened; if the device
 * re-registers between queueing and sending, delivering to the old address and
 * failing is more honest than silently retargeting a notification about a
 * crash to whatever address is current.
 */
class SendPushNotification implements ShouldQueue
{
    use Queueable;

    /**
     * Three attempts with a widening gap. A push that cannot be delivered
     * after roughly a minute of trying is not going to be useful to someone
     * standing at a crash anyway.
     */
    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [5, 20];

    public function __construct(
        private readonly string $token,
        private readonly string $title,
        private readonly string $body,
        private readonly array $data = [],
    ) {}

    public function handle(FcmService $fcm): void
    {
        // FcmService already swallows network faults and returns false, so a
        // transient outage is logged there rather than burning a retry. A
        // throw here means something genuinely unexpected, which is worth
        // retrying and then failing loudly.
        $sent = $fcm->sendToToken($this->token, $this->title, $this->body, $this->data);

        if (! $sent) {
            Log::warning('Push not delivered', [
                'type'  => $this->data['type'] ?? 'unknown',
                'title' => $this->title,
            ]);
        }
    }
}
