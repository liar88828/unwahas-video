<?php

namespace Unwahas\Video\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Unwahas\Video\Http\Controllers\VideoRoomController;
use Unwahas\Video\Models\VideoRoom;
use Unwahas\Video\Tests\TestCase;

class VideoRoomControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        Route::get('rooms/{slug}/status', [VideoRoomController::class, 'status'])->name('rooms.status');
    }

    public function test_it_returns_room_status_json(): void
    {
        $room = VideoRoom::create([
            'name' => 'Kelas Algoritma',
            'slug' => 'kelas-algoritma',
            'is_started' => true,
        ]);

        $response = $this->getJson("/rooms/{$room->slug}/status");

        $response->assertOk()
            ->assertJson([
                'exists' => true,
                'is_started' => true,
            ]);
    }

    public function test_it_returns_false_for_non_existent_room_status(): void
    {
        $response = $this->getJson('/rooms/ruang-tidak-ada/status');

        $response->assertOk()
            ->assertJson([
                'exists' => false,
                'is_started' => false,
            ]);
    }
}
