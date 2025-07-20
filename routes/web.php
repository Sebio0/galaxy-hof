<?php

use App\Http\Controllers\RankingController;
use App\Http\Controllers\EternalRankingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Default route - redirect to rankings
Route::get('/', function () {
    return redirect()->route('ranking.index');
});

// Authentication Routes (already defined in auth.php)
require __DIR__.'/auth.php';

// Ranking Routes
Route::prefix('rankings')->group(function () {
    // Main ranking routes
    Route::get('/', [RankingController::class, 'index'])->name('ranking.index');
    Route::post('/filter', [RankingController::class, 'filter'])->name('ranking.filter');

    // Player specific rankings
    Route::get('/player/{hofUserId}/{roundId}', [RankingController::class, 'playerRankings'])
        ->name('ranking.player');

    // Combined rankings for a round
    Route::get('/combined/{roundId}', [RankingController::class, 'combinedRankings'])
        ->name('ranking.combined');

    // Eternal Ranking Routes
    Route::get('/eternal', [EternalRankingController::class, 'index'])
        ->name('ranking.eternal.index');
    Route::post('/eternal/table', [EternalRankingController::class, 'table'])
        ->name('ranking.eternal.table');
    Route::post('/eternal/search', [EternalRankingController::class, 'search'])
        ->name('ranking.eternal.search');
    Route::post('/eternal/round', [EternalRankingController::class, 'filterRound'])
        ->name('ranking.eternal.round');
});

// Dashboard route (assuming it exists based on the dashboard.blade.php view)
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');
