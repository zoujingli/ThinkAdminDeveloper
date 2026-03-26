<?php

declare(strict_types=1);

namespace plugin\system\builder;

use think\admin\builder\base\render\BuilderAttributes;

/**
 * 图片选择弹层渲染器。
 * @class UploadImageDialogBuilder
 */
class UploadImageDialogBuilder
{
    /**
     * @param array<string, mixed> $context
     */
    public static function render(array $context): string
    {
        $title = lang('图片选择器');
        $searchPlaceholder = lang('请输入搜索关键词');
        $searchText = lang('搜 索');
        $uploadText = lang('上传图片');
        $removeConfirm = lang('确认要移除这张图片吗？');
        $confirmText = lang('已选 %s 张，确认', ['{{data.length}}']);
        $removeAllowed = !empty($context['removeAllowed']);
        $uploadButton = self::renderUploadButton($context);
        $config = self::encode([
            'buttonSelector' => self::buttonSelector($context),
            'multiple' => strval($context['file'] ?? 'image') === 'images',
            'removeAllowed' => $removeAllowed,
            'removeUrl' => strval($context['removeUrl'] ?? ''),
            'imageUrl' => strval($context['imageUrl'] ?? ''),
            'type' => strval($context['type'] ?? 'gif,png,jpg,jpeg'),
            'removeConfirm' => $removeConfirm,
        ]);

        $removeButton = $removeAllowed
            ? '                <span class="layui-icon layui-icon-close image-dialog-item-close" @click.stop="remove(x)"></span>'
            : '';

        return <<<HTML
<div class="image-dialog" id="ImageDialog" v-cloak>
    <div class="image-dialog-head">
        <label class="pull-left flex">
            <input class="layui-input mr5 image-dialog-search-input" v-model="keys" placeholder="{$searchPlaceholder}">
            <a class="layui-btn layui-btn-sm layui-btn-normal" @click="search">{$searchText}</a>
        </label>
        <div class="pull-right">
            <a class="layui-btn layui-btn-sm layui-btn-normal" @click="uploadImage">{$uploadText}</a>
        </div>
    </div>
    <div class="image-dialog-body">
        <div class="image-dialog-item" v-for="x in list" @click="setItem(x)" v-show="show" :class="{'image-dialog-checked':x.checked}">
            <div class="uploadimage" :style="x.style"></div>
            <p class="image-dialog-item-name layui-elip" v-text="x.name"></p>
            <div class="image-dialog-item-tool">
                <span class="image-dialog-item-type">{{x.xext.toUpperCase()}}</span>
                <span class="image-dialog-item-size">{{formatSize(x.size)}}</span>
{$removeButton}
            </div>
        </div>
    </div>
    <div class="image-dialog-foot relative">
        <div id="ImageDialogPage" class="image-dialog-page"></div>
        <div id="ImageDialogButton" class="image-dialog-button layui-btn layui-btn-normal" v-if="data.length>0" @click="confirm">{$confirmText}</div>
    </div>
</div>

<script>
    $.module.use(['vue'], function (vue) {
        var config = {$config};
        var app = new vue({
            el: '#ImageDialog',
            data: {
                didx: 0,
                page: 1, limit: 15, show: false, mult: false,
                keys: '', list: [], data: [], idxs: {}, urls: [],
            },
            created: function () {
                this.didx = $.msg.mdx.pop();
                this.\$btn = config.buttonSelector ? $(config.buttonSelector) : $();
                this.\$ups = $('#ImageDialogUploadLayout [data-file]');
                this.mult = !!config.multiple;
                this.loadPage();
            },
            methods: {
                search: function () {
                    this.page = 1;
                    this.loadPage();
                },
                confirm: function () {
                    this.urls = [];
                    this.data.forEach(function (file) {
                        app.setValue(file.xurl);
                    });
                    this.setInput();
                },
                remove: function (x) {
                    if (!config.removeAllowed || !config.removeUrl) return;
                    $.msg.confirm(config.removeConfirm, function () {
                        $.form.load(config.removeUrl, {id: x.id}, 'POST', function (ret) {
                            ret.code > 0 ? app.loadPage() : $.msg.error(ret.info);
                            return app.\$forceUpdate(), false;
                        });
                    });
                },
                formatSize: function (size) {
                    return $.formatFileSize(size);
                },
                setItem: function (item) {
                    if (!this.mult) {
                        this.setValue(item.xurl).setInput();
                    } else if ((item.checked = !this.idxs[item.id])) {
                        (this.idxs[item.id] = item) && this.data.push(item);
                    } else {
                        delete this.idxs[item.id];
                        this.data.forEach(function (temp, idx) {
                            temp.id === item.id && app.data.splice(idx, 1);
                        });
                    }
                },
                setList: function (items, count) {
                    this.list = items;
                    this.list.forEach(function (item) {
                        item.checked = !!app.idxs[item.id];
                        item.style = 'background-image:url(' + item.xurl + ')';
                    });
                    this.addPage(count);
                },
                setValue: function (xurl) {
                    $.msg.close(this.didx);
                    this.urls.push(xurl) && this.\$btn.triggerHandler('push', xurl);
                    return this;
                },
                setInput: function () {
                    if (this.\$btn.data('input')) {
                        $(this.\$btn.data('input')).val(this.urls.join('|')).trigger('change');
                    }
                },
                addPage: function (count) {
                    this.show = true;
                    layui.laypage.render({
                        curr: this.page, count: count, limit: app.limit,
                        layout: ['count', 'prev', 'page', 'next', 'refresh'],
                        elem: 'ImageDialogPage', jump: function (obj, first) {
                            if (!first) app.loadPage(app.page = obj.curr);
                        },
                    });
                },
                loadPage: function () {
                    this.params = {page: this.page, limit: this.limit, output: 'layui.table', name: this.keys || ''};
                    this.params.type = config.type;
                    $.form.load(config.imageUrl, this.params, 'get', function (ret) {
                        return app.setList(ret.data, ret.count), false;
                    });
                },
                uploadImage: function () {
                    this.urls = [];
                    this.\$ups.off('push').on('push', function (e, xurl) {
                        app.setValue(xurl);
                    }).off('upload.complete').on('upload.complete', function () {
                        app.setInput();
                    }).click();
                },
            }
        });
    });
</script>

<label class="layui-hide" id="ImageDialogUploadLayout">
    {$uploadButton}
</label>
HTML;
    }

    /**
     * @param array<string, mixed> $context
     */
    private static function renderUploadButton(array $context): string
    {
        $attrs = BuilderAttributes::make([
            'data-file' => strval($context['file'] ?? 'image') === 'image' ? 'one' : 'mul',
            'data-type' => strval($context['type'] ?? 'gif,png,jpg,jpeg'),
            'data-path' => strval($context['path'] ?? ''),
            'data-size' => intval($context['size'] ?? 0),
            'data-cut-width' => intval($context['cutWidth'] ?? 0),
            'data-cut-height' => intval($context['cutHeight'] ?? 0),
            'data-max-width' => intval($context['maxWidth'] ?? 0),
            'data-max-height' => intval($context['maxHeight'] ?? 0),
        ]);

        return sprintf('<button %s></button>', $attrs->html());
    }

    /**
     * @param array<string, mixed> $context
     */
    private static function buttonSelector(array $context): string
    {
        $id = trim(strval($context['id'] ?? ''));
        return $id === '' ? '' : ('#' . $id);
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function encode(array $data): string
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?: '{}';
    }
}
