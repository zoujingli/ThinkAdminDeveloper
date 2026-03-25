<?php

declare(strict_types=1);

namespace think\admin\builder\page\render;

/**
 * 页面就绪脚本渲染器.
 * @class PageReadyScriptRenderer
 */
class PageReadyScriptRenderer
{
    public function render(string ...$segments): string
    {
        $lines = array_values(array_filter($segments, static fn(string $segment): bool => trim($segment) !== ''));
        $html = "\n<script>\n    $(function () {";
        if (count($lines) > 0) {
            $html .= "\n" . join("\n", $lines);
        }
        return $html . "\n    });\n</script>";
    }
}
