<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});

// Custom Team Invitation Routes
Route::get('/team-invitations/{invitation}', [App\Http\Controllers\TeamInvitationController::class, 'accept'])
    ->middleware(['signed'])
    ->name('team-invitations.accept');

Route::get('/team-invitations/{invitation}/confirm', [App\Http\Controllers\TeamInvitationController::class, 'acceptWithConfirmation'])
    ->middleware(['signed'])
    ->name('team-invitations.accept-with-confirmation');

Route::post('/team-invitations/{invitation}/confirm', [App\Http\Controllers\TeamInvitationController::class, 'acceptWithConfirmation'])
    ->middleware(['auth'])
    ->name('team-invitations.accept-with-confirmation.post');

// Add GET route for declining invitations
Route::get('/team-invitations/{invitation}/decline', [App\Http\Controllers\TeamInvitationController::class, 'declineWithConfirmation'])
    ->middleware(['signed'])
    ->name('team-invitations.decline');

Route::post('/team-invitations/{invitation}/decline', [App\Http\Controllers\TeamInvitationController::class, 'decline'])
    ->middleware(['auth'])
    ->name('team-invitations.decline.post');
