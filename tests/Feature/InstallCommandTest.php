<?php

namespace Unwahas\Video\Tests\Feature;

use Unwahas\Video\Tests\TestCase;

class InstallCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        // Clean up any files generated in test environment
        $filesToClean = [
            app_path('Http/Controllers/VideoRoomController.php'),
            app_path('Models/VideoRoom.php'),
            app_path('Events/VideoSignalReceived.php'),
            app_path('Events/ReverbTestPing.php'),
            base_path('routes/videoRouter.php'),
            base_path('routes/videoChannel.php'),
            resource_path('js/lib/echo.ts'),
            resource_path('js/lib/format-date.ts'),
            resource_path('js/hooks/use-webrtc.ts'),
            resource_path('js/types/webrtc.ts'),
            resource_path('js/components/cctv-video-tile.tsx'),
            resource_path('js/components/cctv-video-grid.tsx'),
        ];

        foreach ($filesToClean as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }

        parent::tearDown();
    }

    public function test_it_can_scaffold_video_package_into_application(): void
    {
        $this->artisan('unwahas-video:install --force')
            ->expectsOutputToContain('Scaffolding UNWAHAS Video library into your application...')
            ->expectsOutputToContain('1. Publishing configuration and migrations...')
            ->expectsOutputToContain('2. Scaffolding Controller...')
            ->expectsOutputToContain('3. Scaffolding Model...')
            ->expectsOutputToContain('4. Scaffolding Events...')
            ->expectsOutputToContain('5. Scaffolding Routes & Channels...')
            ->expectsOutputToContain('6. Scaffolding JavaScript Echo client and React WebRTC Hook...')
            ->assertSuccessful();

        $this->assertFileExists(app_path('Http/Controllers/VideoRoomController.php'));
        $this->assertFileExists(app_path('Models/VideoRoom.php'));
        $this->assertFileExists(app_path('Events/VideoSignalReceived.php'));
        $this->assertFileExists(app_path('Events/ReverbTestPing.php'));
        $this->assertFileExists(base_path('routes/videoRouter.php'));
        $this->assertFileExists(base_path('routes/videoChannel.php'));
        $this->assertFileExists(resource_path('js/lib/echo.ts'));
        $this->assertFileExists(resource_path('js/lib/format-date.ts'));
        $this->assertFileExists(resource_path('js/hooks/use-webrtc.ts'));
        $this->assertFileExists(resource_path('js/types/webrtc.ts'));
        $this->assertFileExists(resource_path('js/components/cctv-video-tile.tsx'));
        $this->assertFileExists(resource_path('js/components/cctv-video-grid.tsx'));
    }

    public function test_it_can_run_using_unwahas_video_publish_alias(): void
    {
        $this->artisan('unwahas-video:publish --force')
            ->assertSuccessful();
    }

    public function test_it_can_run_using_legacy_video_install_alias(): void
    {
        $this->artisan('video:install --force')
            ->assertSuccessful();
    }
}
