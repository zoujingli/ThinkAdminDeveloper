<?php

declare(strict_types=1);

namespace think\admin\builder\page;

/**
 * 页面列表列对象.
 * @class PageColumn
 */
class PageColumn
{
    private ?int $index = null;
    private ?int $version = null;
    private $syncHandler = null;

    /**
     * @param array<string, mixed> $column
     */
    public function __construct(private PageBuilder $builder, private array $column = [])
    {
    }

    /**
     * @param array<string, mixed> $column
     */
    public function attach(int $index, array $column, ?int $version = null): self
    {
        $this->index = $index;
        $this->version = $version;
        $this->syncHandler = null;
        $this->column = $column;
        return $this;
    }

    public function attachSync(callable $syncHandler): self
    {
        $this->index = null;
        $this->version = null;
        $this->syncHandler = $syncHandler;
        return $this;
    }

    public function field(string $field): self
    {
        $this->column['field'] = trim($field);
        return $this->sync();
    }

    public function title(string $title): self
    {
        $this->column['title'] = $title;
        return $this->sync();
    }

    public function width(int|string $width): self
    {
        $this->column['width'] = $width;
        return $this->sync();
    }

    public function minWidth(int|string $width): self
    {
        $this->column['minWidth'] = $width;
        return $this->sync();
    }

    public function align(string $align): self
    {
        $this->column['align'] = trim($align);
        return $this->sync();
    }

    public function fixed(bool|string $fixed = true): self
    {
        $this->column['fixed'] = $fixed;
        return $this->sync();
    }

    public function sort(bool $sort = true): self
    {
        $this->column['sort'] = $sort;
        return $this->sync();
    }

    public function hide(bool $hide = true): self
    {
        $this->column['hide'] = $hide;
        return $this->sync();
    }

    public function event(string $event): self
    {
        $this->column['event'] = trim($event);
        return $this->sync();
    }

    public function style(string $style): self
    {
        $this->column['style'] = $style;
        return $this->sync();
    }

    public function edit(bool|string $mode = true): self
    {
        $this->column['edit'] = $mode;
        return $this->sync();
    }

    public function toolbar(string $toolbar): self
    {
        $this->column['toolbar'] = $toolbar;
        return $this->sync();
    }

    public function templet(mixed $templet): self
    {
        $this->column['templet'] = $templet;
        return $this->sync();
    }

    public function options(array $options): self
    {
        foreach ($options as $name => $value) {
            if (is_string($name) && trim($name) !== '') {
                $this->column[trim($name)] = $value;
            }
        }
        return $this->sync();
    }

    public function option(string $name, mixed $value): self
    {
        $name = trim($name);
        if ($name !== '') {
            $this->column[$name] = $value;
        }
        return $this->sync();
    }

    /**
     * @return array<string, mixed>
     */
    public function export(): array
    {
        return $this->column;
    }

    private function sync(): self
    {
        if (is_callable($this->syncHandler)) {
            $this->column = ($this->syncHandler)($this->column);
        } elseif ($this->canSync()) {
            $this->column = $this->builder->replaceColumn($this->index, $this->column);
        }
        return $this;
    }

    private function canSync(): bool
    {
        return $this->index !== null && $this->builder->canSyncTableAttachment($this->version);
    }
}
