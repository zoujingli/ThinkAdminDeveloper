<?php

declare(strict_types=1);

namespace think\admin\builder\base\render;

/**
 * Builder 原始 HTML 节点渲染基类.
 * @class BuilderHtmlNodeRenderer
 */
abstract class BuilderHtmlNodeRenderer
{
    /**
     * @param array<string, mixed> $node
     */
    protected function renderHtml(array $node): string
    {
        return strval($node['html'] ?? '');
    }
}
