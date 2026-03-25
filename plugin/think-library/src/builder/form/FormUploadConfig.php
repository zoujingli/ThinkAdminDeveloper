<?php

declare(strict_types=1);

namespace think\admin\builder\form;

use think\admin\builder\base\render\BuilderAttributes;

/**
 * 上传字段配置对象.
 * @class FormUploadConfig
 */
class FormUploadConfig
{
    /**
     * @var null|callable(array<string, mixed>): array<string, mixed>
     */
    private $syncHandler = null;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(private FormUploadField $owner, private array $config = [])
    {
    }

    /**
     * @param callable(array<string, mixed>): array<string, mixed> $syncHandler
     */
    public function attach(callable $syncHandler): self
    {
        $this->syncHandler = $syncHandler;
        return $this;
    }

    public function types(string $types): self
    {
        $types = trim($types);
        if ($types !== '') {
            $this->config['types'] = $types;
            $this->sync();
        }
        return $this;
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
     * @param array<string, mixed> $options
     */
    public function options(array $options): self
    {
        foreach ($options as $name => $value) {
            if (is_string($name) && trim($name) !== '') {
                $this->config[trim($name)] = $value;
            }
        }
        return $this->sync();
    }

    /**
     * @param array<string, mixed> $trigger
     */
    public function trigger(array $trigger): self
    {
        $this->config['trigger'] = $this->mergeAssoc($this->resolveTrigger(), $trigger);
        return $this->sync();
    }

    public function triggerClass(string|array $class): self
    {
        $trigger = $this->resolveTrigger();
        $trigger['class'] = BuilderAttributes::mergeClassNames(strval($trigger['class'] ?? ''), $class);
        $this->config['trigger'] = $trigger;
        return $this->sync();
    }

    public function triggerIcon(string $icon): self
    {
        $icon = trim($icon);
        if ($icon !== '') {
            $trigger = $this->resolveTrigger();
            $trigger['icon'] = $icon;
            $this->config['trigger'] = $trigger;
            $this->sync();
        }
        return $this;
    }

    public function triggerAttr(string $name, mixed $value = null): self
    {
        $name = trim($name);
        if ($name !== '') {
            $trigger = $this->resolveTrigger();
            $attrs = is_array($trigger['attrs'] ?? null) ? $trigger['attrs'] : [];
            $attrs[$name] = $value;
            $trigger['attrs'] = $attrs;
            $this->config['trigger'] = $trigger;
            $this->sync();
        }
        return $this;
    }

    /**
     * @param array<string, mixed> $attrs
     */
    public function triggerAttrs(array $attrs): self
    {
        $trigger = $this->resolveTrigger();
        $trigger['attrs'] = $this->mergeAssoc(is_array($trigger['attrs'] ?? null) ? $trigger['attrs'] : [], $attrs);
        $this->config['trigger'] = $trigger;
        return $this->sync();
    }

    public function triggerFile(string|bool $file): self
    {
        $trigger = $this->resolveTrigger();
        $trigger['file'] = $file;
        $this->config['trigger'] = $trigger;
        return $this->sync();
    }

    /**
     * @param array<string, mixed> $runtime
     */
    public function runtime(array $runtime): self
    {
        $this->config['runtime'] = $this->mergeAssoc($this->resolveRuntime(), $runtime);
        return $this->sync();
    }

    public function runtimeMethod(string $method): self
    {
        $method = trim($method);
        if ($method !== '') {
            $runtime = $this->resolveRuntime();
            $runtime['method'] = $method;
            $this->config['runtime'] = $runtime;
            $this->sync();
        }
        return $this;
    }

    public function runtimeSelector(string $selector): self
    {
        $selector = trim($selector);
        if ($selector !== '') {
            $runtime = $this->resolveRuntime();
            $runtime['selector'] = $selector;
            $this->config['runtime'] = $runtime;
            $this->sync();
        }
        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function export(): array
    {
        return $this->config;
    }

    public function end(): FormUploadField
    {
        return $this->owner;
    }

    private function sync(): self
    {
        if (is_callable($this->syncHandler)) {
            $state = ($this->syncHandler)($this->config);
            $this->config = is_array($state) ? $state : $this->config;
        }
        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveTrigger(): array
    {
        return is_array($this->config['trigger'] ?? null) ? $this->config['trigger'] : [];
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveRuntime(): array
    {
        return is_array($this->config['runtime'] ?? null) ? $this->config['runtime'] : [];
    }

    /**
     * @param array<string, mixed> $origin
     * @param array<string, mixed> $append
     * @return array<string, mixed>
     */
    private function mergeAssoc(array $origin, array $append): array
    {
        foreach ($append as $key => $value) {
            if (is_string($key) && isset($origin[$key]) && is_array($origin[$key]) && is_array($value) && !array_is_list($origin[$key]) && !array_is_list($value)) {
                $origin[$key] = $this->mergeAssoc($origin[$key], $value);
            } elseif (is_string($key)) {
                $origin[$key] = $value;
            }
        }
        return $origin;
    }
}
