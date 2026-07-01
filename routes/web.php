<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\CharacterController;
use App\Http\Controllers\CharacterPointsController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\RankingController;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| All routes live inside the localized group so URLs are locale-aware:
| Vietnamese (default) at the root (/...), English under /en/...
*/

// Non-localized utility routes
Route::get('/sitemap.xml', [\App\Http\Controllers\SitemapController::class, 'index'])->name('sitemap');

Route::group([
    'prefix' => LaravelLocalization::setLocale(),
    'middleware' => ['localizationRedirect', 'localeViewPath'],
], function () {

    Auth::routes();

    // --- Public ---
    Route::get('/', [PageController::class, 'home'])->name('home');
    Route::get('/rankings', [RankingController::class, 'index'])->name('rankings.index');
    Route::get('/events', [EventController::class, 'index'])->name('events.index');
    Route::get('/news', [NewsController::class, 'index'])->name('news.index');
    Route::get('/news/{news}', [NewsController::class, 'show'])->name('news.show');

    // --- Authenticated players ---
    Route::middleware('auth')->group(function () {
        Route::get('/home', [HomeController::class, 'index'])->name('dashboard');
        Route::get('/account', [AccountController::class, 'edit'])->name('account.edit');
        Route::put('/account/password', [AccountController::class, 'updatePassword'])->name('account.password');
        Route::put('/account/email', [AccountController::class, 'updateEmail'])->name('account.email');
        Route::put('/account/security-code', [AccountController::class, 'updateSecurityCode'])->name('account.security');

        Route::resource('character', CharacterController::class)->only(['index', 'show']);

        // Character actions (offline-only, ownership-checked in the controller)
        Route::patch('character/{character}/rename', [\App\Http\Controllers\CharacterActionController::class, 'rename'])->name('character.rename');
        Route::post('character/{character}/reset', [\App\Http\Controllers\CharacterActionController::class, 'reset'])->name('character.reset');
        Route::post('character/{character}/clear-pk', [\App\Http\Controllers\CharacterActionController::class, 'clearPk'])->name('character.clearpk');
        Route::post('character/{character}/unstick', [\App\Http\Controllers\CharacterActionController::class, 'unstick'])->name('character.unstick');
        Route::delete('character/{character}', [\App\Http\Controllers\CharacterActionController::class, 'destroy'])->name('character.destroy');

        Route::group(['prefix' => 'character-points'], function () {
            Route::get('{character}/edit', [CharacterPointsController::class, 'edit'])
                ->name('character-points.edit');
            Route::patch('{character}/update', [CharacterPointsController::class, 'update'])
                ->name('character-points.update');
        });
    });

    // --- GM-only admin (data.Account.State in {2,3}) ---
    Route::middleware(['auth', 'isGameMaster'])
        ->prefix('admin')->name('admin.')->group(function () {
            Route::resource('news', \App\Http\Controllers\Admin\NewsController::class)->except('show');
        });
});
