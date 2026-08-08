<?php

use App\Http\Controllers\Web\AccountDeletionController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\FavoriteController;
use App\Http\Controllers\Web\FollowAlongController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\LocaleController;
use App\Http\Controllers\Web\PlaylistController;
use App\Http\Controllers\Web\ReciterController;
use App\Http\Controllers\Web\StoryController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/reciters', [ReciterController::class, 'index'])->name('reciters.index');
Route::get('/reciters/{reciter}', [ReciterController::class, 'show'])->name('reciters.show');
Route::get('/story', StoryController::class)->name('story');
Route::view('/privacy', 'privacy')->name('privacy');
Route::get('/account-deletion', [AccountDeletionController::class, 'show'])->name('account-deletion');
Route::get('/account-deletion/done', [AccountDeletionController::class, 'done'])->name('account-deletion.done');
Route::delete('/account-deletion', [AccountDeletionController::class, 'destroy'])->name('account-deletion.destroy');
Route::get('/locale/{locale}', LocaleController::class)->name('locale.switch');
Route::get('/listen/{recitation}', [FollowAlongController::class, 'show'])->name('follow-along.show');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->prefix('library')->name('library.')->group(function () {
    Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites');
    Route::post('/favorites', [FavoriteController::class, 'store'])->name('favorites.store');
    Route::delete('/favorites/{recitation}', [FavoriteController::class, 'destroy'])->name('favorites.destroy');

    Route::get('/playlists', [PlaylistController::class, 'index'])->name('playlists');
    Route::post('/playlists', [PlaylistController::class, 'store'])->name('playlists.store');
    Route::get('/playlists/{playlist}', [PlaylistController::class, 'show'])->name('playlists.show');
    Route::put('/playlists/{playlist}', [PlaylistController::class, 'update'])->name('playlists.update');
    Route::delete('/playlists/{playlist}', [PlaylistController::class, 'destroy'])->name('playlists.destroy');
    Route::post('/playlists/{playlist}/items', [PlaylistController::class, 'addItem'])->name('playlists.items.store');
    Route::delete('/playlists/{playlist}/items/{item}', [PlaylistController::class, 'removeItem'])->name('playlists.items.destroy');
});
