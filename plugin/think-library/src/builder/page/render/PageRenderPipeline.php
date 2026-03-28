<?php

declare(strict_types=1);

namespace think\admin\builder\page\render;

use think\admin\builder\base\render\BuilderRenderPipeline;

/**
 * 页面渲染流水线.
 * @class PageRenderPipeline
 */
class PageRenderPipeline extends BuilderRenderPipeline
{
    /**
     * @param array<int, string> $buttons
     */
    public function renderShell(
        PageRenderState $state,
        string $preset,
        string $title,
        array $buttons,
        string $message,
        string $contentClass,
        string $content,
        string $templates,
        string $scripts
    ): string {
        return (new PageShellRenderer())->render(
            $preset,
            $title,
            $buttons,
            $message,
            $contentClass,
            $content,
            $templates,
            $scripts,
            $this->renderSchemaScript($state, 'page-builder-schema')
        );
    }

    /**
     * @param array<int, array<string, mixed>> $nodes
     */
    public function renderContentNodes(array $nodes, PageRenderState $state): string
    {
        return $this->renderNodeContent($nodes, $state, "\n\t\t\t\t");
    }

    /**
     * @param array<int, array<string, mixed>> $fields
     * @param array<string, mixed> $attrs
     */
    public function renderSearch(
        array $fields,
        array $attrs,
        string $tableId,
        string $action,
        string $legend,
        bool $legendEnabled,
        PageRenderState $state
    ): string {
        return (new PageSearchRenderer())->render(
            $fields,
            $attrs,
            $tableId,
            $action,
            $legend,
            $legendEnabled,
            $state->searchRenderContext()
        );
    }

    /**
     * @param array<string, mixed> $attrs
     */
    public function renderTable(array $attrs, PageRenderState $state): string
    {
        return (new PageTableRenderer())->render($attrs, $state->tableRenderContext());
    }

    /**
     * @param array<string, string> $templates
     */
    public function renderTemplates(array $templates): string
    {
        return (new PageTemplateRenderer())->render($templates);
    }

    /**
     * @param array<int, string> $readyScripts
     * @param array<int, string> $initScripts
     * @param array<int, string> $scripts
     */
    public function renderScripts(
        array $readyScripts,
        array $scripts
    ): string {
        return (new PageScriptRenderer())->render($readyScripts, $scripts);
    }
}
