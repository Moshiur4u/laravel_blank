<?php

use App\Http\Controllers\BrandController;
use App\Http\Controllers\DashboardConrtoller;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });
Route::get('/', function () {
    return view('frontend.loginpage.login');
});
Route::middleware(['auth','verified'])->group(function(){
Route::get('/logout',[UserController::class,'logout'])->name('logout');

Route::middleware(['auth','verified'])->group(function(){
route::get('/user/index',[UserController::class,'index'])->name('user.index');
route::get('/user/create',[UserController::class,'create'])->name('user.create');
route::post('/user/store',[UserController::class,'store'])->name('user.store');
route::get('/user/edit/{id}',[UserController::class,'edit'])->name('user.edit');
route::put('/user/update/{id}',[UserController::class,'update'])->name('user.update');
});

//roll-permission route work in here
Route::get('/roles',[RoleController::class,'index'])->middleware('permission:role-menu|view-role-list')->name('roles.index');
Route::get('/roles.create',[RoleController::class,'create'])->middleware('permission:create-role')->name('roles.create');
Route::post('/roles.store',[RoleController::class,'store'])->middleware('permission:create-role')->name('roles.store');
Route::get('/roles/{id}/edit',[RoleController::class,'edit'])->middleware('permission:edit-role-permissions')->name('roles.edit');
Route::put('/roles/{id}/update',[RoleController::class,'update'])->middleware('permission:edit-role-permissions')->name('roles.update');
Route::get('/roles.destroy/{id}',[RoleController::class,'destroy'])->middleware('permission:delete-role')->name('roles.destroy');
});
//product-
Route::middleware(['auth','verified'])->group(function(){
    route::get('/product/index',[ProductCategoryController::class,'index'])->name('category.index');
    route::get('/productCategory/create',[ProductCategoryController::class,'create'])->name('category.create');
    route::post('/productCategory/store',[ProductCategoryController::class,'store'])->name('category.store');
    route::get('/productCategory/{id}',[ProductCategoryController::class,'edit'])->name('category.edit');
    route::put('/productCategory/{id}/update',[ProductCategoryController::class,'update'])->name('category.update');
    route::get('/productCategory/destroy/{id}',[ProductCategoryController::class,'destroy'])->name('category.destroy');
});

//Brand Route Start Here-----------
Route::middleware(['auth','verified'])->group(function(){
    route::get('/product/brand/index',[BrandController::class,'index'])->name('brand.index');
    route::get('/product/brand/create',[BrandController::class,'create'])->name('brand.create');
    route::Post('/product/brand/store',[BrandController::class,'store'])->name('brand.store');
    route::get('/product/brand/{id}/edit',[BrandController::class,'edit'])->name('brand.edit');
    route::put('/product/brand/{id}/update',[BrandController::class,'update'])->name('brand.update');
    route::get('/product/brand/{id}/destroy',[BrandController::class,'destroy'])->name('brand.destroy');
});
Route::middleware(['auth','verified'])->group(function(){
    route::get('/product/index',[ProductController::class,'index'])->name('product.index');
    route::get('/product/create',[ProductController::class,'create'])->name('product.create');
    route::Post('/product/store',[ProductController::class,'store'])->name('product.store');
    route::get('/product/edit/{id}',[ProductController::class,'edit'])->name('product.edit');
    // route::get('/product/update/{id}',[ProductController::class,'update'])->name('product.update');
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
