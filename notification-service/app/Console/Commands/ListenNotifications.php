<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

class ListenNotifications extends Command
{
    protected $signature = 'notifications:listen';

    protected $description = 'Listen for notification messages on the Redis queue';

    public function handle(): void
    {
        $this->info('Notification service listening on queue "notifications"...');

        while (true) {
            try {
                // Wait up to 5 seconds for a message, then loop again
                $item = Redis::blpop(['notifications'], 5);
            } catch (Throwable $e) {
                $this->error('Redis error: '.$e->getMessage());
                sleep(3);
                continue;
            }

            if (! $item) {
                continue;
            }

            $message = json_decode($item[1], true);

            if (! is_array($message)) {
                $this->warn('Skipped invalid message');
                continue;
            }

            $text = sprintf(
                'Booking confirmed for %s: slot %s, vehicle %s (booking #%d)',
                $message['email'] ?? 'unknown',
                $message['slot_number'] ?? '-',
                $message['vehicle_number'] ?? '-',
                $message['booking_id'] ?? 0
            );

            Log::info($text, $message);
            $this->line($text);
        }
    }
}
