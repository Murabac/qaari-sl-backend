<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\FavoriteController;
use App\Http\Controllers\Api\V1\PlaylistController;
use App\Http\Controllers\Api\V1\RecitationController;
use App\Http\Controllers\Api\V1\ReciterController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\StoryController;
use App\Http\Controllers\Api\V1\SurahController;
use Illuminate\Support\Facades\Route;

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
