<?php

declare(strict_types=1);

namespace Youvision\ThinkStore;

use think\Service as ThinkService;

class Service extends ThinkService
{
    public function register(): void
    {
        $this->mergeConfig();

        $this->app->bind('store', function () {
            return new Store(
                (array) $this->app->config->get('store', []),
                $this->app->getRootPath()
            );
        });

        $this->app->bind(Store::class, 'store');

        $this->app->instance(View::class, new View($this->app));
    }

    public function boot(): void
    {
        if ((bool) $this->app->config->get('store.route.enabled', true)) {
            $this->loadRoutesFrom(dirname(__DIR__) . '/route/store.php');
        }
    }

    protected function mergeConfig(): void
    {
        $default = require dirname(__DIR__) . '/config/store.php';
        $current = (array) $this->app->config->get('store', []);

        $this->app->config->set(array_replace_recursive($default, $current), 'store');
    }
}