<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SearchAndOrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    $p = \App\Enum\MediaType::type();
    return $p;
});

Route::middleware(\App\Http\Middleware\CheckToken::class)->group(function () {
    Route::get('/',[ProductController::class,'homePage']);
    Route::get('/products',[ProductController::class,'index']);
    Route::get('/products/{ulid}',[ProductController::class,'show']);
    Route::get('/categories/{ulid}/products',[ProductController::class,'productByCategory']);
    Route::get('/categories',[CategoryController::class,'index']);
    Route::post('/search',[SearchAndOrderController::class,'search']);
    Route::post('/order',[SearchAndOrderController::class,'order']);
    Route::post('/order/reorder',[SearchAndOrderController::class,'confirmConflictedOrder']);

    Route::middleware(['throttle:once-per-10-seconds'])->group(function (){

        Route::prefix('auth')->group(function () {
            Route::post('/login', [AuthController::class, 'login']);
            Route::post('/reset_password', [AuthController::class, 'resetPasswordUsingOldPassword']);
        });

        Route::prefix('dashboard')->middleware('auth:api')->group(function () {
            Route::prefix('category')->group(function () {
                Route::post('/', [CategoryController::class, 'store']);
                Route::post('/{ulid}/update', [CategoryController::class, 'update']);
                Route::post('/{ulid}/changeStatus', [CategoryController::class, 'changeStatus']);
            });

            Route::prefix('product')->group(function () {
                Route::post('/', [ProductController::class, 'store']);
                Route::post('/{ulid}/update', [ProductController::class, 'update']);
                Route::post('/{ulid}/changeStatus', [ProductController::class, 'changeStatus']);
            });

        });
    });

});
