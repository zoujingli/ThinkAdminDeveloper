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

        return FormBuilder::make('form', 'modal')
            ->define(function ($form) use ($themes, $theme, $picker, $configScene) {
                $form->title($configScene ? '选择后台默认配色' : '后台配色方案')
                    ->action(sysuri())
                    ->attr('id', 'theme')
                    ->class('theme-picker-form');

                FormModules::themePalette($form, $themes, $theme, [
                    'title' => '后台配色方案',
                    'subtitle' => 'Theme Style',
                    'description' => $configScene
                        ? '统一为“标准 / 品牌侧栏 / 双栏导航”三种后台布局语义，选择卡片后会实时预览，确认后再回填系统参数页。'
                        : '统一为“标准 / 品牌侧栏 / 双栏导航”三种后台布局语义，新增皮肤可直接预览后保存生效。',
                    'help' => $configScene
                        ? '选中卡片后会先切换后台预览；点击“确认选择”才会回填到系统参数表单，点击“取消选择”会恢复原主题。'
                        : '保存后立即写入当前账号主题；系统默认主题可在系统参数配置中设置，后续新用户登录也会继承。',
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
