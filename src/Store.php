<?php

declare(strict_types=1);

namespace Youvision\ThinkStore;

use InvalidArgumentException;
use Throwable;
use Youvision\ThinkStatic\Project;
use Youvision\ThinkStatic\StaticAssets;

class Store
{
    protected array $config;

    protected string $projectRoot;

    protected ?array $composer = null;

    protected ?array $extra = null;

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
        $assets   = $this->assets();
        $packages = [];

        foreach ($this->installed() as $package => $info) {
            if (!$this->isManaged($package)) {
                continue;
            }

            $packages[] = $this->describe($package, $info, $assets);
        }

        usort($packages, static fn (array $a, array $b): int => strcmp($a['package'], $b['package']));

        return $packages;
    }

    /**
     * 重新同步静态资源。
     *
     * @param string|array|null $packages 包名、包名数组或逗号分隔字符串；null 或空值表示全部
     * @param bool|null         $force    是否覆盖已存在的同名文件；null 使用配置值
     *
     * @return array<string, mixed>
     */
    public function sync(string|array|null $packages = null, ?bool $force = null): array
    {
        $names  = $this->resolvePackages($packages);
        $assets = $this->assets();
        $force  = $force ?? $this->force();

        $result = [
            'packages' => [],
            'total'    => 0,
            'copied'   => 0,
            'skipped'  => 0,
            'failed'   => 0,
            'errors'   => 0,
            'force'    => $force,
        ];

        foreach ($names as $name) {
            $item = $this->publish($name, $force, $assets);

            $result['packages'][] = $item;
            $result['total']++;
            $result['copied']  += (int) $item['copied'];
            $result['skipped'] += (int) $item['skipped'];
            $result['failed']  += (int) $item['failed'];

            if (!$item['success']) {
                $result['errors']++;
            }
        }

        return $result;
    }

    /**
     * 部署根目录，优先读取主项目 extra.think-static.root。
     */
    public function root(): string
    {
        $root = (string) ($this->extra()['root'] ?? $this->config['root'] ?? 'public/store');
        $root = trim(str_replace('\\', '/', $root), '/');

        return $root !== '' ? $root : 'public/store';
    }

    /**
     * 重新同步时是否默认覆盖同名文件。
     */
    public function force(): bool
    {
        return (bool) ($this->config['force'] ?? true);
    }

    /**
     * 传给 youvision/think-static 的部署配置。
     *
     * @return array<string, mixed>
     */
    public function options(): array
    {
        $extra      = $this->extra();
        $projectDir = trim(str_replace('\\', '/', (string) ($this->config['project_dir'] ?? 'project')), '/');

        return [
            'root'           => $this->root(),
            'project_dir'    => $projectDir !== '' ? $projectDir : 'project',
            'manifest'       => (string) ($extra['manifest'] ?? $this->config['manifest'] ?? 'project.json'),
            'force'          => $this->force(),
            'skip_dot_files' => (bool) ($extra['skip_dot_files'] ?? $this->config['skip_dot_files'] ?? true),
        ];
    }

    /**
     * @param array<string, mixed> $info
     *
     * @return array<string, mixed>
     */
    protected function describe(string $package, array $info, StaticAssets $assets): array
    {
        $data = [
            'package'     => $package,
            'version'     => (string) ($info['version'] ?? ''),
            'description' => $this->description($package, (string) ($info['path'] ?? '')),
            'path'        => (string) ($info['path'] ?? ''),
            'target'      => '',
            'items'       => 0,
            'has_project' => false,
            'synced'      => false,
            'synced_at'   => '',
            'error'       => '',
        ];

        try {
            $project              = $assets->scan($package);
            $data['has_project']  = true;
            $data['target']       = $this->target($project);
            $data['items']        = count($project->items);

            $targetPath = $this->projectRoot . '/' . $data['target'];

            if (is_dir($targetPath)) {
                $data['synced'] = true;
                $time           = @filemtime($targetPath);

                if ($time !== false) {
                    $data['synced_at'] = date('Y-m-d H:i:s', $time);
                }
            }
        } catch (Throwable $e) {
            $data['error'] = $e->getMessage();
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    protected function publish(string $package, bool $force, StaticAssets $assets): array
    {
        try {
            $result            = $assets->publish($package, $force);
            $result['success'] = (int) $result['failed'] === 0;
            $result['error']   = '';

            return $result;
        } catch (Throwable $e) {
            return [
                'package' => $package,
                'target'  => '',
                'items'   => [],
                'copied'  => 0,
                'skipped' => 0,
                'failed'  => 0,
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }

    protected function target(Project $project): string
    {
        return $this->root() . ($project->targetPath !== '' ? '/' . $project->targetPath : '');
    }

    /**
     * 解析待同步的包名列表，拒绝未安装或不受管理的包。
     *
     * @param string|array|null $packages
     *
     * @return array<int, string>
     */
    protected function resolvePackages(string|array|null $packages): array
    {
        $installed = $this->installed();
        $managed   = [];

        foreach (array_keys($installed) as $name) {
            if ($this->isManaged($name)) {
                $managed[] = $name;
            }
        }

        if ($packages === null || $packages === [] || $packages === '') {
            return $managed;
        }

        $names = is_array($packages) ? $packages : explode(',', $packages);
        $names = array_values(array_unique(array_filter(array_map(
            static fn ($name): string => trim((string) $name),
            $names
        ), static fn (string $name): bool => $name !== '')));

        if ($names === []) {
            return $managed;
        }

        foreach ($names as $name) {
            if (!isset($installed[$name]) || !$this->isManaged($name)) {
                throw new InvalidArgumentException('未安装或不受管理的 think-store 发布包：' . $name);
            }
        }

        return $names;
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

    /**
     * 主项目 composer.json 的 extra.think-static 配置。
     *
     * @return array<string, mixed>
     */
    protected function extra(): array
    {
        if ($this->extra !== null) {
            return $this->extra;
        }

        $extra = $this->composer()['extra']['think-static'] ?? [];

        return $this->extra = is_array($extra) ? $extra : [];
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

    protected function assets(): StaticAssets
    {
        return new StaticAssets($this->projectRoot, $this->options());
    }

    protected function normalizePath(string $path): string
    {
        $real = realpath($path);

        return rtrim(str_replace('\\', '/', $real !== false ? $real : $path), '/');
    }
}