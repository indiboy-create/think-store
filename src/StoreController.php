<?php

declare(strict_types=1);

namespace Youvision\ThinkStore;

use think\response\Json;
use think\response\Redirect;

class StoreController
{
    public function __construct(protected Store $store, protected View $view)
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
     * 兼容旧地址，跳转到管理页的使用文档。
     */
    public function intro(): Redirect
    {
        return redirect($this->apiPrefix() . '/think-store/index#st-docs');
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
        return $this->view->render($template, [
            'api'     => $this->apiPrefix(),
            'version' => $this->version(),
        ]);
    }

    protected function apiPrefix(): string
    {
        $prefix = trim((string) config('store.route.prefix', 'store'), '/');

        return $prefix === '' ? '' : '/' . $prefix;
    }

    protected function version(): string
    {
        if (class_exists(\Composer\InstalledVersions::class)) {
            try {
                $version = \Composer\InstalledVersions::getPrettyVersion('youvision/think-store');

                if (is_string($version) && $version !== '') {
                    return $version;
                }
            } catch (\Throwable) {
            }
        }

        $file = dirname(__DIR__) . '/composer.json';

        if (!is_file($file)) {
            return 'dev';
        }

        $json    = json_decode((string) file_get_contents($file), true);
        $version = is_array($json) ? (string) ($json['version'] ?? '') : '';

        return $version !== '' ? $version : 'dev';
    }
}