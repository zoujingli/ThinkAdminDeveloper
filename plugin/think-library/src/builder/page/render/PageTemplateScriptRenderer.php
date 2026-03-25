<?php

declare(strict_types=1);

namespace think\admin\builder\page\render;

use think\admin\builder\base\render\BuilderAttributes;

/**
 * 页面模板脚本片段渲染器.
 * @class PageTemplateScriptRenderer
 */
class PageTemplateScriptRenderer
{
    public function render(string $id, string $template): string
    {
        return sprintf(
            "\n<script type=\"text/html\" id=\"%s\">\n%s\n</script>",
            BuilderAttributes::escape($id),
            $template
        );
    }
}
