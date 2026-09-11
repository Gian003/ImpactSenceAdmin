<?php

namespace App\Console\Commands;

use App\Support\QueueHealth;
use Illuminate\Console\Command;

class QueueHealthCheck extends Command
{
    protected $signature = 'queue:health';

    protected $description = 'Report whether crash notifications are actually being delivered';

    /**
     * Exits non-zero when something is wrong, so this can be wired to a
     * scheduled task or a monitor without anything having to parse the text.
     */
    public function handle(): int
    {
        $q = QueueHealth::check();

        $this->newLine();
        $this->line('  Connection : ' . $q->connection);

        $label = strtoupper(str_replace('_', ' ', $q->status));
        match ($q->status) {
            'healthy'    => $this->info("  Status     : {$label}"),
            'not_queued' => $this->line("  Status     : {$label}"),
            'stalled'    => $this->error("  Status     : {$label}"),
            default      => $this->warn("  Status     : {$label}"),
        };

        if ($q->queued) {
            $this->line('  Waiting    : ' . $q->pending);
            $this->line('  Failed     : ' . $q->failed);
        }

        $this->newLine();
        $this->line('  ' . $q->message);
        $this->newLine();

        return in_array($q->status, ['stalled', 'failing'], true)
            ? self::FAILURE
            : self::SUCCESS;
    }
}
