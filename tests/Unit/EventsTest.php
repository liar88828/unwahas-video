<?php

namespace Unwahas\Video\Tests\Unit;

use Illuminate\Broadcasting\PresenceChannel;
use PHPUnit\Framework\TestCase;
use Unwahas\Video\Events\ReverbTestPing;
use Unwahas\Video\Events\VideoSignalReceived;

class EventsTest extends TestCase
{
    public function test_reverb_test_ping_event(): void
    {
        $event = new ReverbTestPing(
            data: ['message' => 'ping'],
            senderName: 'Dosen Test',
            timestamp: '10:00:00'
        );

        $channel = $event->broadcastOn();
        $this->assertInstanceOf(PresenceChannel::class, $channel);
        $this->assertSame('presence-reverb-monitoring', $channel->name);
        $this->assertSame('ReverbTestPing', $event->broadcastAs());
    }

    public function test_video_signal_received_event(): void
    {
        $event = new VideoSignalReceived(
            signal: ['type' => 'offer', 'sdp' => 'test-sdp'],
            userId: 123,
            channelName: 'video-room.custom-slug'
        );

        $channel = $event->broadcastOn();
        $this->assertInstanceOf(PresenceChannel::class, $channel);
        $this->assertSame('presence-video-room.custom-slug', $channel->name);
    }

    public function test_video_signal_received_default_channel(): void
    {
        $event = new VideoSignalReceived(
            signal: ['type' => 'candidate'],
            userId: 456
        );

        $channel = $event->broadcastOn();
        $this->assertInstanceOf(PresenceChannel::class, $channel);
        $this->assertSame('presence-video-room.456', $channel->name);
    }
}
