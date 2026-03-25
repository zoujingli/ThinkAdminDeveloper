<?php

declare(strict_types=1);

namespace think\admin\builder\base\render;

/**
 * Builder 渲染流水线基类.
 * @class BuilderRenderPipeline
 */
class BuilderRenderPipeline
{
    /**
     * @param array<int, array<string, mixed>> $nodes
     */
    protected function renderNodeContent(array $nodes, BuilderRenderState $state, string $separator = "\n"): string
    {
        $html = [];
        $context = $state->nodeRenderContext();
        $factory = $state->nodeRendererFactory();
        foreach ($nodes as $node) {
            if (!is_array($node)) {
                continue;
            }
            $item = $factory->create($node)->render($node, $context);
            if ($item !== '') {
                $html[] = $item;
            }
        }
        return join($separator, $html);
    }

    protected function renderSchemaScript(BuilderRenderState $state, string $className): string
    {
        return (new JsonScriptRenderer())->render($state->schema(), $className);
    }
}
