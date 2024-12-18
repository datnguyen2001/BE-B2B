<?php

use App\Http\Controllers\MessageController;
use App\Http\Controllers\PusherController;
use Illuminate\Support\Facades\Route;
use \App\Http\Controllers\web\AuthController;
use \App\Http\Controllers\web\HomeController;
use \App\Http\Controllers\web\ShopController;
use \App\Http\Controllers\web\AddressController;
use \App\Http\Controllers\web\ProductsController;
use \App\Http\Controllers\web\RequestSupplierController;
use \App\Http\Controllers\web\QuotesController;
use \App\Http\Controllers\web\CartController;
use \App\Http\Controllers\web\DeliveryAddressController;
use \App\Http\Controllers\web\ProfileManagementController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
Route::post('send-code', [AuthController::class, 'sendCode']);
Route::post('verify-code', [AuthController::class, 'verifyCode']);
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::get('banner', [HomeController::class, 'banner']);
Route::get('trademark', [HomeController::class, 'trademark']);
Route::get('category', [HomeController::class, 'category']);
Route::get('category-product', [HomeController::class, 'categoryProduct']);
Route::get('deal-hot-today', [ProductsController::class, 'dealHotToday']);
Route::get('product-new', [ProductsController::class, 'productNew']);
Route::get('filter-deal-hot-today', [ProductsController::class, 'filterDealHotToday']);
Route::get('filter-Product', [ProductsController::class, 'filterProduct']);
Route::get('product-for-you', [ProductsController::class, 'productForYou']);
Route::get('search-product', [ProductsController::class, 'searchProduct']);
Route::get('get-product-shop/{id}', [HomeController::class, 'getProductShop']);
Route::get('setting', [HomeController::class, 'setting']);
Route::get('detail-post-footer/{slug}', [HomeController::class, 'detailPostFooter']);

Route::get('detail-shop/{id}', [ShopController::class, 'detailShop']);
Route::get('get-request-supplier', [RequestSupplierController::class, 'getRequestSupplier']);
Route::get('edit-request-supplier-user/{id}', [RequestSupplierController::class, 'editRequestSupplierUser']);
Route::post('check-follow-shop', [AuthController::class, 'checkFollowShop']);

Route::get('province', [AddressController::class, 'province']);
Route::get('district/{province_id}', [AddressController::class, 'district']);
Route::get('wards/{district_id}', [AddressController::class, 'wards']);

Route::post('test-chat/broadcast', [PusherController::class, 'broadcast'])->name('test-chat.broadcast');
Route::post('test-chat/receive', [PusherController::class, 'receive'])->name('test-chat.receive');
Route::post('check-online', [AuthController::class, 'checkOnline']);

Route::middleware(['jwt.auth'])->group(function () {
    Route::get('get-profile', [AuthController::class, 'getProfile']);
    Route::post('update-profile', [AuthController::class, 'updateProfile']);
    Route::post('follow-shop', [AuthController::class, 'followShop']);
    Route::post('unfollow-shop', [AuthController::class, 'unfollowShop']);
    Route::get('get-follow-shop', [AuthController::class, 'getFollowShop']);
    Route::post('favorite-product', [ProductsController::class, 'favoriteProduct']);
    Route::get('get-favorite-product', [ProductsController::class, 'getFavoriteProducts']);
    Route::get('check-shop', [AuthController::class, 'checkShop']);
    Route::get('get-notification', [AuthController::class, 'getNotification']);
    Route::get('read-messages/{id}', [AuthController::class, 'readMessages']);

    Route::get('get-client', [ProfileManagementController::class, 'getClient']);
    Route::post('user-order', [ProfileManagementController::class, 'userOrder']);
    Route::post('user-order-cancel', [ProfileManagementController::class, 'userOrderCancel']);
    Route::get('detail-user-order/{id}', [ProfileManagementController::class, 'detailUserOrder']);
    Route::get('statistical', [ProfileManagementController::class, 'statistical']);

    Route::post('shop-order', [ProfileManagementController::class, 'shopOrder']);
    Route::post('shop-order-status', [ProfileManagementController::class, 'shopOrderStatus']);

    Route::post('save-ask-buy', [ProductsController::class, 'saveAskBuy']);
    Route::post('product-report', [ProductsController::class, 'productReport']);
    Route::get('get-ask-buy', [ProfileManagementController::class, 'getAskBuy']);
    Route::get('detail-ask-buy/{id}', [ProfileManagementController::class, 'detailAskBuy']);
    Route::get('get-product-report', [ProfileManagementController::class, 'getProductReport']);
    Route::get('detail-product-report/{id}', [ProfileManagementController::class, 'detailProductReport']);

    Route::get('get-shop', [ShopController::class, 'getShop']);
    Route::post('create-shop', [ShopController::class, 'createShop']);
    Route::post('update-shop', [ShopController::class, 'updateShop']);
    Route::post('delete-src-shop', [ShopController::class, 'deleteSrcShop']);
    Route::get('get-product', [ShopController::class, 'getProduct']);
    Route::post('create-product', [ShopController::class, 'createProduct']);
    Route::post('update-product/{id}', [ShopController::class, 'updateProduct']);
    Route::post('update-quantity-product/{id}', [ShopController::class, 'updateQuantityProduct']);
    Route::get('delete-product/{id}', [ShopController::class, 'deleteProduct']);
    Route::get('delete-product-attribute/{id}', [ShopController::class, 'deleteProductAttribute']);
    Route::post('delete-product-image/{id}', [ShopController::class, 'deleteProductImage']);
    Route::post('update-product-display/{id}', [ShopController::class, 'updateProductDisplay']);
    Route::get('detail-product-shop/{id}', [ShopController::class, 'detailProductShop']);
    Route::post('set-product-discount/{id}', [ShopController::class, 'setProductDiscount']);
    Route::get('search-product-shop', [ShopController::class, 'searchProductShop']);
    Route::post('set-display-product-discount/{id}', [ShopController::class, 'setDisplayProductDiscount']);

    Route::get('get-request-supplier-user', [RequestSupplierController::class, 'getRequestSupplierUser']);
    Route::post('create-request-supplier', [RequestSupplierController::class, 'createRequestSupplier']);
    Route::post('update-request-supplier/{id}', [RequestSupplierController::class, 'updateRequestSupplier']);
    Route::post('update-request-display/{id}', [RequestSupplierController::class, 'updateRequestDisplay']);
    Route::get('delete-request/{id}', [RequestSupplierController::class, 'deleteRequest']);

    Route::get('get-quotes', [QuotesController::class, 'getQuotes']);
    Route::post('create-quotes', [QuotesController::class, 'createQuotes']);
    Route::get('detail-quotes/{id}', [QuotesController::class, 'detailQuotes']);
    Route::get('get-quotes-user', [QuotesController::class, 'getQuotesUser']);

    Route::get('get-delivery-address', [DeliveryAddressController::class, 'getDeliveryAddress']);
    Route::post('create-delivery-address', [DeliveryAddressController::class, 'createDeliveryAddress']);
    Route::get('detail-delivery-address/{id}', [DeliveryAddressController::class, 'detailDeliveryAddress']);
    Route::post('update-delivery-address/{id}', [DeliveryAddressController::class, 'updateDeliveryAddress']);
    Route::get('delete-delivery-address/{id}', [DeliveryAddressController::class, 'deleteDeliveryAddress']);
    Route::get('select-default-address/{id}', [DeliveryAddressController::class, 'selectDefaultAddress']);

    Route::post('logout', [AuthController::class, 'logout']);

    Route::post('/messages', [MessageController::class, 'store']);
    Route::get('/messages/{userId}/{receiverId}', [MessageController::class, 'index']);
    Route::get('/conversations', [MessageController::class, 'getAllConversations']);
    Route::post('/create-conversations', [MessageController::class, 'createConversations']);
    Route::post('/messages/mark-as-read/{userId}/{conversationId}', [MessageController::class, 'markAsRead']);
//    Route::get('/messages/unread-message', [MessageController::class, 'countUnreadMessage']);
});
