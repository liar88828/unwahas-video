<?php

namespace Unwahas\Video\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Unwahas\Video\Video;

class VideoTest extends TestCase
{
    public function test_can_instantiate_video(): void
    {
        $video = new Video();
        $this->assertSame('1.0.0', $video->version());
    }
}
