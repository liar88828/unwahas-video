<?php

declare(strict_types=1);

namespace Unwahas\Video\Facades;

use Illuminate\Support\Facades\Facade;

class Video extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'unwahas.video';
    }
}
