<?php

declare(strict_types=1);

namespace think\admin\builder\page;

/**
 * 页面排序输入列对象.
 * @class PageSortInputColumn
 */
class PageSortInputColumn extends PagePresetColumn
{
    public function __construct(PageBuilder $builder, private string $actionUrl = '{:sysuri()}', array $options = [])
    {
        parent::__construct($builder, $options);
    }

    public function actionUrl(string $actionUrl): self
    {
        $this->actionUrl = trim($actionUrl) ?: '{:sysuri()}';
        return $this->sync();
    }

    public function templateId(string $templateId): self
    {
        $this->options['templateId'] = trim($templateId);
        return $this->sync();
    }

    public function dataValue(string $dataValue): self
    {
        $this->options['dataValue'] = $dataValue;
        return $this->sync();
    }

    public function inputValue(string $inputValue): self
    {
        $this->options['inputValue'] = $inputValue;
        return $this->sync();
    }

    public function inputAttrs(array $attrs): self
    {
        $this->options['inputAttrs'] = $attrs;
        return $this->sync();
    }

    public function inputAttr(string $name, mixed $value): self
    {
        $name = trim($name);
        if ($name !== '') {
            $attrs = is_array($this->options['inputAttrs'] ?? null) ? $this->options['inputAttrs'] : [];
            $attrs[$name] = $value;
            $this->options['inputAttrs'] = $attrs;
        }
        return $this->sync();
    }

    public function getActionUrl(): string
    {
        return $this->actionUrl;
    }

    protected function sync(): self
    {
        if ($this->isAttached()) {
            $this->syncResult($this->builder->replaceSortInputColumn($this->index(), $this->actionUrl, $this->export(), $this->meta()));
        }
        return $this;
    }
}
