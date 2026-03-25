<?php

declare(strict_types=1);

namespace think\admin\builder\base\render;

/**
 * 通用脚本标签渲染器.
 * @class InlineScriptRenderer
 */
class InlineScriptRenderer
{
    /**
     * @param array<int, string> $scripts
     */
    public function render(array $scripts): string
    {
        if (count($scripts) < 1) {
            return '';
        }

        $html = '';
        foreach ($scripts as $script) {
            $html .= "\n<script>\n{$script}\n</script>";
        }
        return $html;
    }
}
