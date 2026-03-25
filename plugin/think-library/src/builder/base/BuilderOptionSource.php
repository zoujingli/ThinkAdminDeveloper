<?php

declare(strict_types=1);

namespace think\admin\builder\base;

/**
 * Builder 选项源对象.
 * @class BuilderOptionSource
 */
class BuilderOptionSource
{
    /**
     * @var null|callable(array<string, mixed>): array<string, mixed>
     */
    private $syncHandler = null;

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        private string $sourceKey = 'source',
        private array $options = [],
        private string $source = '',
        private mixed $owner = null
    ) {
        $this->sourceKey = trim($this->sourceKey) ?: 'source';
    }

    /**
     * @param callable(array<string, mixed>): array<string, mixed> $syncHandler
     */
    public function attach(callable $syncHandler): self
    {
        $this->syncHandler = $syncHandler;
        return $this;
    }

    public function source(string $source): self
    {
        $this->source = trim($source);
        return $this->sync();
    }

    public function variable(string $source): self
    {
        return $this->source($source);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function options(array $options): self
    {
        $this->options = $options;
        return $this->sync();
    }

    public function option(string|int $value, mixed $label): self
    {
        $this->options[(string)$value] = $label;
        return $this->sync();
    }

    public function removeOption(string|int $value): self
    {
        $key = (string)$value;
        if (array_key_exists($key, $this->options)) {
            unset($this->options[$key]);
            $this->sync();
        }
        return $this;
    }

    public function clearOptions(): self
    {
        $this->options = [];
        return $this->sync();
    }

    /**
     * @return array<string, mixed>
     */
    public function export(): array
    {
        return [
            'options' => $this->options,
            $this->sourceKey => $this->source,
        ];
    }

    public function end(): mixed
    {
        return $this->owner;
    }

    private function sync(): self
    {
        if (is_callable($this->syncHandler)) {
            $state = ($this->syncHandler)($this->export());
            $this->options = is_array($state['options'] ?? null) ? $state['options'] : $this->options;
            $this->source = trim(strval($state[$this->sourceKey] ?? $this->source));
        }
        return $this;
    }
}
