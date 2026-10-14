# UNWAHAS Video Library

Video processing and management library for PHP / Laravel with WebRTC signaling and room management.

## Requirements

- PHP >= 8.2
- Laravel 10.x, 11.x, 12.x, or 13.x (`illuminate/database` & `illuminate/support`)
- Inertia.js Laravel `^2.0 || ^3.0` (`inertiajs/inertia-laravel`)
- Laravel Reverb (or any Pusher-compatible WebSocket server)

## Installation

```bash
composer require unwahas/video
```

## Laravel Integration

The package registers automatically via Laravel package discovery:

- **Service Provider:** `Unwahas\Video\VideoServiceProvider`
- **Facade:** `Unwahas\Video\Facades\Video` (aliased as `Video`)

If you don't use package auto-discovery, you can register them manually in `config/app.php` (or `bootstrap/providers.php` in Laravel 11+):

```php
'providers' => [
    Unwahas\Video\VideoServiceProvider::class,
],

'aliases' => [
    'Video' => Unwahas\Video\Facades\Video::class,
],
```

### Scaffolding & Publishing to Your Application

Running the command below will publish all configurations, migrations, and scaffold the controllers, models, events, routes, channels, and React components directly into your Laravel application:

```bash
php artisan unwahas-video:install
# or alias:
php artisan unwahas-video:publish
```

#### What this command creates in your app:
- `config/video.php`
- `database/migrations/YYYY_MM_DD_HHMMSS_create_video_rooms_table.php`
- `app/Http/Controllers/VideoRoomController.php`
- `app/Models/VideoRoom.php`
- `app/Events/VideoSignalReceived.php`
- `app/Events/ReverbTestPing.php`
- `routes/videoRouter.php` (and links it into `routes/web.php`)
- `routes/videoChannel.php` (and links it into `routes/channels.php`)
- `resources/js/lib/echo.ts` (Laravel Echo & Reverb WebSocket client)
- `resources/js/lib/format-date.ts` (Indonesian locale clock formatter)
- `resources/js/types/webrtc.ts` (TypeScript types for WebRTC)
- `resources/js/hooks/use-webrtc.ts` (React WebRTC hook)
- `resources/js/components/cctv-video-tile.tsx` (CCTV Video Tile component)
- `resources/js/components/cctv-video-grid.tsx` (Dynamic CCTV Grid component)

#### Frontend NPM Requirements
Make sure your application has `laravel-echo`, `pusher-js`, and `lucide-react` installed:
```bash
npm install --save-dev laravel-echo pusher-js lucide-react
```

#### Command Options:
- `--force`: Overwrite existing files in your application.
- `--migrate`: Automatically run database migrations after scaffolding.
- `--no-routes`: Do not automatically append require statements to `routes/web.php` and `routes/channels.php`.

Example:
```bash
php artisan unwahas-video:install --migrate
```

### Manual Individual Publishing via Tags

If you prefer to publish items individually using `--tag=unwahas-video`:

```bash
# Publish Everything in one tag
php artisan vendor:publish --tag=unwahas-video

# Or individually:
php artisan vendor:publish --tag=unwahas-video-config
php artisan vendor:publish --tag=unwahas-video-migrations
php artisan vendor:publish --tag=unwahas-video-controller
php artisan vendor:publish --tag=unwahas-video-model
php artisan vendor:publish --tag=unwahas-video-events
php artisan vendor:publish --tag=unwahas-video-routes
php artisan vendor:publish --tag=unwahas-video-channels
php artisan vendor:publish --tag=unwahas-video-js
php artisan vendor:publish --tag=unwahas-video-react
```

---

## ⚠️ Important Note: Broadcast Channels Setup

WebRTC signaling (`VideoRoomController::signal`) and ping monitoring depend on Laravel's broadcast presence channels.

### Automatic Channel Registration
The package registers these presence channels automatically (configured via `'load_channels' => true` in `config/video.php`).

### Manual Channel Registration (in `routes/channels.php` or `routes/videoChannel.php`)
If you prefer to define them directly in your application's `routes/channels.php` (or after publishing via `php artisan vendor:publish --tag=video-channels` into `routes/videoChannel.php`), ensure the following channels are authorized:

```php
use Illuminate\Support\Facades\Broadcast;

// Presence channel for video meeting room signaling (WebRTC offer, answer, ice-candidates)
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

// Presence channel for Reverb WebSocket monitoring & ping health check
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
```

> **Note**: If your application also has exam monitoring, remember to register `Broadcast::channel('exam-room.{roomId}', ...)` as well.

---

## Routes

You can publish the default room routes or load them directly via config:

```bash
php artisan vendor:publish --tag=video-routes
```

Routes provided:
- `GET /rooms` &rarr; `rooms.index`
- `POST /rooms` &rarr; `rooms.store`
- `GET /rooms/{slug}` &rarr; `rooms.show`
- `POST /rooms/{slug}/start` &rarr; `rooms.start`
- `POST /rooms/{slug}/end` &rarr; `rooms.end`
- `GET /rooms/{slug}/status` &rarr; `rooms.status`
- `POST /rooms/signal` &rarr; `rooms.signal`

---

## Usage & Models

### Video Facade

```php
use Video; // or use Unwahas\Video\Facades\Video;

$version = Video::version(); // "1.0.x"
```

### VideoRoom Model

```php
use Unwahas\Video\Models\VideoRoom;

$room = VideoRoom::create([
    'name' => 'Ruang Kuliah',
    'slug' => 'ruang-kuliah',
    'is_started' => true,
    'started_at' => now(),
    'user_id' => $user->id,
]);

// Helper methods:
$room->startRoom(); // Sets is_started = true and started_at = now()
$room->endRoom();   // Sets is_started = false
```

---

## React WebRTC Hook (`useWebRTC`)

The library provides a production-ready React hook for WebRTC mesh networking, audio/video toggle, screen sharing, resolution switching (`720p`, `480p`, `360p`, `240p`), and dual-path signaling (Echo presence whisper + HTTP fallback).

### Publishing to React frontend:
```bash
php artisan vendor:publish --tag=unwahas-video-react
```
Files created:
- `resources/js/hooks/use-webrtc.ts`
- `resources/js/types/webrtc.ts`
- `resources/js/lib/echo.ts`
- `resources/js/lib/format-date.ts`
- `resources/js/components/cctv-video-tile.tsx`
- `resources/js/components/cctv-video-grid.tsx`

### React Component Example:

```tsx
import { useWebRTC } from '@/hooks/use-webrtc';

export function VideoRoomComponent({ room, currentUser }) {
    const {
        localStream,
        remoteStreams,
        peers,
        isAudioMuted,
        isVideoDisabled,
        isScreenSharing,
        activeScreenShareUserId,
        toggleAudio,
        toggleVideo,
        toggleScreenShare,
        videoQuality,
        changeVideoQuality,
        connectionStatus,
        errorMessage,
    } = useWebRTC({
        roomSlug: room.slug,
        channelName: `video-room.${room.slug}`,
        currentUser: {
            id: String(currentUser.id),
            name: currentUser.name,
            role: currentUser.role,
        },
    });

    // ... render local and remote video elements
}
```

---

## Testing

```bash
composer test
# or
./vendor/bin/phpunit
```

## License

The MIT License (MIT).
