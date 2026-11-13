<?php

use think\facade\Route;
use Youvision\ThinkStore\AssetController;
use Youvision\ThinkStore\StoreController;

$prefix     = trim((string) config('store.route.prefix', 'store'), '/');
$middleware = (array) config('store.route.middleware', []);

$register = static function (): void {
    Route::get('think-store/index', StoreController::class . '@index')->name('store.index')->completeMatch();
    Route::get('think-store/packages', StoreController::class . '@packages')->name('store.packages')->completeMatch();
    Route::get('think-store/intro', StoreController::class . '@intro')->name('store.intro')->completeMatch();

    // 静态资源直接由路由输出，无需发布到 public
    Route::get('assets/<path>', [AssetController::class, 'file'])
        ->pattern(['path' => '.*'])
        ->name('store.asset');
};

if ($prefix === '') {
    $register();
} else {
    $group = Route::group($prefix, $register);
    $group->middleware($middleware);
}