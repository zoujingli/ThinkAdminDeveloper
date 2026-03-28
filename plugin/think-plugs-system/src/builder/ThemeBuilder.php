<?php

declare(strict_types=1);

namespace plugin\system\builder;

use think\admin\builder\form\FormBuilder;
use think\admin\builder\form\module\FormModules;

/**
 * 后台主题选择构建器。
 * @class ThemeBuilder
 */
class ThemeBuilder
{
    /**
     * @param array<string, mixed> $context
     */
    public static function buildUserThemeForm(array $context): FormBuilder
    {
        return self::buildThemeForm($context, false);
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function buildConfigThemeForm(array $context): FormBuilder
    {
        return self::buildThemeForm($context, true);
    }

    /**
     * @param array<string, mixed> $context
     */
    private static function buildThemeForm(array $context, bool $configScene): FormBuilder
    {
        $themes = is_array($context['themes'] ?? null) ? $context['themes'] : [];
        $theme = strval($context['theme'] ?? 'default');
        $picker = strval($context['picker'] ?? '');

        return FormBuilder::dialogForm('form')
            ->define(function ($form) use ($themes, $theme, $picker, $configScene) {
                $form->title($configScene ? '选择后台默认配色' : '后台配色方案')
                    ->action(sysuri())
                    ->attr('id', 'theme')
                    ->class('theme-picker-form theme-picker-form--compact');

                $form->html(self::compactThemeStyle());

                FormModules::themePalette($form, $themes, $theme, [
                    'class' => 'layui-form-item mb0 theme-picker-field',
                    'title' => '后台配色方案',
                    'subtitle' => 'Theme Style',
                    'label_class' => 'help-label theme-picker-heading',
                    'description_class' => 'help-block theme-picker-description',
                    'palette_class' => 'theme-palette theme-palette--compact',
                    'description' => $configScene
                        ? '统一为“标准 / 品牌侧栏 / 双栏导航”三种后台布局语义，选择卡片后会实时预览，确认后再回填系统参数页。'
                        : '统一为“标准 / 品牌侧栏 / 双栏导航”三种后台布局语义，新增皮肤可直接预览后保存生效。',
                    'help' => $configScene
                        ? '选中卡片后会先切换后台预览；点击“确认选择”才会回填到系统参数表单，点击“取消选择”会恢复原主题。'
                        : '保存后立即写入当前账号主题；系统默认主题可在系统参数配置中设置，后续新用户登录也会继承。',
                    'help_class' => 'help-block theme-picker-help',
                ]);

                $form->actions(function ($actions) use ($configScene) {
                    if ($configScene) {
                        $actions->button('确认选择', 'button', '', ['data-theme-confirm' => null])
                            ->button('取消选择', 'button', '', ['data-theme-cancel' => null], 'layui-btn-danger');
                        return;
                    }

                    $actions->submit('保存配置')
                        ->button('取消修改', 'button', '', ['data-close' => null], 'layui-btn-danger');
                });

                $form->script(sprintf("const themeCatalog=%s;", json_encode($themes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}'));
                if ($configScene) {
                    $form->script(sprintf("const themePicker=%s;", json_encode($picker, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '""'));
                    $form->script(self::configThemeScript());
                } else {
                    $form->script(self::userThemeScript());
                }
            })
            ->build();
    }

    private static function compactThemeStyle(): string
    {
        return <<<'STYLE'
<style>
.theme-picker-form--compact{
    max-width:740px;
    margin:0 auto;
}
.theme-picker-form--compact .layui-form-item{
    margin-bottom:8px;
}
.theme-picker-form--compact .help-label{
    margin-bottom:2px;
    line-height:1.45;
}
.theme-picker-form--compact .help-label b{
    font-size:13px;
    margin-right:4px!important;
}
.theme-picker-form--compact .help-label span{
    font-size:11px;
}
.theme-picker-form--compact .theme-picker-description,
.theme-picker-form--compact .theme-picker-help{
    margin-top:2px;
    font-size:11px;
    line-height:1.55;
}
.theme-picker-form--compact .theme-palette--compact{
    gap:8px;
    margin-top:8px;
}
.theme-picker-form--compact .theme-palette-input,
.theme-picker-form--compact .layui-form-radio,
.theme-picker-form--compact .layui-form-checkbox{
    display:none !important;
}
.theme-picker-form--compact .theme-palette--compact .theme-palette-card{
    width:calc((100% - 40px) / 6);
    height:128px;
    flex:0 0 calc((100% - 40px) / 6);
    border-radius:5px;
    box-shadow:none;
}
.theme-picker-form--compact .theme-palette--compact .theme-palette-card:hover{
    box-shadow:none;
}
.theme-picker-form--compact .theme-palette--compact .theme-palette-card.active{
    box-shadow:none;
}
.theme-picker-form--compact .theme-palette--compact .theme-palette-check{
    top:auto;
    right:6px;
    bottom:6px;
    width:auto;
    height:auto;
    color:var(--ta-accent, #009688);
    background:transparent;
    box-shadow:none;
    border-radius:0;
    font-size:14px;
}
.theme-picker-form--compact .theme-palette--compact .theme-palette-preview{
    height:100%;
}
.theme-picker-form--compact .theme-palette--compact .theme-palette-preview-header{
    height:18px;
}
.theme-picker-form--compact .theme-palette--compact .theme-palette-preview-side,
.theme-picker-form--compact .theme-palette--compact .theme-palette-preview-side-alt{
    top:18px;
}
.theme-picker-form--compact .theme-palette--compact .theme-palette-preview-hero{
    top:28px;
    height:18px;
}
.theme-picker-form--compact .theme-palette--compact .theme-palette-preview-panel,
.theme-picker-form--compact .theme-palette--compact .theme-palette-preview-panel-right{
    top:54px;
    height:18px;
}
.theme-picker-form--compact .theme-palette--compact .theme-palette-preview-tone{
    top:26px;
}
.theme-picker-form--compact .theme-palette--compact .theme-palette-preview-copy-1{
    top:36px;
}
.theme-picker-form--compact .theme-palette--compact .theme-palette-preview-copy-2{
    top:43px;
}
.theme-picker-form--compact .theme-palette--compact .theme-palette-preview-copy-3,
.theme-picker-form--compact .theme-palette--compact .theme-palette-preview-copy-4{
    top:60px;
}
.theme-picker-form--compact .theme-palette--compact .theme-palette-preview.split .theme-palette-preview-header{
    left:0;
    background:var(--theme-header, var(--theme-side, #20222a));
}
.theme-picker-form--compact .theme-palette--compact .theme-palette-meta{
    left:0;
    right:0;
    bottom:0;
    padding:28px 8px 8px;
    position:absolute;
    z-index:2;
    gap:4px;
    justify-content:flex-end;
    pointer-events:none;
    background:linear-gradient(180deg, rgba(15, 23, 42, 0) 0%, rgba(15, 23, 42, 0.72) 58%, rgba(15, 23, 42, 0.86) 100%);
}
.theme-picker-form--compact .theme-palette--compact .theme-palette-title{
    padding-right:22px;
    font-size:11px;
    color:#fff;
    text-shadow:0 1px 2px rgba(15, 23, 42, 0.35);
}
.theme-picker-form--compact .theme-palette--compact .theme-palette-layout{
    max-width:calc(100% - 24px);
    padding:1px 5px;
    color:rgba(255, 255, 255, 0.88);
    border-color:rgba(255, 255, 255, 0.12);
    background:rgba(15, 23, 42, 0.18);
    backdrop-filter:blur(1px);
}
.theme-picker-form--compact .layui-btn{
    height:32px;
    padding:0 14px;
    font-size:12px;
    line-height:32px;
    border-radius:5px;
}
.theme-picker-form--compact .layui-btn + .layui-btn{
    margin-left:6px;
}
@media (max-width:900px){
    .theme-picker-form--compact .theme-palette--compact .theme-palette-card{
        width:calc((100% - 24px) / 4);
        flex-basis:calc((100% - 24px) / 4);
    }
}
@media (max-width:640px){
    .theme-picker-form--compact .theme-palette--compact .theme-palette-card{
        width:calc((100% - 16px) / 3);
        flex-basis:calc((100% - 16px) / 3);
    }
}
@media (max-width:480px){
    .theme-picker-form--compact{
        max-width:none;
    }
    .theme-picker-form--compact .theme-palette--compact .theme-palette-card{
        width:calc((100% - 8px) / 2);
        flex-basis:calc((100% - 8px) / 2);
    }
    .theme-picker-form--compact .layui-btn{
        padding:0 12px;
    }
}
</style>
STYLE;
    }

    private static function userThemeScript(): string
    {
        return <<<'SCRIPT'
const applyThemePreview = function (value) {
    var alls = '', prox = 'layui-layout-theme-', curt = prox + value;
    $.each(themeCatalog, function (key) {
        if (key !== value) alls += ' ' + prox + key;
    });
    (top.$ || $)('.layui-layout-body').removeClass($.trim(alls)).addClass(curt);
};

$('form#theme').off('click', '[data-theme-card]').on('click', '[data-theme-card]', function () {
    var input = $(this).find('input[name=site_theme]')[0];
    if (!input) return;
    if (!input.checked) {
        input.checked = true;
        $(input).trigger('change');
    }
});

$('form#theme').off('change', 'input[name=site_theme]').on('change', 'input[name=site_theme]', function () {
    var that = this;
    $('form#theme input[name=site_theme]').each(function () {
        $(this).closest('[data-theme-card]').toggleClass('active', this === that);
    });
    applyThemePreview(that.value);
});
SCRIPT;
    }

    private static function configThemeScript(): string
    {
        return <<<'SCRIPT'
var themeCommitted = false;
var themeOrigin = (function () {
    var className = String(((top.$ || $)('.layui-layout-body').attr('class') || ''));
    var matched = className.match(/layui-layout-theme-([A-Za-z0-9\-]+)/);
    if (matched && matched[1]) return matched[1];
    return $('form#theme input[name=site_theme]:checked').val() || 'default';
})();

var getSelectedTheme = function () {
    return $('form#theme input[name=site_theme]:checked').val() || '';
};

var getSelectedLabel = function () {
    var $card = $('form#theme input[name=site_theme]:checked').closest('[data-theme-card]');
    return $card.data('themeLabel') || $.trim($card.find('.theme-palette-title').text()) || getSelectedTheme();
};

var applyThemePreview = function (value) {
    var alls = '', prox = 'layui-layout-theme-', curt = prox + value;
    $.each(themeCatalog, function (key) {
        if (key !== value) alls += ' ' + prox + key;
    });
    (top.$ || $)('.layui-layout-body').removeClass($.trim(alls)).addClass(curt);
};

var restoreThemePreview = function () {
    if (themeOrigin) applyThemePreview(themeOrigin);
};

var syncThemePicker = function (value, label) {
    if (!themePicker) return;
    if (typeof top.window[themePicker] === 'function') {
        top.window[themePicker](value, label);
    }
};

var closeThemeDialog = function (element) {
    var index = $(element || 'form#theme').parents('div.layui-layer-page').attr('times');
    if (index && window.layer && typeof layer.close === 'function') {
        layer.close(index);
    }
};

var bindThemeLayerClose = function () {
    var index = $('form#theme').parents('div.layui-layer-page').attr('times');
    var $layer = index ? $('#layui-layer' + index) : $();
    if (!$layer.length || $layer.data('themeCloseBound')) return;
    $layer.data('themeCloseBound', '1').on('click.themePreview', '.layui-layer-setwin [class*=layui-layer-close]', function () {
        if (!themeCommitted) restoreThemePreview();
    });
};

$('form#theme').off('click', '[data-theme-card]').on('click', '[data-theme-card]', function () {
    var input = $(this).find('input[name=site_theme]')[0];
    if (!input) return;
    if (!input.checked) {
        input.checked = true;
        $(input).trigger('change');
    }
});

$('form#theme').off('change', 'input[name=site_theme]').on('change', 'input[name=site_theme]', function () {
    var that = this;
    $('form#theme input[name=site_theme]').each(function () {
        $(this).closest('[data-theme-card]').toggleClass('active', this === that);
    });
    applyThemePreview(that.value);
});

$('body').off('click', '[data-theme-confirm]').on('click', '[data-theme-confirm]', function () {
    var value = getSelectedTheme();
    if (!value) return;
    themeCommitted = true;
    syncThemePicker(value, getSelectedLabel());
    closeThemeDialog(this);
});

$('body').off('click', '[data-theme-cancel]').on('click', '[data-theme-cancel]', function () {
    restoreThemePreview();
    closeThemeDialog(this);
});

setTimeout(bindThemeLayerClose, 0);
SCRIPT;
    }
}
