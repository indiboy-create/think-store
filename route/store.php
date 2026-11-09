<?php

use think\facade\Route;
use Youvision\ThinkStore\StoreController;

$prefix     = trim((string) config('store.route.prefix', 'store'), '/');
$middleware = (array) config('store.route.middleware', []);

if ($prefix === '') {
    Route::get('packages', StoreController::class . '@packages')->middleware($middleware);
    Route::post('packages/sync', StoreController::class . '@sync')->middleware($middleware);

    return;
}

Route::group($prefix, function (): void {
    Route::get('packages', StoreController::class . '@packages');
    Route::post('packages/sync', StoreController::class . '@sync');
})->middleware($middleware);