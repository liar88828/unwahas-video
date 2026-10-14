<?php

declare(strict_types=1);

namespace Unwahas\Video\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Unwahas\Video\Events\VideoSignalReceived;
use Unwahas\Video\Models\VideoRoom;

class VideoRoomController extends Controller
{
    /**
     * Check if user has dosen/host privileges.
     */
    protected function isDosen(mixed $user): bool
    {
        if (! $user) {
            return false;
        }

        if (method_exists($user, 'isDosen')) {
            return (bool) $user->isDosen();
        }

        return ($user->role ?? null) === 'dosen';
    }

    /**
     * Display a listing of video rooms.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $rooms = VideoRoom::query()
            ->with('user:id,name,role')
            ->latest()
            ->take(20)
            ->get(['id', 'name', 'slug', 'user_id', 'is_started', 'started_at', 'created_at']);

        return Inertia::render('rooms/index', [
            'rooms' => $rooms,
            'isDosen' => $this->isDosen($user),
        ]);
    }

    /**
     * Store a newly created video room (Only Dosen can create).
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $this->isDosen($user)) {
            abort(403, 'Hanya Dosen yang dapat membuat ruang perkuliahan.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $baseSlug = Str::slug($validated['name']);
        if ($baseSlug === '') {
            $baseSlug = 'kelas';
        }

        $slug = $baseSlug . '-' . Str::lower(Str::random(6));

        $room = VideoRoom::query()->create([
            'name' => $validated['name'],
            'slug' => $slug,
            'user_id' => $user->id,
            'is_started' => true,
            'started_at' => now(),
        ]);

        return redirect()->route('rooms.show', ['slug' => $room->slug]);
    }

    /**
     * Display the specified video call room.
     */
    public function show(Request $request, string $slug): Response
    {
        $user = $request->user();
        $isDosen = $this->isDosen($user);

        $room = VideoRoom::query()
            ->with('user:id,name,role')
            ->where('slug', $slug)
            ->first();

        if (! $room) {
            if ($isDosen) {
                $room = VideoRoom::query()->create([
                    'name' => Str::title(str_replace('-', ' ', $slug)),
                    'slug' => $slug,
                    'user_id' => $user->id,
                    'is_started' => true,
                    'started_at' => now(),
                ]);
            } else {
                abort(404, 'Ruang perkuliahan tidak ditemukan.');
            }
        }

        // If the Dosen enters, automatically activate/start the room
        if ($isDosen && ! $room->is_started) {
            $room->startRoom();
        }

        return Inertia::render('rooms/show', [
            'room' => [
                'id' => $room->id,
                'name' => $room->name,
                'slug' => $room->slug,
                'is_started' => (bool) $room->is_started,
                'started_at' => $room->started_at?->toIso8601String(),
                'dosen' => $room->user ? [
                    'id' => $room->user->id,
                    'name' => $room->user->name,
                ] : null,
            ],
            'isDosen' => $isDosen,
            'iceServers' => config('video.ice_servers', [
                ['urls' => 'stun:stun.l.google.com:19302'],
                ['urls' => 'stun:stun1.l.google.com:19302'],
            ]),
        ]);
    }

    /**
     * Start the video room meeting (Dosen only).
     */
    public function start(Request $request, string $slug): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        if (! $this->isDosen($user)) {
            abort(403, 'Hanya Dosen yang dapat memulai perkuliahan.');
        }

        $room = VideoRoom::query()->where('slug', $slug)->firstOrFail();
        $room->startRoom();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_started' => true,
            ]);
        }

        return redirect()->back();
    }

    /**
     * End the video room meeting (Dosen only).
     */
    public function end(Request $request, string $slug): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        if (! $this->isDosen($user)) {
            abort(403, 'Hanya Dosen yang dapat mengakhiri perkuliahan.');
        }

        $room = VideoRoom::query()->where('slug', $slug)->firstOrFail();
        $room->endRoom();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_started' => false,
            ]);
        }

        return redirect()->route('rooms.index');
    }

    /**
     * Get current status of a room (for waiting room polling/updates).
     */
    public function status(string $slug): JsonResponse
    {
        $room = VideoRoom::query()->where('slug', $slug)->first();

        return response()->json([
            'exists' => (bool) $room,
            'is_started' => (bool) ($room?->is_started ?? false),
        ]);
    }

    /**
     * Broadcast WebRTC signaling data (offer, answer, ice-candidate) to target user via Reverb.
     */
    public function signal(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'target_id' => ['required'],
            'signal' => ['required', 'array'],
            'room_slug' => ['nullable', 'string'],
        ]);

        $roomSlug = $validated['room_slug'] ?? $request->input('room_slug');
        $channelName = $roomSlug ? "video-room.{$roomSlug}" : null;

        Log::info('[room-signal] dispatch', [
            'from' => $request->user()?->id,
            'to' => $validated['target_id'],
            'type' => $validated['signal']['type'] ?? null,
            'channel' => $channelName,
        ]);

        try {
            broadcast(new VideoSignalReceived(
                signal: $validated['signal'],
                userId: $validated['target_id'],
                channelName: $channelName
            ))->toOthers();
        } catch (\Throwable $e) {
            Log::error('[room-signal] broadcast failed', [
                'channel' => $channelName,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }

        return response()->json(['success' => true]);
    }
}
