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
        string $title,
        array $buttons,
        string $message,
        string $contentClass,
        string $content,
        string $templates,
        string $scripts
    ): string {
        return (new PageShellRenderer())->render(
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
     * @param array<int, string> $rowActions
     */
    public function renderTemplates(array $templates, string $toolbarId, array $rowActions): string
    {
        return (new PageTemplateRenderer())->render($templates, $toolbarId, $rowActions);
    }

    /**
     * @param array<string, mixed> $options
     * @param array<int, string> $bootScripts
     * @param array<int, string> $initScripts
     * @param array<int, string> $scripts
     */
    public function renderScripts(
        string $tableId,
        array $options,
        array $bootScripts,
        array $initScripts,
        array $scripts,
        PageRenderState $state
    ): string {
        return (new PageScriptRenderer())->render(
            $tableId,
            $options,
            $bootScripts,
            $initScripts,
            $scripts,
            $state->scriptRenderContext()
        );
    }
}
