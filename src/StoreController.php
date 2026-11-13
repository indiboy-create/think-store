<?php

declare(strict_types=1);

namespace Youvision\ThinkStore;

use think\App;
use think\response\Json;
use think\view\driver\Think;

class StoreController
{
    public function __construct(protected Store $store, protected App $app)
    {
    }

    /**
     * 渲染发布包管理首页。
     */
    public function index(): string
    {
        return $this->render('index');
    }

    /**
     * 渲染使用说明页。
     */
    public function intro(): string
    {
        return $this->render('intro');
    }

    /**
     * 查询已安装的 think-store-* 发布包。
     */
    public function packages(): Json
    {
        $packages = $this->store->packages();

        return json([
            'total'    => count($packages),
            'packages' => $packages,
        ]);
    }

    protected function render(string $template): string
    {
        $driver = new Think($this->app, [
            'view_path'   => dirname(__DIR__) . '/theme/view/',
            'view_suffix' => 'html',
        ]);

        ob_start();

        $driver->fetch($template, [
            'api'     => $this->apiPrefix(),
            'version' => $this->version(),
        ]);

        return (string) ob_get_clean();
    }

    protected function apiPrefix(): string
    {
        $prefix = trim((string) config('store.route.prefix', 'store'), '/');

        return $prefix === '' ? '' : '/' . $prefix;
    }

    protected function version(): string
    {
        $file = dirname(__DIR__) . '/composer.json';

        if (!is_file($file)) {
            return '';
        }

        $json = json_decode((string) file_get_contents($file), true);

        return is_array($json) ? (string) ($json['version'] ?? '') : '';
    }
}