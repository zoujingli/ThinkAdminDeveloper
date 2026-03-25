<?php

declare(strict_types=1);

namespace think\admin\builder\page\render;

use think\admin\builder\base\render\InlineScriptRenderer;

/**
 * 页面附加脚本渲染器.
 * @class PageCustomScriptRenderer
 */
class PageCustomScriptRenderer
{
    /**
     * @param array<int, string> $scripts
     */
    public function render(array $scripts): string
    {
        return (new InlineScriptRenderer())->render($scripts);
    }
}
