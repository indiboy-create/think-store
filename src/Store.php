<?php

declare(strict_types=1);

namespace Youvision\ThinkStore;

use Throwable;

class Store
{
    protected array $config;

    protected string $projectRoot;

    protected ?array $composer = null;

    protected array $descriptions = [];

    /**
     * @param array       $config      商店配置，通常来自 config/store.php
     * @param string|null $projectRoot 主项目根目录，默认取当前工作目录
     */
    public function __construct(array $config = [], ?string $projectRoot = null)
    {
        $root = $projectRoot !== null && $projectRoot !== '' ? $projectRoot : (string) getcwd();

        $this->projectRoot = rtrim(str_replace('\\', '/', $root), '/');
        $this->config      = $config;
    }

    public function projectRoot(): string
    {
        return $this->projectRoot;
    }

    /**
     * 已安装的 think-store-* 发布包列表。
     *
     * @return array<int, array<string, mixed>>
     */
    public function packages(): array
    {
        $packages = [];

        foreach ($this->installed() as $package => $info) {
            if (!$this->isManaged($package)) {
                continue;
            }

            $packages[] = $this->describe($package, $info);
        }

        usort($packages, static fn (array $a, array $b): int => strcmp($a['package'], $b['package']));

        return $packages;
    }

    /**
     * @param array<string, mixed> $info
     *
     * @return array<string, mixed>
     */
    protected function describe(string $package, array $info): array
    {
        return [
            'package'     => $package,
            'version'     => (string) ($info['version'] ?? ''),
            'description' => $this->description($package, (string) ($info['path'] ?? '')),
            'path'        => (string) ($info['path'] ?? ''),
        ];
    }

    /**
     * 读取已安装的 Composer 包，优先使用 Composer 运行时 API。
     *
     * @return array<string, array{version: string, path: string}>
     */
    protected function installed(): array
    {
        $packages = [];

        if (class_exists(\Composer\InstalledVersions::class)) {
            try {
                foreach (\Composer\InstalledVersions::getInstalledPackages() as $name) {
                    $path = \Composer\InstalledVersions::getInstallPath($name);

                    if (!is_string($path) || $path === '' || !is_dir($path)) {
                        continue;
                    }

                    $packages[$name] = [
                        'version' => (string) (\Composer\InstalledVersions::getPrettyVersion($name) ?? ''),
                        'path'    => $this->normalizePath($path),
                    ];
                }
            } catch (Throwable) {
            }
        }

        foreach ($this->fromInstalledJson() as $name => $info) {
            if (!isset($packages[$name])) {
                $packages[$name] = $info;
            }
        }

        return $packages;
    }

    /**
     * 从 vendor/composer/installed.json 读取已安装包，作为运行时 API 的回退。
     *
     * @return array<string, array{version: string, path: string}>
     */
    protected function fromInstalledJson(): array
    {
        $file = $this->vendorDir() . '/composer/installed.json';

        if (!is_file($file)) {
            return [];
        }

        $json = json_decode((string) file_get_contents($file), true);

        if (!is_array($json)) {
            return [];
        }

        $list     = isset($json['packages']) && is_array($json['packages']) ? $json['packages'] : $json;
        $base     = dirname($file);
        $packages = [];

        foreach ($list as $item) {
            if (!is_array($item) || !is_string($item['name'] ?? null) || $item['name'] === '') {
                continue;
            }

            $name = $item['name'];
            $path = $item['install-path'] ?? ($item['install_path'] ?? '');

            if (!is_string($path) || trim($path) === '') {
                $path = $base . '/../' . $name;
            } else {
                $path = str_replace('\\', '/', trim($path));

                if (!preg_match('#^(?:[A-Za-z]:/|/)#', $path)) {
                    $path = $base . '/' . $path;
                }
            }

            $real = realpath($path);

            if ($real === false || !is_dir($real)) {
                continue;
            }

            $packages[$name] = [
                'version' => (string) ($item['pretty_version'] ?? $item['version'] ?? ''),
                'path'    => $this->normalizePath($real),
            ];
        }

        return $packages;
    }

    protected function isManaged(string $package): bool
    {
        $prefix = trim((string) ($this->config['prefix'] ?? ''));

        if ($prefix === '') {
            $prefix = 'youvision/think-store-';
        }

        if (!str_starts_with($package, $prefix)) {
            return false;
        }

        foreach ((array) ($this->config['exclude'] ?? []) as $pattern) {
            $pattern = trim((string) $pattern);

            if ($pattern !== '' && $this->matches($pattern, $package)) {
                return false;
            }
        }

        return true;
    }

    protected function matches(string $pattern, string $package): bool
    {
        $regex = preg_quote($pattern, '#');
        $regex = str_replace(['\*', '\?'], ['.*', '.'], $regex);

        return (bool) preg_match('#^' . $regex . '$#i', $package);
    }

    protected function description(string $package, string $path): string
    {
        if (isset($this->descriptions[$package])) {
            return $this->descriptions[$package];
        }

        $file = $path . '/composer.json';

        if ($path === '' || !is_file($file)) {
            return $this->descriptions[$package] = '';
        }

        $json        = json_decode((string) file_get_contents($file), true);
        $description = is_array($json) ? (string) ($json['description'] ?? '') : '';

        return $this->descriptions[$package] = trim($description);
    }

    /**
     * @return array<string, mixed>
     */
    protected function composer(): array
    {
        if ($this->composer !== null) {
            return $this->composer;
        }

        $file = $this->projectRoot . '/composer.json';

        if (!is_file($file)) {
            return $this->composer = [];
        }

        $json = json_decode((string) file_get_contents($file), true);

        return $this->composer = is_array($json) ? $json : [];
    }

    /**
     * 主项目 composer.json 的 config 配置。
     *
     * @return array<string, mixed>
     */
    protected function composerConfig(): array
    {
        $config = $this->composer()['config'] ?? [];

        return is_array($config) ? $config : [];
    }

    protected function vendorDir(): string
    {
        $vendor = trim(str_replace('\\', '/', (string) ($this->composerConfig()['vendor-dir'] ?? 'vendor')), '/');

        if ($vendor === '') {
            $vendor = 'vendor';
        }

        if (preg_match('#^(?:[A-Za-z]:/|/)#', $vendor)) {
            return rtrim($vendor, '/');
        }

        return $this->projectRoot . '/' . $vendor;
    }

    protected function normalizePath(string $path): string
    {
        $real = realpath($path);

        return rtrim(str_replace('\\', '/', $real !== false ? $real : $path), '/');
    }
}