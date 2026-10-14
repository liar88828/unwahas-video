<?php

use Illuminate\Support\Facades\Route;
use Unwahas\Video\Http\Controllers\VideoRoomController;

Route::get('rooms', [VideoRoomController::class, 'index'])->name('rooms.index');
Route::post('rooms', [VideoRoomController::class, 'store'])->name('rooms.store');
Route::get('rooms/{slug}', [VideoRoomController::class, 'show'])->name('rooms.show');
Route::post('rooms/{slug}/start', [VideoRoomController::class, 'start'])->name('rooms.start');
Route::post('rooms/{slug}/end', [VideoRoomController::class, 'end'])->name('rooms.end');
Route::get('rooms/{slug}/status', [VideoRoomController::class, 'status'])->name('rooms.status');
Route::post('rooms/signal', [VideoRoomController::class, 'signal'])->name('rooms.signal');
