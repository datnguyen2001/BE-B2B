<?php

use Illuminate\Support\Facades\Route;
use \App\Http\Controllers\admin\LoginController;
use \App\Http\Controllers\admin\DashboardController;
use \App\Http\Controllers\admin\BannerController;
use \App\Http\Controllers\admin\TrademarkController;
use \App\Http\Controllers\admin\CategoryController;
use \App\Http\Controllers\admin\ProductController;
use \App\Http\Controllers\admin\RequestSupplierController;
use \App\Http\Controllers\admin\OrdersController;
use \App\Http\Controllers\admin\SettingController;
use \App\Http\Controllers\admin\FooterController;

Route::get('/login', [LoginController::class, 'login'])->name('login');
Route::post('/dologin', [LoginController::class, 'doLogin'])->name('doLogin');
Route::get('logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware('check-admin-auth')->group(function () {
    Route::get('', [DashboardController::class, 'index'])->name('index');
    Route::get('get-user', [DashboardController::class, 'getUser'])->name('get-user');
    Route::get('set-display-user/{id}/{status}', [DashboardController::class, 'setDisplayUser'])->name('set-display-user');
    Route::get('get-shop', [DashboardController::class, 'getShop'])->name('get-shop');
    Route::get('set-display-shop/{id}/{status}', [DashboardController::class, 'setDisplayShop'])->name('set-display-shop');

    Route::prefix('banner')->name('banner.')->group(function () {
        Route::get('/', [BannerController::class, 'index'])->name('index');
        Route::get('create', [BannerController::class, 'create'])->name('create');
        Route::post('store', [BannerController::class, 'store'])->name('store');
        Route::get('delete/{id}', [BannerController::class, 'delete']);
        Route::get('edit/{id}', [BannerController::class, 'edit']);
        Route::post('update/{id}', [BannerController::class, 'update']);
    });

    Route::prefix('trademark')->name('trademark.')->group(function () {
        Route::get('/', [TrademarkController::class, 'index'])->name('index');
        Route::get('create', [TrademarkController::class, 'create'])->name('create');
        Route::post('store', [TrademarkController::class, 'store'])->name('store');
        Route::get('delete/{id}', [TrademarkController::class, 'delete']);
        Route::get('edit/{id}', [TrademarkController::class, 'edit']);
        Route::post('update/{id}', [TrademarkController::class, 'update']);
    });

    Route::prefix('category')->name('category.')->group(function () {
        Route::get('', [CategoryController::class, 'index'])->name('index');
        Route::get('create', [CategoryController::class, 'create'])->name('create');
        Route::post('store', [CategoryController::class, 'store'])->name('store');
        Route::get('delete/{id}', [CategoryController::class, 'delete']);
        Route::get('edit/{id}', [CategoryController::class, 'edit'])->name('edit');
        Route::post('update/{id}', [CategoryController::class, 'update'])->name('update');
    });

    Route::prefix('products')->name('products.')->group(function () {
        Route::get('approved-not', [ProductController::class, 'approvedNot'])->name('approved-not');
        Route::get('approved', [ProductController::class, 'approved'])->name('approved');
        Route::get('delete/{id}', [ProductController::class, 'delete']);
        Route::get('detail/{id}', [ProductController::class, 'detail']);
        Route::get('status/{status}/{id}', [ProductController::class, 'status']);
    });
    Route::prefix('request')->name('request.')->group(function () {
        Route::get('approved-not', [RequestSupplierController::class, 'approvedNot'])->name('approved-not');
        Route::get('approved', [RequestSupplierController::class, 'approved'])->name('approved');
        Route::get('delete/{id}', [RequestSupplierController::class, 'delete']);
        Route::get('detail/{id}', [RequestSupplierController::class, 'detail']);
        Route::get('status/{status}/{id}', [RequestSupplierController::class, 'status']);
    });

    Route::prefix('order')->name('order.')->group(function (){
        Route::get('index/{status}', [OrdersController::class,'getDataOrder'])->name('index');
        Route::get('detail/{id}', [OrdersController::class,'orderDetail'])->name('detail');
        Route::get('status/{order_id}/{status_id}', [OrdersController::class,'statusOrder'])->name('status');
    });

    Route::prefix('setting')->name('setting.')->group(function () {
        Route::get('', [SettingController::class, 'index'])->name('index');
        Route::post('update', [SettingController::class, 'save'])->name('update');
    });

    Route::prefix('footer')->name('footer.')->group(function () {
        Route::get('/', [FooterController::class, 'index'])->name('index');
        Route::get('create', [FooterController::class, 'create'])->name('create');
        Route::post('store', [FooterController::class, 'store'])->name('store');
        Route::get('delete/{id}', [FooterController::class, 'delete']);
        Route::get('edit/{id}', [FooterController::class, 'edit']);
        Route::post('update/{id}', [FooterController::class, 'update']);
    });

});

Route::post('ckeditor/upload', [DashboardController::class, 'upload'])->name('ckeditor.image-upload');
