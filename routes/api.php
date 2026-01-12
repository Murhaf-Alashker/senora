<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    $p = \App\Enum\MediaType::type();
    return $p;
});

Route::get('/',[ProductController::class,'homePage'])->middleware('throttle:once-per-10-seconds');
Route::get('/products',[ProductController::class,'index']);
Route::get('/products/{ulid}',[ProductController::class,'show']);
Route::get('/categories/{ulid}/products',[ProductController::class,'productByCategory']);
Route::get('/categories',[CategoryController::class,'index']);

Route::prefix('dashboard')->middleware(['throttle:once-per-10-seconds'])->group(function () {
    Route::post('/category', [CategoryController::class, 'store']);
    Route::post('/category/update/{ulid}', [CategoryController::class, 'update']);
    Route::post('/product', [ProductController::class, 'store']);
    Route::post('/product/update/{ulid}', [ProductController::class, 'update']);
});
