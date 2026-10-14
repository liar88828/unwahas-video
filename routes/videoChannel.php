<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels for Video & Real-time WebRTC
|--------------------------------------------------------------------------
|
| Channel authorization callbacks required for WebRTC signaling and Reverb monitoring.
|
*/

// Presence channel for video meeting rooms (used by VideoRoomController & WebRTC signaling)
Broadcast::channel('video-room.{roomId}', function ($user, string $roomId): ?array {
    if (! $user) {
        return null;
    }

    return [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email ?? null,
        'role' => $user->role ?? (method_exists($user, 'isDosen') && $user->isDosen() ? 'dosen' : 'mahasiswa'),
    ];
});

// Presence channel for Reverb WebSocket monitoring and ping tests
Broadcast::channel('reverb-monitoring', function ($user): ?array {
    if (! $user) {
        return null;
    }

    return [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email ?? null,
        'role' => $user->role ?? (method_exists($user, 'isDosen') && $user->isDosen() ? 'dosen' : 'mahasiswa'),
    ];
});
