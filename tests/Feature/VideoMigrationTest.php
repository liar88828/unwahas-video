<?php

namespace Unwahas\Video\Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Unwahas\Video\Models\VideoRoom;
use Unwahas\Video\Tests\TestCase;

class VideoMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Run migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    public function test_it_creates_video_rooms_table(): void
    {
        $this->assertTrue(Schema::hasTable('video_rooms'));
        $this->assertTrue(Schema::hasColumns('video_rooms', [
            'id',
            'name',
            'slug',
            'is_started',
            'started_at',
            'user_id',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_it_can_create_a_video_room_model(): void
    {
        $room = VideoRoom::create([
            'name' => 'Ruang Kuliah Online',
            'slug' => 'ruang-kuliah-online',
            'is_started' => true,
            'started_at' => now(),
        ]);

        $this->assertNotNull($room->id);
        $this->assertSame('Ruang Kuliah Online', $room->name);
        $this->assertSame('ruang-kuliah-online', $room->slug);
        $this->assertTrue($room->is_started);
    }

    public function test_it_can_start_and_end_room(): void
    {
        $room = VideoRoom::create([
            'name' => 'Demo Room',
            'slug' => 'demo-room',
            'is_started' => false,
        ]);

        $this->assertFalse($room->is_started);

        $room->startRoom();
        $this->assertTrue($room->fresh()->is_started);
        $this->assertNotNull($room->fresh()->started_at);

        $room->endRoom();
        $this->assertFalse($room->fresh()->is_started);
    }
}
