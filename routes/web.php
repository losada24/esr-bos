<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ReferredController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', [DashboardController::class, 'index'])->middleware(['auth']);
Route::get('/referred/{reference_code}/create', [ReferredController::class, 'create'])->name('referred.create');
Route::post('/referred/store', [ReferredController::class, 'store'])->name('referred.store');


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // USERS
    Route::resource('user', UserController::class);
    // REDERRED
    Route::resource('referred', ReferredController::class, [
        'only' => ['index']        
    ])->middleware(['role:client|admin']);

    Route::resource('referred', ReferredController::class, [
        'only' => ['edit', 'update', 'destroy']        
    ])->middleware(['role:admin']);
});

require __DIR__.'/auth.php';
require __DIR__.'/socialite.php';
