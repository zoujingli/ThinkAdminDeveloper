<?php

declare(strict_types=1);

namespace think\admin\builder\base;

use think\admin\builder\base\render\BuilderAttributes;

/**
 * Builder 属性对象.
 * @class BuilderAttributeBag
 */
class BuilderAttributeBag
{
    /**
     * @var null|callable(array<string, mixed>): array<string, mixed>
     */
    private $syncHandler = null;

    /**
     * @param array<string, mixed> $attrs
     */
    public function __construct(
        private mixed $owner = null,
        private array $attrs = [],
        private bool $detachedClass = false,
        private string $className = ''
    ) {
    }

    /**
     * @param callable(array<string, mixed>): array<string, mixed> $syncHandler
     */
    public function attach(callable $syncHandler): self
    {
        $this->syncHandler = $syncHandler;
        return $this;
    }

    public function attr(string $name, mixed $value = null): self
    {
        $name = trim($name);
        if ($name === '') {
            return $this;
        }
        if ($name === 'class') {
            return $this->assignClass($value);
        }
        $this->attrs[$name] = $value;
        return $this->sync();
    }

    /**
     * @param array<string, mixed> $attrs
     */
    public function attrs(array $attrs): self
    {
        foreach ($attrs as $name => $value) {
            if (is_string($name)) {
                $this->attr($name, $value);
            }
        }
        return $this;
    }

    public function class(string|array $class): self
    {
        if ($this->detachedClass) {
            $this->className = BuilderAttributes::mergeClassNames($this->className, $class);
        } else {
            $this->attrs = BuilderAttributes::make($this->attrs)->class($class)->all();
        }
        return $this->sync();
    }

    public function removeClass(string|array $class): self
    {
        if ($this->detachedClass) {
            $attrs = BuilderAttributes::make(['class' => $this->className])->removeClass($class)->all();
            $this->className = trim(strval($attrs['class'] ?? ''));
        } else {
            $this->attrs = BuilderAttributes::make($this->attrs)->removeClass($class)->all();
        }
        return $this->sync();
    }

    public function toggleClass(string|array $class, ?bool $force = null): self
    {
        if ($this->detachedClass) {
            $attrs = BuilderAttributes::make(['class' => $this->className])->toggleClass($class, $force)->all();
            $this->className = trim(strval($attrs['class'] ?? ''));
        } else {
            $this->attrs = BuilderAttributes::make($this->attrs)->toggleClass($class, $force)->all();
        }
        return $this->sync();
    }

    public function data(string $name, mixed $value = null): self
    {
        $name = trim($name);
        if ($name !== '') {
            $this->attr('data-' . ltrim($name, '-'), $value);
        }
        return $this;
    }

    public function id(string $id): self
    {
        return $this->attr('id', $id);
    }

    public function remove(string $name): self
    {
        $name = trim($name);
        if ($name === '') {
            return $this;
        }
        if ($name === 'class') {
            if ($this->detachedClass) {
                $this->className = '';
            } else {
                unset($this->attrs['class']);
            }
            return $this->sync();
        }
        if (array_key_exists($name, $this->attrs)) {
            unset($this->attrs[$name]);
            $this->sync();
        }
        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function export(): array
    {
        $state = ['attrs' => $this->attrs];
        if ($this->detachedClass) {
            $state['class'] = $this->className;
        }
        return $state;
    }

    public function end(): mixed
    {
        return $this->owner;
    }

    private function assignClass(mixed $value): self
    {
        $class = is_array($value) ? BuilderAttributes::mergeClassNames('', $value) : trim(strval($value));
        if ($this->detachedClass) {
            $this->className = $class;
        } elseif ($class === '') {
            unset($this->attrs['class']);
        } else {
            $this->attrs['class'] = $class;
        }
        return $this->sync();
    }

    private function sync(): self
    {
        if (is_callable($this->syncHandler)) {
            $state = ($this->syncHandler)($this->export());
            $this->attrs = is_array($state['attrs'] ?? null) ? $state['attrs'] : $this->attrs;
            if ($this->detachedClass) {
                $this->className = trim(strval($state['class'] ?? $this->className));
            }
        }
        return $this;
    }
}
