<?php

use App\Http\Controllers\DashboardConrtoller;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::middleware(['auth','verified'])->group(function(){
Route::get('/logout',[UserController::class,'logout'])->name('logout');
});

Route::middleware(['auth','verified'])->group(function(){
Route::get('/dashboard',[DashboardConrtoller::class,'dashboard'])->name('dashboard');
Route::get('/dashboard-body',[DashboardConrtoller::class,'dashboardbody'])->name('dashboardbody');
});

// Route::get('/dashboard', function () {
//     return view('dashboard.dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

// Route::middleware('auth')->group(function () {
//     Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
//     Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
//     Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
// });

require __DIR__.'/auth.php';