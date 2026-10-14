<?php

declare(strict_types=1);

namespace Unwahas\Video;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class VideoServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/video.php',
            'video'
        );

        $this->app->singleton(Video::class, function () {
            return new Video();
        });

        $this->app->alias(Video::class, 'unwahas.video');
    }

    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        if (config('video.load_migrations', true)) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }

        if (config('video.load_routes', false)) {
            Route::middleware(config('video.routes.middleware', ['web', 'auth']))
                ->prefix(config('video.routes.prefix', ''))
                ->group(__DIR__ . '/../routes/videoRouter.php');
        }

        if (config('video.load_channels', true) && file_exists(__DIR__ . '/../routes/videoChannel.php')) {
            require __DIR__ . '/../routes/videoChannel.php';
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\Commands\InstallVideoCommand::class,
            ]);

            // Publish configuration
            $this->publishes([
                __DIR__ . '/../config/video.php' => config_path('video.php'),
            ], ['unwahas-video-config', 'video-config']);

            // Publish migrations
            $this->publishes([
                __DIR__ . '/../database/migrations/' => database_path('migrations'),
            ], ['unwahas-video-migrations', 'video-migrations']);

            // Publish routes
            $this->publishes([
                __DIR__ . '/../routes/videoRouter.php' => base_path('routes/videoRouter.php'),
            ], ['unwahas-video-routes', 'video-routes']);

            // Publish broadcast channels
            $this->publishes([
                __DIR__ . '/../routes/videoChannel.php' => base_path('routes/videoChannel.php'),
            ], ['unwahas-video-channels', 'video-channels']);

            // Publish controller
            $this->publishes([
                __DIR__ . '/../stubs/VideoRoomController.stub' => app_path('Http/Controllers/VideoRoomController.php'),
            ], ['unwahas-video-controller', 'video-controller']);

            // Publish model
            $this->publishes([
                __DIR__ . '/../stubs/VideoRoom.stub' => app_path('Models/VideoRoom.php'),
            ], ['unwahas-video-model', 'video-model']);

            // Publish events
            $this->publishes([
                __DIR__ . '/../stubs/VideoSignalReceived.stub' => app_path('Events/VideoSignalReceived.php'),
                __DIR__ . '/../stubs/ReverbTestPing.stub' => app_path('Events/ReverbTestPing.php'),
            ], ['unwahas-video-events', 'video-events']);

            // Publish JavaScript Echo client
            $this->publishes([
                __DIR__ . '/../resources/js/lib/echo.ts' => resource_path('js/lib/echo.ts'),
            ], ['unwahas-video-js', 'video-js']);

            // Publish React WebRTC Hook, Components & Types
            $this->publishes([
                __DIR__ . '/../resources/js/hooks/use-webrtc.ts' => resource_path('js/hooks/use-webrtc.ts'),
                __DIR__ . '/../resources/js/types/webrtc.ts' => resource_path('js/types/webrtc.ts'),
                __DIR__ . '/../resources/js/lib/echo.ts' => resource_path('js/lib/echo.ts'),
                __DIR__ . '/../resources/js/lib/format-date.ts' => resource_path('js/lib/format-date.ts'),
                __DIR__ . '/../resources/js/components/cctv-video-tile.tsx' => resource_path('js/components/cctv-video-tile.tsx'),
                __DIR__ . '/../resources/js/components/cctv-video-grid.tsx' => resource_path('js/components/cctv-video-grid.tsx'),
                __DIR__ . '/../resources/js/components/cctv-video-grid-example.tsx' => resource_path('js/components/cctv-video-grid-example.tsx'),
            ], ['unwahas-video-react', 'video-react']);

            // Publish all in one tag
            $this->publishes([
                __DIR__ . '/../config/video.php' => config_path('video.php'),
                __DIR__ . '/../database/migrations/' => database_path('migrations'),
                __DIR__ . '/../routes/videoRouter.php' => base_path('routes/videoRouter.php'),
                __DIR__ . '/../routes/videoChannel.php' => base_path('routes/videoChannel.php'),
                __DIR__ . '/../stubs/VideoRoomController.stub' => app_path('Http/Controllers/VideoRoomController.php'),
                __DIR__ . '/../stubs/VideoRoom.stub' => app_path('Models/VideoRoom.php'),
                __DIR__ . '/../stubs/VideoSignalReceived.stub' => app_path('Events/VideoSignalReceived.php'),
                __DIR__ . '/../stubs/ReverbTestPing.stub' => app_path('Events/ReverbTestPing.php'),
                __DIR__ . '/../resources/js/lib/echo.ts' => resource_path('js/lib/echo.ts'),
                __DIR__ . '/../resources/js/lib/format-date.ts' => resource_path('js/lib/format-date.ts'),
                __DIR__ . '/../resources/js/hooks/use-webrtc.ts' => resource_path('js/hooks/use-webrtc.ts'),
                __DIR__ . '/../resources/js/types/webrtc.ts' => resource_path('js/types/webrtc.ts'),
                __DIR__ . '/../resources/js/components/cctv-video-tile.tsx' => resource_path('js/components/cctv-video-tile.tsx'),
                __DIR__ . '/../resources/js/components/cctv-video-grid.tsx' => resource_path('js/components/cctv-video-grid.tsx'),
                __DIR__ . '/../resources/js/components/cctv-video-grid-example.tsx' => resource_path('js/components/cctv-video-grid-example.tsx'),
            ], ['unwahas-video', 'video']);
        }
    }
}
