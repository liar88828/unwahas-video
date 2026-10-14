<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Load Package Migrations
    |--------------------------------------------------------------------------
    |
    | Set to false if you publish migrations and prefer to manage them manually.
    |
    */
    'load_migrations' => true,

    /*
    |--------------------------------------------------------------------------
    | Load Package Routes
    |--------------------------------------------------------------------------
    |
    | Automatically register package routes. Set to false if you register
    | custom routes in your application's routes/web.php.
    |
    */
    'load_routes' => false,

    /*
    |--------------------------------------------------------------------------
    | Load Broadcast Channels
    |--------------------------------------------------------------------------
    |
    | Automatically register presence channel authorization callbacks.
    | Set to false if defined in application routes/channels.php.
    |
    */
    'load_channels' => true,

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    */
    'routes' => [
        'prefix' => '',
        'middleware' => ['web', 'auth'],
    ],

    /*
    |--------------------------------------------------------------------------
    | User & Exam Models
    |--------------------------------------------------------------------------
    */
    'user_model' => config('auth.providers.users.model', 'App\\Models\\User'),
    'exam_model' => 'App\\Models\\Exam',

    /*
    |--------------------------------------------------------------------------
    | STUN / TURN WebRTC ICE Servers
    |--------------------------------------------------------------------------
    */
    'ice_servers' => [
        ['urls' => 'stun:stun.l.google.com:19302'],
        ['urls' => 'stun:stun1.l.google.com:19302'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Video Rooms Table
    |--------------------------------------------------------------------------
    */
    'table_names' => [
        'video_rooms' => 'video_rooms',
    ],
];
