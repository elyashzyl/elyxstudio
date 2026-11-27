<?php

use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('settings', function () {
        $user = \Illuminate\Support\Facades\Auth::user();
        return redirect($user && ($user->isTeacher() || $user->isSuperadmin())
            ? route('teacher.profile')
            : route('student.profile')
        );
    })->name('settings');

    Route::get('settings/profile', function () {
        $user = \Illuminate\Support\Facades\Auth::user();
        return redirect($user && ($user->isTeacher() || $user->isSuperadmin())
            ? route('teacher.profile')
            : route('student.profile')
        );
    })->name('profile.edit');

    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('settings/profile/customization', [ProfileController::class, 'customize'])->name('profile.customize');
    Route::get('profile/avatar/{user}', [ProfileController::class, 'avatar'])->name('profile.avatar');

    Route::get('settings/security', function () {
        $user = \Illuminate\Support\Facades\Auth::user();
        return redirect($user && ($user->isTeacher() || $user->isSuperadmin())
            ? route('teacher.profile')
            : route('student.profile')
        );
    })->name('security.edit');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');
});

Route::get('.well-known/passkey-endpoints', function () {
    return response()->json([
        'enroll' => route('security.edit'),
        'manage' => route('security.edit'),
    ]);
})->name('well-known.passkeys');
