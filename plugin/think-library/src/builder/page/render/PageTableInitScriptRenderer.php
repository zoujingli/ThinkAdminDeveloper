<?php

declare(strict_types=1);

namespace think\admin\builder\page\render;

/**
 * 页面表格初始化脚本渲染器.
 * @class PageTableInitScriptRenderer
 */
class PageTableInitScriptRenderer
{
    /**
     * @param array<string, mixed> $options
     */
    public function render(string $tableId, array $options, PageScriptRenderContext $context): string
    {
        return "        let \$table = $('#" . addslashes($tableId) . "').layTable(" . $context->encodeJs($options) . ');';
    }
}
