<?php

use App\Enum\RoleEnum;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\EstimateController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RawMaterialController;
use App\Http\Controllers\FixedWindowsController;

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

Route::get('/', [DashboardController::class, 'index'])->middleware(['auth'])->name('dashboard');

/*Route::get('/mailable', function () {

  dd(config('custom', 'admin_emails'));

  $referrer = App\Models\Referred::find(1);

  return new App\Mail\NewReferred($referrer);
});*/

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // USERS
    Route::resource('user', UserController::class)
      ->only(['index', 'create', 'store'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$CLIENT_ADMIN]);
    
    Route::resource('user', UserController::class)
      ->only(['edit', 'update', 'destroy'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$CLIENT_ADMIN, 'checkUserCreatedByField']);

    // CLIENTS
    Route::resource('client', ClientController::class)
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$CLIENT_ADMIN]);

    // RAW MATERIALS
    Route::resource('raw-material', RawMaterialController::class)
      ->middleware(["role:" . RoleEnum::$ADMIN]);
    
    // ESTIMATES
    Route::resource('estimate', EstimateController::class)
      ->only(['index', 'create', 'store'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$CLIENT_ADMIN . "|" . RoleEnum::$CLIENT]);

    Route::resource('estimate', EstimateController::class)
      ->only(['edit', 'update', 'destroy'])
      ->middleware([
        "role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$CLIENT_ADMIN . "|" . RoleEnum::$CLIENT,
        "validate.order.owner"
      ]);

    Route::resource('estimate', EstimateController::class)
      ->only(['show'])
      ->middleware([
        "role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$CLIENT_ADMIN . "|" . RoleEnum::$CLIENT // TODO: Validate if the user is the owner of the order
      ]);

    // PRODUCTS
    /* Route::get('/product/{id}', [ProductController::class, 'index'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$CLIENT_ADMIN . "|" . RoleEnum::$CLIENT]) // TODO: Validate if the user is the owner of the order
      ->name('product.index'); */
    
    // FIX WINDOWS
    Route::get('/fixed-windows/{id}/create', [FixedWindowsController::class, 'create'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$CLIENT_ADMIN . "|" . RoleEnum::$CLIENT]) // TODO: Validate if the user is the owner of the order
      ->name('fixed-windows.create');
    
    Route::post('/fix-windows/store', [FixedWindowsController::class, 'store'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$CLIENT_ADMIN . "|" . RoleEnum::$CLIENT])
      ->name('fixed-windows.store');

    
});

require __DIR__.'/auth.php';
// require __DIR__.'/socialite.php';
