<?php

declare(strict_types=1);

namespace think\admin\builder\page;

/**
 * 页面表格配置对象.
 * @class PageTableOptions
 */
class PageTableOptions
{
    /**
     * @param array<string, mixed> $options
     */
    public function __construct(private PageBuilder $builder, private array $options = [])
    {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function attach(array $options): self
    {
        $this->options = $options;
        return $this;
    }

    public function even(bool $even = true): self
    {
        $this->options['even'] = $even;
        return $this->sync();
    }

    public function height(string|int $height): self
    {
        $this->options['height'] = $height;
        return $this->sync();
    }

    /**
     * @param bool|array<string, mixed> $page
     */
    public function page(bool|array $page = true): self
    {
        $this->options['page'] = $page;
        return $this->sync();
    }

    public function limit(int $limit): self
    {
        $this->options['limit'] = $limit;
        return $this->sync();
    }

    /**
     * @param array<int, int|string> $limits
     */
    public function limits(array $limits): self
    {
        $this->options['limits'] = $limits;
        return $this->sync();
    }

    /**
     * @param array<string, mixed> $where
     */
    public function where(array $where): self
    {
        $this->options['where'] = $where;
        return $this->sync();
    }

    /**
     * @param string|array<int, string>|bool $toolbar
     */
    public function toolbar(string|array|bool $toolbar): self
    {
        $this->options['toolbar'] = $toolbar;
        return $this->sync();
    }

    /**
     * @param array<int, string>|bool $toolbar
     */
    public function defaultToolbar(array|bool $toolbar): self
    {
        $this->options['defaultToolbar'] = $toolbar;
        return $this->sync();
    }

    public function skin(string $skin): self
    {
        $this->options['skin'] = trim($skin);
        return $this->sync();
    }

    public function size(string $size): self
    {
        $this->options['size'] = trim($size);
        return $this->sync();
    }

    public function lineStyle(string $style): self
    {
        $this->options['lineStyle'] = $style;
        return $this->sync();
    }

    /**
     * @param array<string, mixed> $request
     */
    public function request(array $request): self
    {
        $this->options['request'] = $request;
        return $this->sync();
    }

    /**
     * @param array<string, mixed> $response
     */
    public function response(array $response): self
    {
        $this->options['response'] = $response;
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

    /**
     * @param array<string, mixed> $options
     */
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

    private function sync(): self
    {
        $this->options = $this->builder->replaceTableOptions($this->options);
        return $this;
    }
}
