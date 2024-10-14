<?php

use Illuminate\Support\Facades\Route;
use \App\Http\Controllers\web\HomeController;
use \App\Http\Controllers\web\CartController;
use \App\Http\Controllers\web\ProductsController;

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

Route::get('/', [HomeController::class, 'home'])->name('home');

Route::get('api/detail-product/{slug}', [ProductsController::class, 'detailProduct']);
Route::get('api/get-viewed-products', [ProductsController::class, 'getViewedProducts']);

Route::get('api/get-cart', [CartController::class, 'getCart']);
Route::post('api/add-to-cart', [CartController::class, 'addToCart']);
Route::post('api/remove-product-from-cart', [CartController::class, 'removeProductFromCart']);
Route::post('api/remove-shop-from-cart', [CartController::class, 'removeShopFromCart']);
Route::post('api/update-cart-quantity', [CartController::class, 'updateCartQuantity']);
Route::middleware(['jwt.auth'])->group(function () {
    Route::post('api/buy-now', [CartController::class, 'buyNow']);
    Route::post('api/checkout', [CartController::class, 'checkout']);
    Route::post('api/pay', [CartController::class, 'pay']);
});

Route::middleware('auth')->group(function () {

});
