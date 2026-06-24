<?php

use App\Http\Controllers\AttendeeController;
use App\Http\Controllers\EventController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/events')->name('home');

// Original raw listing + its JSON feed.
Route::get('events', [EventController::class, 'index'])->name('events.index');
Route::get('events/data', [EventController::class, 'data'])->name('events.data');

// Two distinct browsing experiences and their JSON feeds.
Route::get('events-visual-1', [EventController::class, 'visualGrid'])->name('events.visual1');
Route::get('events-visual-2', [EventController::class, 'visualMap'])->name('events.visual2');
Route::get('events/grid', [EventController::class, 'grid'])->name('events.grid');
Route::get('events/map', [EventController::class, 'map'])->name('events.map');

// Detail + attendee registration. (Wildcard last so it can't shadow the feeds.)
Route::get('events/{event}', [EventController::class, 'show'])->name('events.show');
Route::post('events/{event}/attendees', [AttendeeController::class, 'store'])->name('events.attendees.store');

Route::inertia('dashboard', 'Dashboard')->name('dashboard');

require __DIR__.'/settings.php';
