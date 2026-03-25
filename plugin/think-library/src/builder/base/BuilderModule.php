<?php

declare(strict_types=1);

namespace think\admin\builder\base;

/**
 * Builder 模块对象.
 * @class BuilderModule
 */
class BuilderModule
{
    private ?int $index = null;

    /**
     * @var null|callable(int, array<string, mixed>): array<string, mixed>
     */
    private $syncHandler = null;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private string $name,
        private array $config = [],
        private mixed $owner = null
    ) {
        $this->name = trim($this->name);
    }

    /**
     * @param array<string, mixed> $module
     * @param callable(int, array<string, mixed>): array<string, mixed> $syncHandler
     */
    public function attach(int $index, array $module, callable $syncHandler): self
    {
        $this->index = $index;
        $this->syncHandler = $syncHandler;
        $this->name = trim(strval($module['name'] ?? $this->name));
        $this->config = is_array($module['config'] ?? null) ? $module['config'] : $this->config;
        return $this;
    }

    public function name(string $name): self
    {
        $name = trim($name);
        if ($name !== '') {
            $this->name = $name;
            $this->sync();
        }
        return $this;
    }

    /**
     * @param array<string, mixed> $config
     */
    public function config(array $config): self
    {
        $this->config = $config;
        return $this->sync();
    }

    public function option(string $name, mixed $value): self
    {
        $name = trim($name);
        if ($name !== '') {
            $this->config[$name] = $value;
            $this->sync();
        }
        return $this;
    }

    /**
     * @param array<string, mixed> $config
     */
    public function options(array $config): self
    {
        foreach ($config as $name => $value) {
            if (is_string($name) && trim($name) !== '') {
                $this->config[trim($name)] = $value;
            }
        }
        return $this->sync();
    }

    public function remove(string $name): self
    {
        $name = trim($name);
        if ($name !== '' && array_key_exists($name, $this->config)) {
            unset($this->config[$name]);
            $this->sync();
        }
        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function export(): array
    {
        return ['name' => $this->name, 'config' => $this->config];
    }

    public function end(): mixed
    {
        return $this->owner;
    }

    private function sync(): self
    {
        if ($this->index !== null && is_callable($this->syncHandler)) {
            $module = ($this->syncHandler)($this->index, $this->export());
            $this->name = trim(strval($module['name'] ?? $this->name));
            $this->config = is_array($module['config'] ?? null) ? $module['config'] : $this->config;
        }
        return $this;
    }
}
