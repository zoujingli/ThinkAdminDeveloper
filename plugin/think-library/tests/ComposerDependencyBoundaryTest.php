<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdmin
 * +----------------------------------------------------------------------
 * | 版权所有 2014~2026 ThinkAdmin [ thinkadmin.top ]
 * +----------------------------------------------------------------------
 * | 官方网站: https://thinkadmin.top
 * +----------------------------------------------------------------------
 * | 开源协议 ( https://mit-license.org )
 * | 免责声明 ( https://thinkadmin.top/disclaimer )
 * | 会员特权 ( https://thinkadmin.top/vip-introduce )
 * +----------------------------------------------------------------------
 * | gitee 代码仓库：https://gitee.com/zoujingli/ThinkAdmin
 * | github 代码仓库：https://github.com/zoujingli/ThinkAdmin
 * +----------------------------------------------------------------------
 */

namespace think\admin\tests;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class ComposerDependencyBoundaryTest extends TestCase
{
    private string $projectRoot;

    /**
     * @var array<string, array{name:string, path:string, require:array<string, string>}>
     */
    private array $packages = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->projectRoot = TEST_PROJECT_ROOT;
        $this->packages = $this->loadPackages();
    }

    public function testLocalPluginDependencyGraphIsAcyclic(): void
    {
        $graph = $this->localGraph();
        $state = [];
        $stack = [];
        $cycle = [];

        $visit = function (string $package) use (&$visit, &$graph, &$state, &$stack, &$cycle): void {
            if ($cycle !== []) {
                return;
            }
            $state[$package] = 1;
            $stack[] = $package;

            foreach ($graph[$package] ?? [] as $dependency) {
                $depState = $state[$dependency] ?? 0;
                if ($depState === 0) {
                    $visit($dependency);
                    if ($cycle !== []) {
                        return;
                    }
                    continue;
                }
                if ($depState === 1) {
                    $offset = array_search($dependency, $stack, true);
                    $cycle = array_slice($stack, $offset === false ? 0 : $offset);
                    $cycle[] = $dependency;
                    return;
                }
            }

            array_pop($stack);
            $state[$package] = 2;
        };

        foreach (array_keys($graph) as $package) {
            if (($state[$package] ?? 0) === 0) {
                $visit($package);
            }
        }

        $this->assertSame([], $cycle, 'Local composer dependency cycle detected: ' . implode(' -> ', $cycle));
    }

    public function testBasePackagesStayAtTheBottomOfDependencyGraph(): void
    {
        $this->assertSame([], $this->localRequires('zoujingli/think-library'));
        $this->assertSame([
            'zoujingli/think-library',
            'zoujingli/think-plugs-static',
            'zoujingli/think-plugs-storage',
            'zoujingli/think-plugs-worker',
        ], $this->localRequires('zoujingli/think-plugs-system'));
        $this->assertSame(['zoujingli/think-library'], $this->localRequires('zoujingli/think-plugs-worker'));
    }

    public function testStorageAndHelperDoNotReintroduceKnownDependencyLoops(): void
    {
        $storage = $this->localRequires('zoujingli/think-plugs-storage');
        $helper = $this->localRequires('zoujingli/think-plugs-helper');

        $this->assertNotContains('zoujingli/think-plugs-worker', $storage);

        $this->assertNotContains('zoujingli/think-plugs-storage', $helper);
    }

    public function testEveryLocalDependencyPointsToAnExistingWorkspacePackage(): void
    {
        $known = array_keys($this->packages);
        $missing = [];

        foreach ($this->packages as $name => $package) {
            foreach ($this->localRequires($name) as $dependency) {
                if (!in_array($dependency, $known, true)) {
                    $missing[] = [$name, $dependency];
                }
            }
        }

        $this->assertSame([], $missing, 'Unknown local package dependencies found: ' . json_encode($missing, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * @return array<string, array{name:string, path:string, require:array<string, string>}>
     */
    private function loadPackages(): array
    {
        $items = [];
        foreach (glob($this->path('plugin/*/composer.json')) ?: [] as $file) {
            $json = json_decode(file_get_contents($file) ?: '', true);
            if (!is_array($json) || empty($json['name'])) {
                continue;
            }
            $items[strval($json['name'])] = [
                'name' => strval($json['name']),
                'path' => $file,
                'require' => is_array($json['require'] ?? null) ? $json['require'] : [],
            ];
        }

        ksort($items);
        return $items;
    }

    /**
     * @return array<string, list<string>>
     */
    private function localGraph(): array
    {
        $graph = [];
        foreach (array_keys($this->packages) as $name) {
            $graph[$name] = $this->localRequires($name);
        }
        return $graph;
    }

    /**
     * @return list<string>
     */
    private function localRequires(string $package): array
    {
        $requires = array_keys($this->packages[$package]['require'] ?? []);
        $locals = array_values(array_filter($requires, fn (string $name): bool => isset($this->packages[$name])));
        sort($locals);
        return $locals;
    }

    private function path(string $relative): string
    {
        return $this->projectRoot . '/' . ltrim($relative, '/');
    }
}
