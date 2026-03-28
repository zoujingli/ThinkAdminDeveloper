<?php

declare(strict_types=1);

namespace plugin\system\builder;

use think\admin\builder\page\PageBuilder;

/**
 * 系统列表表格预设。
 * @class SystemTablePreset
 */
class SystemTablePreset
{
    /**
     * @return array<string, mixed>
     */
    public static function idColumn(string $field = 'id', string $title = 'ID', int $width = 80): array
    {
        return [
            'field' => $field,
            'title' => $title,
            'width' => $width,
            'align' => 'center',
            'sort' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function iconColumn(string $field = 'icon', string $title = '图标', int $width = 80): array
    {
        return [
            'field' => $field,
            'title' => $title,
            'width' => $width,
            'align' => 'center',
            'templet' => '<div><i class="{{d.icon}} font-s18"></i></div>',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function textColumn(string $field, string $title, int $minWidth = 100, string $align = 'center', string $empty = '-'): array
    {
        return [
            'field' => $field,
            'title' => $title,
            'minWidth' => $minWidth,
            'align' => $align,
            'templet' => sprintf('<div>{{d.%s||"%s"}}</div>', $field, addslashes($empty)),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function avatarColumn(string $field = 'headimg', string $title = '头像', int $width = 60): array
    {
        return [
            'field' => $field,
            'title' => $title,
            'width' => $width,
            'align' => 'center',
            'templet' => PageBuilder::js(<<<'SCRIPT'
function (d) {
    if (!d.headimg) return '-';
    return layui.laytpl('<div class="headimg headimg-ss shadow-inset ma0" data-tips-image data-tips-hover data-lazy-src="{{d.headimg}}"></div>').render(d);
}
SCRIPT),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function pluginColumn(string $field = 'plugin_title', string $title = '所属插件', int $minWidth = 120): array
    {
        $mixed = json_encode(strval(lang('跨插件')), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '"跨插件"';
        $common = json_encode(strval(lang('未绑定')), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '"未绑定"';
        return [
            'field' => $field,
            'title' => $title,
            'align' => 'center',
            'minWidth' => $minWidth,
            'templet' => PageBuilder::js(sprintf(<<<'SCRIPT'
function (d) {
    if (d.plugin_group === 'mixed') {
        d.badge = '<span class="layui-badge layui-bg-orange">' + (d.plugin_title || %s) + '</span>';
    } else if (d.plugin_group === 'common') {
        d.badge = '<span class="layui-badge layui-bg-gray">' + (d.plugin_title || %s) + '</span>';
    } else {
        d.badge = '<span class="layui-badge layui-bg-blue">' + (d.plugin_title || '-') + '</span>';
    }
    d.extra = d.plugin_text && Number(d.plugin_count || 0) > 1 ? '<div class="color-desc nowrap">' + d.plugin_text + '</div>' : '';
    return d.badge + d.extra;
}
SCRIPT, $mixed, $common)),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function linkColumn(string $field, string $title, int $minWidth = 200, string $empty = '-'): array
    {
        return [
            'field' => $field,
            'title' => $title,
            'minWidth' => $minWidth,
            'templet' => sprintf('<div>{{d.%s||"%s"}}</div>', $field, addslashes($empty)),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function filePreviewColumn(string $field = 'xurl', string $title = '查看文件', int $minWidth = 90): array
    {
        $playVideo = json_encode(strval(lang('播放视频')), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '"播放视频"';
        $playAudio = json_encode(strval(lang('播放音频')), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '"播放音频"';
        $viewDownload = json_encode(strval(lang('查看下载')), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '"查看下载"';
        return [
            'field' => $field,
            'title' => $title,
            'minWidth' => $minWidth,
            'align' => 'center',
            'templet' => PageBuilder::js(sprintf(<<<'SCRIPT'
function (d) {
    var field = %s;
    var fileUrl = String(d[field] || d.file_url || d.xurl || '');
    if (fileUrl === '') {
        return '<div>-</div>';
    }
    if (typeof d.mime === 'string' && /^image\//.test(d.mime)) {
        return '<div><a target="_blank" data-tips-hover data-tips-image="' + fileUrl + '"><i class="layui-icon layui-icon-picture"></i></a></div>';
    }
    if (typeof d.mime === 'string' && /^video\//.test(d.mime)) {
        return '<div><a target="_blank" data-video-player="' + fileUrl + '" data-tips-text="' + %s + '"><i class="layui-icon layui-icon-video"></i></a></div>';
    }
    if (typeof d.mime === 'string' && /^audio\//.test(d.mime)) {
        return '<div><a target="_blank" data-video-player="' + fileUrl + '" data-tips-text="' + %s + '"><i class="layui-icon layui-icon-headset"></i></a></div>';
    }
    return '<div><a target="_blank" href="' + fileUrl + '" data-tips-text="' + %s + '"><i class="layui-icon layui-icon-file"></i></a></div>';
}
SCRIPT, json_encode($field, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '"xurl"', $playVideo, $playAudio, $viewDownload)),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function timeColumn(string $field = 'create_time', string $title = '创建时间', int $minWidth = 170): array
    {
        return [
            'field' => $field,
            'title' => $title,
            'align' => 'center',
            'minWidth' => $minWidth,
            'sort' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function toolbar(string $title = '操作面板', int $minWidth = 150, array $extra = []): array
    {
        return ['title' => $title, 'minWidth' => $minWidth] + $extra;
    }

    /**
     * @return array<string, mixed>
     */
    public static function statusOptions(string $title = '使用状态', string $active = '已启用', string $inactive = '已禁用'): array
    {
        return [
            'title' => $title,
            'activeHtml' => sprintf('<b class="color-green">%s</b>', $active),
            'inactiveHtml' => sprintf('<b class="color-red">%s</b>', $inactive),
        ];
    }
}
