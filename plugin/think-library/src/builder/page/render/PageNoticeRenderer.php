<?php

declare(strict_types=1);

namespace think\admin\builder\page\render;

use think\admin\builder\base\render\BuilderAttributes;

/**
 * 页面提示渲染器.
 * @class PageNoticeRenderer
 */
class PageNoticeRenderer
{
    public function render(string $message): string
    {
        if ($message === '') {
            return '';
        }

        return sprintf(
            '<div class="think-box-notify" type="error"><b>%s</b><span>%s</span></div>',
            BuilderAttributes::escape('系统提示：'),
            $message
        );
    }
}
