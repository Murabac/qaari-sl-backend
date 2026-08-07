<?php

use App\Http\Controllers\Api\Staff\AuthController as StaffAuthController;
use App\Http\Controllers\Api\Staff\AyahSyncController as StaffAyahSyncController;
use App\Http\Controllers\Api\Staff\DashboardController as StaffDashboardController;
use App\Http\Controllers\Api\Staff\RecitationController as StaffRecitationController;
use App\Http\Controllers\Api\Staff\ReciterController as StaffReciterController;
use App\Http\Controllers\Api\Staff\ReviewController as StaffReviewController;
use App\Http\Controllers\Api\Staff\SurahController as StaffSurahController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\FavoriteController;
use App\Http\Controllers\Api\V1\PlaylistController;
use App\Http\Controllers\Api\V1\RecitationController;
use App\Http\Controllers\Api\V1\ReciterController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\StoryController;
use App\Http\Controllers\Api\V1\SurahController;
use Illuminate\Support\Facades\Route;

Route::prefix('staff')->group(function (): void {
    Route::post('login', [StaffAuthController::class, 'login']);

    Route::middleware(['auth:sanctum', 'staff'])->group(function (): void {
        Route::post('logout', [StaffAuthController::class, 'logout']);
        Route::get('me', [StaffAuthController::class, 'me']);
        Route::get('dashboard', StaffDashboardController::class);
        Route::get('surahs', [StaffSurahController::class, 'index']);

        Route::get('reciters', [StaffReciterController::class, 'index']);
        Route::post('reciters', [StaffReciterController::class, 'store']);
        Route::get('reciters/{reciter}', [StaffReciterController::class, 'show']);
        Route::put('reciters/{reciter}', [StaffReciterController::class, 'update']);
        Route::post('reciters/{reciter}', [StaffReciterController::class, 'update']);

        Route::get('reciters/{reciter}/recitations', [StaffRecitationController::class, 'indexForReciter']);
        Route::post('reciters/{reciter}/recitations', [StaffRecitationController::class, 'store']);

        Route::get('recitations/{recitation}', [StaffRecitationController::class, 'show']);
        Route::put('recitations/{recitation}', [StaffRecitationController::class, 'update']);
        Route::post('recitations/{recitation}/submit', [StaffRecitationController::class, 'submit']);
        Route::post('recitations/{recitation}/replace-audio', [StaffRecitationController::class, 'replaceAudio']);
        Route::get('recitations/{recitation}/review-notes', [StaffRecitationController::class, 'reviewNotes']);

        Route::get('recitations/{recitation}/ayah-sync', [StaffAyahSyncController::class, 'show']);
        Route::put('recitations/{recitation}/ayah-sync', [StaffAyahSyncController::class, 'save']);
        Route::post('recitations/{recitation}/ayah-sync/auto', [StaffAyahSyncController::class, 'autoSync']);

        Route::get('reviews', [StaffReviewController::class, 'index']);
        Route::post('recitations/{recitation}/approve', [StaffReviewController::class, 'approve']);
        Route::post('recitations/{recitation}/reject', [StaffReviewController::class, 'reject']);
    });
});

Route::prefix('v1')->group(function (): void {
    Route::get('reciters', [ReciterController::class, 'index']);
    Route::get('reciters/{reciter}', [ReciterController::class, 'show']);

    Route::get('surahs', [SurahController::class, 'index']);
    Route::get('surahs/{surah}', [SurahController::class, 'show']);

    Route::get('recitations', [RecitationController::class, 'index']);
    Route::get('recitations/{recitation}/follow-along', [RecitationController::class, 'followAlong']);
    Route::get('recitations/{recitation}', [RecitationController::class, 'show']);

    Route::get('search', SearchController::class);
    Route::get('story', StoryController::class);

    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::delete('auth/account', [AuthController::class, 'destroy']);

        Route::get('favorites', [FavoriteController::class, 'index']);
        Route::post('favorites', [FavoriteController::class, 'store']);
        Route::delete('favorites/{recitation}', [FavoriteController::class, 'destroy']);

        Route::get('playlists', [PlaylistController::class, 'index']);
        Route::post('playlists', [PlaylistController::class, 'store']);
        Route::get('playlists/{playlist}', [PlaylistController::class, 'show']);
        Route::put('playlists/{playlist}', [PlaylistController::class, 'update']);
        Route::patch('playlists/{playlist}', [PlaylistController::class, 'update']);
        Route::delete('playlists/{playlist}', [PlaylistController::class, 'destroy']);
        Route::post('playlists/{playlist}/items', [PlaylistController::class, 'addItem']);
        Route::delete('playlists/{playlist}/items/{item}', [PlaylistController::class, 'removeItem']);
        Route::put('playlists/{playlist}/reorder', [PlaylistController::class, 'reorderItems']);
    });
});
