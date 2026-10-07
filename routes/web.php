<?php

use App\Http\Controllers\DealerApplicationController;
use App\Http\Controllers\DealerController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/apply', [DealerApplicationController::class, 'create'])->name('dealers.apply');
Route::post('/apply', [DealerApplicationController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('dealers.apply.store');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::patch('/users/{user}', [UserController::class, 'update'])->name('users.update');

    Route::get('/dealers', [DealerController::class, 'index'])->name('dealers.index');
    Route::get('/dealers/create', [DealerController::class, 'create'])->name('dealers.create');
    Route::post('/dealers', [DealerController::class, 'store'])->name('dealers.store');
    Route::get('/dealers/{dealer}', [DealerController::class, 'show'])->name('dealers.show');
    Route::get('/dealers/{dealer}/edit', [DealerController::class, 'edit'])->name('dealers.edit');
    Route::patch('/dealers/{dealer}', [DealerController::class, 'update'])->name('dealers.update');
    Route::post('/dealers/{dealer}/approve', [DealerController::class, 'approve'])->name('dealers.approve');
    Route::post('/dealers/{dealer}/reject', [DealerController::class, 'reject'])->name('dealers.reject');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
