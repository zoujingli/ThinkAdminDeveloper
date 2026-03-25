<?php

declare(strict_types=1);

namespace think\admin\builder\form\render;

use think\admin\builder\base\render\BuilderNodeRendererFactory;

/**
 * 表单节点渲染器工厂.
 * @class FormNodeRendererFactory
 */
class FormNodeRendererFactory extends BuilderNodeRendererFactory
{
    /**
     * @param array<string, mixed> $node
     */
    public function create(array $node): FormNodeRendererInterface
    {
        $class = $this->resolveRendererClass($node);
        return new $class();
    }

    protected function rendererMap(): array
    {
        return [
            'html' => FormHtmlNodeRenderer::class,
            'field' => FormFieldNodeRenderer::class,
            'button' => FormButtonNodeRenderer::class,
            'actions' => FormActionBarRenderer::class,
            'element' => FormElementNodeRenderer::class,
        ];
    }

    protected function fallbackRendererClass(): string
    {
        return FormHtmlNodeRenderer::class;
    }
}
