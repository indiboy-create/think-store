<?php

declare(strict_types=1);

namespace Youvision\ThinkStore;

use InvalidArgumentException;
use think\response\Json;

class StoreController
{
    public function __construct(protected Store $store)
    {
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

    /**
     * 重新同步静态资源。
     */
    public function sync(): Json
    {
        try {
            $result = $this->store->sync($this->packagesParam(), $this->forceParam());
        } catch (InvalidArgumentException $e) {
            return json([
                'message' => $e->getMessage(),
            ], 400);
        }

        return json($result);
    }

    protected function packagesParam(): string|array|null
    {
        $packages = request()->param('packages');

        if (is_array($packages)) {
            return $packages;
        }

        if (is_scalar($packages)) {
            return (string) $packages;
        }

        return null;
    }

    protected function forceParam(): ?bool
    {
        $value = request()->param('force');

        if ($value === null || $value === '') {
            return null;
        }

        $force = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return $force === null ? null : (bool) $force;
    }
}