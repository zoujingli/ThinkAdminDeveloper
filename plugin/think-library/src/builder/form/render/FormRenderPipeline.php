<?php

declare(strict_types=1);

namespace think\admin\builder\form\render;

use think\admin\builder\base\render\BuilderRenderPipeline;

/**
 * 表单渲染流水线.
 * @class FormRenderPipeline
 */
class FormRenderPipeline extends BuilderRenderPipeline
{
    /**
     * @param array<string, mixed> $attrs
     * @param array<int, array<string, mixed>> $content
     * @param array<int, string> $fields
     * @param array<int, string> $headerButtons
     * @param array<int, string> $buttons
     * @param array<int, string> $scripts
     */
    public function renderShell(
        array $attrs,
        array $bodyAttrs,
        array $content,
        array $fields,
        array $headerButtons,
        array $buttons,
        FormRenderState $state,
        array $scripts
    ): string {
        return (new FormShellRenderer())->render(
            $attrs,
            $bodyAttrs,
            $content,
            $fields,
            $headerButtons,
            $buttons,
            $state->schema(),
            $scripts,
            $state->nodeRenderContext()
        );
    }

    /**
     * @param array<string, mixed> $field
     */
    public function renderField(array $field, string $variable): string
    {
        return (new FormFieldRendererFactory())->create($field)->render(new FormFieldRenderContext($field, $variable));
    }

    /**
     * @param array<int, array<string, mixed>> $nodes
     */
    public function renderContentNodes(array $nodes, FormRenderState $state): string
    {
        return $this->renderNodeContent($nodes, $state, "\n\t\t");
    }
}
