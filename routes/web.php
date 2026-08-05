<?php

use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\LocaleController;
use App\Http\Controllers\Web\ReciterController;
use App\Http\Controllers\Web\StoryController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/reciters', [ReciterController::class, 'index'])->name('reciters.index');
Route::get('/reciters/{reciter}', [ReciterController::class, 'show'])->name('reciters.show');
Route::get('/story', StoryController::class)->name('story');
Route::get('/locale/{locale}', LocaleController::class)->name('locale.switch');
