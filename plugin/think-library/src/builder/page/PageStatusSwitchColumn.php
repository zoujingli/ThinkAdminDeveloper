<?php

declare(strict_types=1);

namespace think\admin\builder\page;

/**
 * 页面状态开关列对象.
 * @class PageStatusSwitchColumn
 */
class PageStatusSwitchColumn extends PagePresetColumn
{
    public function __construct(PageBuilder $builder, private string $actionUrl, array $options = [])
    {
        parent::__construct($builder, $options);
    }

    public function actionUrl(string $actionUrl): self
    {
        $this->actionUrl = trim($actionUrl);
        return $this->sync();
    }

    public function templateId(string $templateId): self
    {
        $this->options['templateId'] = trim($templateId);
        return $this->sync();
    }

    public function filter(string $filter): self
    {
        $this->options['filter'] = trim($filter);
        return $this->sync();
    }

    public function auth(string $auth): self
    {
        $this->options['auth'] = trim($auth);
        return $this->sync();
    }

    public function valueExpr(string $value): self
    {
        $this->options['value'] = $value;
        return $this->sync();
    }

    public function checkedExpr(string $checked): self
    {
        $this->options['checked'] = $checked;
        return $this->sync();
    }

    public function toggleText(string $text): self
    {
        $this->options['text'] = $text;
        return $this->sync();
    }

    public function activeHtml(string $html): self
    {
        $this->options['activeHtml'] = $html;
        return $this->sync();
    }

    public function inactiveHtml(string $html): self
    {
        $this->options['inactiveHtml'] = $html;
        return $this->sync();
    }

    public function dataScript(string $script): self
    {
        $this->options['dataScript'] = trim($script);
        return $this->sync();
    }

    public function reloadSelector(string $selector): self
    {
        $this->options['reloadSelector'] = $selector;
        return $this->sync();
    }

    public function reloadOnError(bool $reload = true): self
    {
        $this->options['reloadOnError'] = $reload;
        return $this->sync();
    }

    public function reloadOnSuccess(bool $reload = true): self
    {
        $this->options['reloadOnSuccess'] = $reload;
        return $this->sync();
    }

    public function getActionUrl(): string
    {
        return $this->actionUrl;
    }

    protected function sync(): self
    {
        if ($this->isAttached()) {
            $this->syncResult($this->builder->replaceStatusSwitchColumn($this->index(), $this->actionUrl, $this->export(), $this->meta()));
        }
        return $this;
    }
}
