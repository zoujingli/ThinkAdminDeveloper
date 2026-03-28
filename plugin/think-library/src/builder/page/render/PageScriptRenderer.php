<?php

declare(strict_types=1);

namespace think\admin\builder\page\render;

/**
 * 页面脚本渲染器.
 * @class PageScriptRenderer
 */
class PageScriptRenderer
{
    /**
     * @param array<int, string> $readyScripts
     * @param array<int, string> $scripts
     */
    public function render(array $readyScripts, array $scripts): string
    {
        $ready = (new PageReadyScriptRenderer())->render(
            (new PageBootScriptRenderer())->render($readyScripts),
        );
        return $ready . (new PageCustomScriptRenderer())->render($scripts);
    }
}
