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
     * @param array<string, mixed> $options
     * @param array<int, string> $bootScripts
     * @param array<int, string> $initScripts
     * @param array<int, string> $scripts
     */
    public function render(
        string $tableId,
        array $options,
        array $bootScripts,
        array $initScripts,
        array $scripts,
        PageScriptRenderContext $context
    ): string {
        $ready = (new PageReadyScriptRenderer())->render(
            (new PageBootScriptRenderer())->render($bootScripts),
            (new PageTableInitScriptRenderer())->render($tableId, $options, $context),
            (new PageInitScriptRenderer())->render($initScripts),
        );
        return $ready . (new PageCustomScriptRenderer())->render($scripts);
    }
}
