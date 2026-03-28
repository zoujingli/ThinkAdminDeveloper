<?php

declare(strict_types=1);

namespace think\admin\builder\page;

/**
 * 页面预设列对象基类.
 * @class PagePresetColumn
 */
abstract class PagePresetColumn
{
    private ?int $index = null;
    private ?int $version = null;
    protected $syncHandler = null;

    /**
     * @var array<int, string>
     */
    private array $templateKeys = [];

    /**
     * @var array<int, int>
     */
    private array $scriptIndexes = [];

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(protected PageBuilder $builder, protected array $options = [])
    {
    }

    /**
     * @param array<string, mixed> $result
     */
    public function attachResult(array $result): self
    {
        $this->index = intval($result['index'] ?? 0);
        $this->version = isset($result['version']) ? intval($result['version']) : null;
        $this->templateKeys = array_values(array_filter((array)($result['templateKeys'] ?? []), 'is_string'));
        $this->scriptIndexes = array_values(array_map('intval', (array)($result['scriptIndexes'] ?? [])));
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
        $this->options['field'] = trim($field);
        return $this->sync();
    }

    public function title(string $title): self
    {
        $this->options['title'] = $title;
        return $this->sync();
    }

    public function width(int|string $width): self
    {
        $this->options['width'] = $width;
        return $this->sync();
    }

    public function minWidth(int|string $width): self
    {
        $this->options['minWidth'] = $width;
        return $this->sync();
    }

    public function align(string $align): self
    {
        $this->options['align'] = trim($align);
        return $this->sync();
    }

    public function fixed(bool|string $fixed = true): self
    {
        $this->options['fixed'] = $fixed;
        return $this->sync();
    }

    public function sort(bool $sort = true): self
    {
        $this->options['sort'] = $sort;
        return $this->sync();
    }

    public function hide(bool $hide = true): self
    {
        $this->options['hide'] = $hide;
        return $this->sync();
    }

    public function option(string $name, mixed $value): self
    {
        $name = trim($name);
        if ($name !== '') {
            $this->options[$name] = $value;
        }
        return $this->sync();
    }

    public function options(array $options): self
    {
        foreach ($options as $name => $value) {
            if (is_string($name) && trim($name) !== '') {
                $this->options[trim($name)] = $value;
            }
        }
        return $this->sync();
    }

    /**
     * @return array<string, mixed>
     */
    public function export(): array
    {
        return $this->options;
    }

    protected function isAttached(): bool
    {
        return $this->index !== null;
    }

    protected function canSync(): bool
    {
        return $this->isAttached() && $this->builder->canSyncTableAttachment($this->version);
    }

    protected function index(): int
    {
        return intval($this->index);
    }

    /**
     * @return array{templateKeys: array<int, string>, scriptIndexes: array<int, int>}
     */
    protected function meta(): array
    {
        return ['templateKeys' => $this->templateKeys, 'scriptIndexes' => $this->scriptIndexes];
    }

    /**
     * @param array<string, mixed> $result
     */
    protected function syncResult(array $result): self
    {
        return $this->attachResult($result);
    }

    abstract protected function sync(): self;
}
