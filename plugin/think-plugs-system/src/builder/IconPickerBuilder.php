<?php

declare(strict_types=1);

namespace plugin\system\builder;

use think\admin\builder\base\render\BuilderAttributes;
use think\admin\service\AppService;

/**
 * 图标选择器构建器。
 * @class IconPickerBuilder
 */
class IconPickerBuilder
{
    /**
     * @param array<string, mixed> $context
     */
    public static function render(array $context): string
    {
        $title = trim(strval($context['title'] ?? '图标选择器'));
        $field = trim(strval($context['field'] ?? 'icon'));
        $layuiIcons = array_values(array_map('strval', is_array($context['layuiIcons'] ?? null) ? $context['layuiIcons'] : []));
        $thinkIcons = array_values(array_map('strval', is_array($context['thinkIcons'] ?? null) ? $context['thinkIcons'] : []));
        $extraIcons = array_values(array_map('strval', is_array($context['extraIcons'] ?? null) ? $context['extraIcons'] : []));

        $head = [
            '<!DOCTYPE html>',
            '<html lang="zh-CN">',
            '<head>',
            '    <meta charset="utf-8">',
            '    <title>' . self::escape($title) . '</title>',
            '    <meta name="renderer" content="webkit">',
            '    <meta name="format-detection" content="telephone=no">',
            '    <meta name="mobile-web-app-capable" content="yes">',
            '    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">',
            '    <meta name="apple-mobile-web-app-status-bar-style" content="black">',
            '    <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1">',
            '    <link rel="stylesheet" href="' . self::asset('static/theme/css/iconfont.css?at=' . date('md')) . '">',
            '    <link rel="stylesheet" href="' . self::asset('static/plugs/layui/css/layui.css?v=' . date('ymd')) . '">',
            '    <link rel="stylesheet" href="' . self::asset('static/theme/css/console.css?at=' . date('md')) . '">',
        ];

        if (is_file(syspath('public/static/extra/icon/iconfont.css'))) {
            $head[] = '    <link rel="stylesheet" href="' . self::asset('static/extra/icon/iconfont.css?at=' . date('md')) . '">';
        }

        $head[] = '</head>';
        $head[] = '<body class="ta-icon-picker-body layui-layout-body layui-layout-theme-default">';
        $head[] = '    <div class="ta-icon-picker" data-icon-picker data-field="' . self::escape($field) . '">';
        $head[] = '        <div class="ta-icon-picker__header">';
        $head[] = '            <div class="ta-icon-picker__title">选择图标</div>';
        $head[] = '            <label class="ta-icon-picker__search">';
        $head[] = '                <input type="text" class="layui-input" placeholder="请输入图标名称" data-icon-filter>';
        $head[] = '            </label>';
        $head[] = '        </div>';
        $head[] = '        <ul class="ta-icon-picker__list">';
        $head[] = self::renderItems($extraIcons, 'iconfont');
        $head[] = self::renderItems($layuiIcons, 'layui-icon');
        $head[] = self::renderItems($thinkIcons, 'iconfont');
        $head[] = '        </ul>';
        $head[] = '    </div>';
        $head[] = '    <script src="' . self::asset('static/plugs/jquery/jquery.min.js') . '"></script>';
        $head[] = '    <script>' . self::script() . '</script>';
        $head[] = '</body>';
        $head[] = '</html>';

        return join("\n", $head);
    }

    /**
     * @param array<int, string> $icons
     */
    private static function renderItems(array $icons, string $prefix): string
    {
        $html = [];
        foreach ($icons as $icon) {
            if ($icon === '') {
                continue;
            }
            $class = $prefix . ' ' . $icon;
            $html[] = sprintf(
                '<li class="ta-icon-picker__item" data-icon-name="%s"><i class="%s"></i><div class="ta-icon-picker__name">%s</div></li>',
                self::escape(strtolower($icon)),
                self::escape($class),
                self::escape($icon)
            );
        }
        return join("\n", $html);
    }

    private static function asset(string $path): string
    {
        return strval(AppService::uri($path, '__ROOT__'));
    }

    private static function escape(string $value): string
    {
        return BuilderAttributes::escape($value);
    }
    private static function script(): string
    {
        return <<<'SCRIPT'
$(function () {
    var $root = $('[data-icon-picker]');
    var field = String($root.data('field') || 'icon');

    $root.on('click', '[data-icon-name]', function () {
        var className = $(this).find('i').attr('class') || '';
        if (!className) return;
        top.$('[name="' + field + '"]').val(className).trigger('change');
        if (top.layer && window.name) {
            top.layer.close(top.layer.getFrameIndex(window.name));
        }
    });

    $root.on('input', '[data-icon-filter]', function () {
        var keyword = $.trim(String(this.value || '')).toLowerCase();
        $root.find('[data-icon-name]').each(function () {
            var matched = keyword === '' || String($(this).data('iconName') || '').indexOf(keyword) >= 0;
            $(this).toggleClass('is-hidden', !matched);
        });
    });
});
SCRIPT;
    }
}
