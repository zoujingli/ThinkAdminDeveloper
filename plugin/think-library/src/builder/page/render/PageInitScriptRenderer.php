<?php

declare(strict_types=1);

namespace think\admin\builder\page\render;

/**
 * 页面初始化后脚本渲染器.
 * @class PageInitScriptRenderer
 */
class PageInitScriptRenderer
{
    /**
     * @param array<int, string> $scripts
     */
    public function render(array $scripts): string
    {
        return $this->renderLines($scripts);
    }

    /**
     * @param array<int, string> $scripts
     */
    private function renderLines(array $scripts): string
    {
        $html = [];
        foreach ($scripts as $line) {
            $line = trim($line);
            if ($line !== '') {
                $html[] = "        {$line}";
            }
        }
        return join("\n", $html);
    }
}
