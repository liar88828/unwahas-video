<?php

declare(strict_types=1);

namespace Unwahas\Video\Console\Commands;

use Illuminate\Console\Command;

class InstallVideoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'unwahas-video:install
                            {--force : Overwrite existing files in the application}
                            {--migrate : Run database migrations after publishing}
                            {--no-routes : Do not append routes to routes/web.php and routes/channels.php}';

    /**
     * The aliases of the console command.
     *
     * @var array<int, string>
     */
    protected $aliases = ['unwahas-video:publish', 'video:install', 'video:publish'];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scaffold and publish video controllers, models, events, routes, and config to the application';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Scaffolding UNWAHAS Video library into your application...');

        $force = (bool) $this->option('force');
        $namespace = rtrim($this->laravel->getNamespace(), '\\');

        // 1. Publish Config & Migrations
        $this->comment('1. Publishing configuration and migrations...');
        $this->call('vendor:publish', array_filter([
            '--tag' => 'unwahas-video-config',
            '--force' => $force,
        ]));
        $this->call('vendor:publish', array_filter([
            '--tag' => 'unwahas-video-migrations',
            '--force' => $force,
        ]));

        // 2. Scaffold Controllers
        $this->comment('2. Scaffolding Controller...');
        $this->publishFile(
            __DIR__ . '/../../../stubs/VideoRoomController.stub',
            app_path('Http/Controllers/VideoRoomController.php'),
            $namespace,
            $force
        );

        // 3. Scaffold Models
        $this->comment('3. Scaffolding Model...');
        $this->publishFile(
            __DIR__ . '/../../../stubs/VideoRoom.stub',
            app_path('Models/VideoRoom.php'),
            $namespace,
            $force
        );

        // 4. Scaffold Events
        $this->comment('4. Scaffolding Events...');
        $this->publishFile(
            __DIR__ . '/../../../stubs/VideoSignalReceived.stub',
            app_path('Events/VideoSignalReceived.php'),
            $namespace,
            $force
        );
        $this->publishFile(
            __DIR__ . '/../../../stubs/ReverbTestPing.stub',
            app_path('Events/ReverbTestPing.php'),
            $namespace,
            $force
        );

        // 5. Scaffold Routes & Channels
        $this->comment('5. Scaffolding Routes & Channels...');
        $this->publishFile(
            __DIR__ . '/../../../stubs/videoRouter.stub',
            base_path('routes/videoRouter.php'),
            $namespace,
            $force
        );
        $this->publishFile(
            __DIR__ . '/../../../stubs/videoChannel.stub',
            base_path('routes/videoChannel.php'),
            $namespace,
            $force
        );

        // 6. Scaffold JavaScript Echo client & React WebRTC Hook
        $this->comment('6. Scaffolding JavaScript Echo client and React WebRTC Hook...');
        $this->publishFile(
            __DIR__ . '/../../../stubs/echo.stub',
            resource_path('js/lib/echo.ts'),
            $namespace,
            $force
        );
        $this->publishFile(
            __DIR__ . '/../../../stubs/webrtc-types.stub',
            resource_path('js/types/webrtc.ts'),
            $namespace,
            $force
        );
        $this->publishFile(
            __DIR__ . '/../../../stubs/use-webrtc.stub',
            resource_path('js/hooks/use-webrtc.ts'),
            $namespace,
            $force
        );
        $this->publishFile(
            __DIR__ . '/../../../stubs/format-date.stub',
            resource_path('js/lib/format-date.ts'),
            $namespace,
            $force
        );
        $this->publishFile(
            __DIR__ . '/../../../stubs/cctv-video-tile.stub',
            resource_path('js/components/cctv-video-tile.tsx'),
            $namespace,
            $force
        );
        $this->publishFile(
            __DIR__ . '/../../../stubs/cctv-video-grid.stub',
            resource_path('js/components/cctv-video-grid.tsx'),
            $namespace,
            $force
        );
        $this->publishFile(
            __DIR__ . '/../../../stubs/cctv-video-grid-example.stub',
            resource_path('js/components/cctv-video-grid-example.tsx'),
            $namespace,
            $force
        );

        // 7. Register in web.php and channels.php
        if (! $this->option('no-routes')) {
            $this->comment('7. Linking routes to web.php and channels.php...');
            $this->appendRouteFile(base_path('routes/web.php'), "require __DIR__.'/videoRouter.php';");
            $this->appendRouteFile(base_path('routes/channels.php'), "require __DIR__.'/videoChannel.php';");
        }

        // 8. Run Migrations if requested
        if ($this->option('migrate')) {
            $this->comment('8. Running database migrations...');
            $this->call('migrate');
        }

        $this->newLine();
        $this->info('✅ UNWAHAS Video library published successfully to your application!');
        $this->line('Created files:');
        $this->line(' - <info>config/video.php</info>');
        $this->line(' - <info>app/Http/Controllers/VideoRoomController.php</info>');
        $this->line(' - <info>app/Models/VideoRoom.php</info>');
        $this->line(' - <info>app/Events/VideoSignalReceived.php</info>');
        $this->line(' - <info>app/Events/ReverbTestPing.php</info>');
        $this->line(' - <info>routes/videoRouter.php</info>');
        $this->line(' - <info>routes/videoChannel.php</info>');
        $this->line(' - <info>resources/js/lib/echo.ts</info>');
        $this->line(' - <info>resources/js/lib/format-date.ts</info>');
        $this->line(' - <info>resources/js/types/webrtc.ts</info>');
        $this->line(' - <info>resources/js/hooks/use-webrtc.ts</info>');
        $this->line(' - <info>resources/js/components/cctv-video-tile.tsx</info>');
        $this->line(' - <info>resources/js/components/cctv-video-grid.tsx</info>');

        return self::SUCCESS;
    }

    /**
     * Publish a file from a stub with namespace replacement.
     */
    protected function publishFile(string $stubPath, string $destinationPath, string $namespace, bool $force = false): void
    {
        if (file_exists($destinationPath) && ! $force) {
            $this->line("   <comment>Skipped</comment> {$destinationPath} (already exists, use --force to overwrite)");
            return;
        }

        $directory = dirname($destinationPath);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $content = file_get_contents($stubPath);
        $content = str_replace('{{namespace}}', $namespace, $content);

        file_put_contents($destinationPath, $content);
        $this->line("   <info>Created</info> {$destinationPath}");
    }

    /**
     * Append require statement to target route file if exists and not already present.
     */
    protected function appendRouteFile(string $filePath, string $requireStatement): void
    {
        if (! file_exists($filePath)) {
            return;
        }

        $content = file_get_contents($filePath);
        if (! str_contains($content, $requireStatement)) {
            file_put_contents($filePath, PHP_EOL . $requireStatement . PHP_EOL, FILE_APPEND);
            $this->line("   <info>Updated</info> {$filePath}");
        }
    }
}
