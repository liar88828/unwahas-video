<?php

declare(strict_types=1);

namespace Unwahas\Video\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VideoSignalReceived implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  array<string, mixed>  $signal
     */
    public function __construct(
        public array $signal,
        public int|string $userId,
        public ?string $channelName = null
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel
    {
        if ($this->channelName) {
            return new PresenceChannel($this->channelName);
        }

        return new PresenceChannel('video-room.' . $this->userId);
    }
}
