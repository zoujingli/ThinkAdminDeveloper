<?php

declare(strict_types=1);

namespace plugin\system\builder;

use think\admin\builder\form\FormBuilder;
use think\admin\builder\form\module\FormModules;
use think\admin\builder\page\PageBuilder;
use think\admin\builder\page\PageNode;
use think\admin\builder\page\module\PageModules;

/**
 * 系统配置页面构建器。
 * @class ConfigBuilder
 */
class ConfigBuilder
{
    /**
     * @param array<string, mixed> $context
     */
    public static function buildIndexPage(array $context): PageBuilder
    {
        $site = is_array($context['site'] ?? null) ? $context['site'] : [];
        $runtime = is_array($context['runtime'] ?? null) ? $context['runtime'] : [];
        $storage = is_array($context['storage'] ?? null) ? $context['storage'] : [];
        $isSuper = !empty($context['issuper']);
        $isDebug = !empty($context['appDebug']);
        $storageEditable = !empty($context['storageEditable']);
        $storageDriver = strval($context['storageDriver'] ?? 'local');
        $storageName = strval($context['storageName'] ?? $storageDriver);
        $systemInfo = is_array($context['systemInfo'] ?? null) ? $context['systemInfo'] : [];
        $showSystemButton = !empty($context['canEditSystem']);

        return PageBuilder::make()
            ->define(function ($page) use ($site, $runtime, $storage, $isSuper, $isDebug, $storageEditable, $storageDriver, $storageName, $systemInfo, $showSystemButton) {
                $page->title('系统参数配置')->contentClass('');

                self::buildHeaderButtons($page, $isSuper, $showSystemButton);

                PageModules::card($page, ['title' => '系统概览', 'remark' => '统一管理运行模式、存储中心与系统基础参数', 'class' => 'layui-card mb15'], function (PageNode $body) use ($isDebug, $storageName, $runtime) {
                    PageModules::paragraphs($body, [
                        '当前后台使用 System 统一维护运行模式、富编辑器、文件存储与系统基础参数。',
                    ], ['class' => 'ta-desc mt0']);
                    PageModules::kvGrid($body, [
                        ['label' => '运行模式', 'value' => $isDebug ? '开发模式' : '生产模式'],
                        ['label' => '默认存储驱动', 'value' => $storageName],
                        ['label' => '默认编辑器', 'value' => strval($runtime['editor_driver'] ?? 'ckeditor5')],
                        ['label' => '站点名称', 'value' => strval($site['name'] ?? '-')],
                    ]);
                });

                if ($isSuper) {
                    self::buildSummaryPanels($page, [
                        'isDebug' => $isDebug,
                        'storageEditable' => $storageEditable,
                        'storageDriver' => $storageDriver,
                        'storageName' => $storageName,
                        'storage' => $storage,
                        'runtime' => $runtime,
                        'site' => $site,
                        'systemInfo' => $systemInfo,
                    ]);
                }
            })
            ->build();
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function buildStorageIndexPage(array $context): PageBuilder
    {
        $storage = is_array($context['storage'] ?? null) ? $context['storage'] : [];
        $files = is_array($context['files'] ?? null) ? $context['files'] : [];
        $driver = strval($context['driver'] ?? 'local');
        $driverName = strval($context['driverName'] ?? $driver);
        $canEdit = !empty($context['canEdit']);
        $allowedExtensionsText = strval($storage['allowed_extensions_text'] ?? '-');

        return PageBuilder::make()
            ->define(function ($page) use ($storage, $files, $driver, $driverName, $canEdit, $allowedExtensionsText) {
                $page->title('存储配置中心')->contentClass('')
                    ->buttons(function ($buttons) {
                        $buttons->open('文件管理', sysuri('system/file/index'))
                            ->open('返回系统配置', sysuri('system/config/index'));
                    });

                PageModules::card($page, ['title' => '存储概览', 'remark' => '统一管理文件上传、命名策略与外链输出', 'class' => 'layui-card mb15'], function (PageNode $body) use ($driverName, $storage, $files) {
                    PageModules::paragraphs($body, [
                        '系统所有上传驱动共享一套全局策略，切换默认驱动后，上传入口、命名方式和链接输出会同步生效。',
                    ], ['class' => 'ta-desc mt0']);
                    PageModules::kvGrid($body, [
                        ['label' => '当前默认驱动', 'value' => $driverName],
                        ['label' => '命名策略', 'value' => strval($storage['naming_rule'] ?? 'xmd5')],
                        ['label' => '链接策略', 'value' => strval($storage['link_mode'] ?? 'none')],
                        ['label' => '可用驱动数量', 'value' => strval(count($files))],
                    ]);
                });

                PageModules::card($page, ['title' => '存储引擎', 'remark' => '系统默认文件存储方式，可按驱动分别配置', 'class' => 'layui-card mb15'], function (PageNode $body) use ($files, $driver, $canEdit, $allowedExtensionsText) {
                    $grid = $body->div()->class('layui-row layui-col-space15');
                    foreach ($files as $code => $name) {
                        $col = $grid->div()->class('layui-col-xs12 layui-col-md4');
                        self::buildStorageDriverCard($col, strval($code), strval($name), $driver, $canEdit);
                    }
                    PageModules::paragraphs($body, [
                        '允许类型：' . $allowedExtensionsText,
                        '上传后的文件记录统一在 System 的文件管理中维护，可直接查看、重命名、删除或清理重复文件。',
                        '本地存储适合快速部署，自建网关适合统一收口，对象存储更适合公共资源、CDN 和大文件场景。',
                    ]);
                });
            })
            ->build();
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function buildSystemForm(array $context): FormBuilder
    {
        $site = is_array($context['site'] ?? null) ? $context['site'] : [];
        $security = is_array($context['security'] ?? null) ? $context['security'] : [];
        $runtime = is_array($context['runtime'] ?? null) ? $context['runtime'] : [];
        $pluginCenter = is_array($context['pluginCenter'] ?? null) ? $context['pluginCenter'] : [];
        $themes = is_array($context['themes'] ?? null) ? $context['themes'] : [];
        $siteThemeKey = strval($context['siteThemeKey'] ?? 'default');
        $siteThemeLabel = strval($context['siteThemeLabel'] ?? $siteThemeKey);
        $themePickerUrl = strval($context['themePickerUrl'] ?? '/system/index/theme');

        return FormBuilder::make('form', 'page')
            ->define(function ($form) use ($site, $security, $runtime, $pluginCenter, $themes, $siteThemeKey, $siteThemeLabel, $themePickerUrl) {
                $form->title('系统参数设置')
                    ->headerButton('返回配置首页', 'button', '', ['data-target-backup' => null], 'layui-btn-primary layui-btn-sm')
                    ->class('system-config-form')
                    ->data('auto', 'true')
                    ->action(sysuri());

                FormModules::intro($form, [
                    'title' => '统一管理登录入口、品牌信息与安全配置',
                    'description' => '当前页面用于维护后台默认主题、登录页资源、插件中心开关以及站点品牌字段。',
                ]);

                FormModules::section($form, [
                    'title' => '入口与主题',
                    'description' => '登录入口由插件注册前缀决定，这里负责维护登录页标题和后台默认主题。',
                ], function ($section) use ($site, $siteThemeKey, $siteThemeLabel) {
                    $grid = $section->div()->class('layui-row layui-col-space15');

                    $col = $grid->div()->class('layui-col-xs12 layui-col-md4');
                    $col->fields(function ($fields) use ($site) {
                        $fields->text('site[login_title]', '登录表单标题', 'Login Title', true, '登录页主标题会显示在登录表单上方。', null, [
                            'placeholder' => '请输入登录页面的表单标题',
                            'vali-name' => '登录标题',
                        ]);
                    });

                    $entry = $grid->div()->class('layui-col-xs12 layui-col-md4');
                    FormModules::readonlyField($entry, [
                        'title' => '后台登录入口',
                        'subtitle' => 'Login Entry',
                        'value' => substr(sysuri('system/index/index', [], false), strlen(sysuri('@'))),
                        'copy' => sysuri('system/index/index', [], false),
                        'help' => '如需修改入口路径，请调整 System 插件注册前缀。',
                    ]);

                    $theme = $grid->div()->class('layui-col-xs12 layui-col-md4');
                    FormModules::pickerField($theme, [
                        'title' => '后台默认配色',
                        'subtitle' => 'Theme Style',
                        'hidden_name' => 'site[theme]',
                        'hidden_value' => $siteThemeKey,
                        'value' => $siteThemeLabel,
                        'attrs' => ['data-open-site-theme' => null],
                        'input_attrs' => ['data-site-theme-text' => null],
                        'help' => '保存后会作为后台默认主题，用户个人主题仍可单独切换。',
                    ]);
                });

                FormModules::section($form, [
                    'title' => '插件中心',
                    'description' => '插件中心已经合并进 System，这里只保留启停和菜单展示策略两个全局开关。',
                ], function ($section) use ($pluginCenter) {
                    $section->fields(function ($fields) use ($pluginCenter) {
                        $fields->select('plugin_center[enabled]', '插件中心状态', 'Center Status', true, '禁用后将隐藏插件中心相关入口。', [
                            '1' => '启用',
                            '0' => '禁用',
                        ])->select('plugin_center[show_menu]', '菜单显示策略', 'Menu Display', true, '适合把插件能力作为内部功能收口时使用。', [
                            '1' => '显示菜单节点',
                            '0' => '隐藏菜单节点',
                        ]);
                    });
                });

                FormModules::section($form, [
                    'title' => '运行参数',
                    'description' => '维护默认编辑器与队列日志保留时长，避免运行参数散落在其它页面。',
                ], function ($section) use ($runtime) {
                    $section->fields(function ($fields) {
                        $fields->select('runtime[editor_driver]', '默认编辑器', 'Editor Driver', true, '用于后台富文本场景的默认编辑器。', [
                            'ckeditor4' => 'CKEditor 4',
                            'ckeditor5' => 'CKEditor 5',
                            'wangEditor' => 'wangEditor',
                            'auto' => '自动选择',
                        ])->text('runtime[queue_retain_days]', '队列保留天数', 'Queue Days', true, '队列日志的默认保留天数，最小值为 1。', null, [
                            'type' => 'number',
                            'min' => 1,
                            'vali-name' => '保留天数',
                            'placeholder' => '请输入队列日志保留天数',
                        ]);
                    });
                });

                FormModules::section($form, [
                    'title' => '安全与登录资源',
                    'description' => '统一维护 JWT 密钥、浏览器图标以及登录背景图片。',
                ], function ($section) {
                    $grid = $section->div()->class('layui-row layui-col-space15');

                    $col1 = $grid->div()->class('layui-col-xs12 layui-col-md6');
                    $col1->fields(function ($fields) {
                        $field = $fields->text('security[jwt_secret]', 'JWT 接口密钥', 'Jwt Key', true, '请输入 32 位 JWT 接口密钥。', '.{32}', [
                            'maxlength' => 32,
                            'vali-name' => '接口密钥',
                            'placeholder' => '请输入32位JWT接口密钥',
                        ]);
                        $field->input()->class('relative')->html('<a class="input-right-icon layui-icon layui-icon-refresh" id="RefreshJwtKey"></a>');
                    });

                    $col2 = $grid->div()->class('layui-col-xs12 layui-col-md6');
                    $col2->fields(function ($fields) {
                        $field = $fields->text('site[browser_icon]', '浏览器小图标', 'Browser Icon', true, '建议上传 128x128 或 256x256 的 JPG/PNG/JPEG 图片。', 'url', [
                            'vali-name' => '图标文件',
                            'placeholder' => '请上传浏览器图标',
                            'data-tips-image' => null,
                            'data-tips-hover' => null,
                        ]);
                        $field->input()->html('<a class="input-right-icon layui-icon layui-icon-upload-drag" data-file="btn" data-type="png,jpg,jpeg" data-field="site[browser_icon]"></a>');
                    });

                    $col3 = $grid->div()->class('layui-col-xs12');
                    $col3->fields(function ($fields) {
                        $fields->images('site[login_background_images]', '登录背景图片', 'Background Image', false);
                    });
                    FormModules::note($col3, '建议保持 2-4 张横向背景图，风格和当前后台主题尽量统一。');
                });

                FormModules::section($form, [
                    'title' => '站点品牌信息',
                    'description' => '这部分字段会出现在浏览器标题、后台品牌位和登录页底部。',
                ], function ($section) {
                    $section->fields(function ($fields) {
                        $fields->text('site[website_name]', '网站名称', 'Site Name', true, '网站名称将显示在浏览器标签页和页面标题中。', null, [
                            'placeholder' => '请输入网站名称',
                            'vali-name' => '网站名称',
                        ])->text('site[application_name]', '后台程序名称', 'App Name', true, '管理程序名称显示在后台左上标题处。', null, [
                            'placeholder' => '请输入程序名称',
                            'vali-name' => '程序名称',
                        ])->text('site[application_version]', '后台程序版本', 'App Version', false, '用于后台版本标识和发布后的版本核验。', null, [
                            'placeholder' => '请输入程序版本',
                        ])->text('site[public_security_filing]', '公安备案号', 'Beian', false, '', null, [
                            'placeholder' => '请输入公安备案号',
                        ])->text('site[miit_filing]', '网站备案号', 'Miitbeian', false, '', null, [
                            'placeholder' => '请输入网站备案号',
                        ])->text('site[copyright]', '网站版权信息', 'Copyright', true, '网站上线时建议同步补齐备案与版权信息。', null, [
                            'placeholder' => '请输入版权信息',
                            'vali-name' => '版权信息',
                        ]);
                    });
                });

                $form->actions(function ($actions) {
                    $actions->submit('保存配置')->cancel('取消修改', '确定要取消修改吗？');
                });

                $form->script(sprintf("const siteThemeCatalog=%s;", json_encode($themes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}'));
                $form->script(sprintf("const siteThemePickerUrl=%s;", json_encode($themePickerUrl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '"/system/index/theme"'));
                $form->script(<<<'SCRIPT'
const generateJwtKey = function () {
    if (window.crypto && typeof window.crypto.getRandomValues === 'function') {
        const bytes = new Uint8Array(16);
        window.crypto.getRandomValues(bytes);
        return Array.from(bytes, function (byte) {
            return byte.toString(16).padStart(2, '0');
        }).join('');
    }
    let chars = '0123456789abcdef', token = '';
    while (token.length < 32) token += chars[Math.floor(Math.random() * chars.length)];
    return token.slice(0, 32);
};
$('body').off('click', '#RefreshJwtKey').on('click', '#RefreshJwtKey', function () {
    $('[name="security[jwt_secret]"]').val(generateJwtKey());
});
const previewSiteTheme = function (value) {
    var alls = '', prox = 'layui-layout-theme-', curt = prox + value;
    $.each(siteThemeCatalog, function (key) {
        if (key !== value) alls += ' ' + prox + key;
    });
    (top.$ || $)('.layui-layout-body').removeClass($.trim(alls)).addClass(curt);
};
const updateSiteThemeField = function (value, label) {
    var meta = siteThemeCatalog[value] || {};
    var text = label || meta.label || value;
    $('input[name="site[theme]"]').val(value);
    $('[data-site-theme-text]').val(text).attr('title', text);
    previewSiteTheme(value);
};
$('body').off('click', '[data-open-site-theme]').on('click', '[data-open-site-theme]', function () {
    var value = $('input[name="site[theme]"]').val() || 'default';
    var frameIndex = top.layer && typeof top.layer.getFrameIndex === 'function' ? (top.layer.getFrameIndex(window.name) || 'page') : 'page';
    var pickerName = '__themeConfigPicker_' + String(frameIndex).replace(/[^\w]/g, '_');
    top.window[pickerName] = function (theme, label) {
        updateSiteThemeField(theme, label);
    };
    $.form.modal(siteThemePickerUrl + "?scene=config&picker=" + encodeURIComponent(pickerName) + "&value=" + encodeURIComponent(value), {}, '选择后台默认配色', undefined, undefined, undefined, '800px');
});
SCRIPT);
            })
            ->build();
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function buildStorageForm(array $context): FormBuilder
    {
        $type = strval($context['type'] ?? 'local');
        $driverName = strval($context['driverName'] ?? strtoupper($type));
        $points = is_array($context['points'] ?? null) ? $context['points'] : [];

        return FormBuilder::make('form', 'page')
            ->define(function ($form) use ($type, $driverName, $points) {
                $form->title($driverName . ' 存储配置')
                    ->headerButton('返回存储中心', 'button', '', ['data-target-backup' => null], 'layui-btn-primary layui-btn-sm')
                    ->class('storage-form')
                    ->data('auto', 'true')
                    ->action(sysuri());

                FormModules::intro($form, [
                    'title' => '统一维护上传策略与驱动参数',
                    'description' => self::storageFormSummary($type),
                ]);

                FormModules::section($form, [
                    'title' => '全局上传策略',
                    'description' => '下面三项规则会对所有存储驱动同时生效，建议优先确认。',
                ], function ($section) {
                    $section->fields(function ($fields) {
                        $fields->select('storage[naming_rule]', '命名方式', 'Naming Rule', true, '推荐使用文件哈希，可减少重复存储并支持秒传。', [
                            'xmd5' => '文件哈希（支持秒传）',
                            'date' => '日期随机（普通上传）',
                        ])->select('storage[link_mode]', '链接类型', 'Link Mode', true, '带压缩的模式仅在驱动已提供图片处理能力时生效。', [
                            'none' => '简洁链接',
                            'full' => '完整链接',
                            'none+compress' => '简洁链接 + 图片压缩',
                            'full+compress' => '完整链接 + 图片压缩',
                        ])->text('storage[allowed_extensions_text]', '允许类型', 'Allow Exts', true, '多个后缀请用英文逗号分隔，例如 png,jpg,pdf,zip。', null, [
                            'vali-name' => '文件后缀',
                            'placeholder' => '请输入系统允许上传的文件后缀',
                        ]);
                    });
                });

                FormModules::section($form, [
                    'title' => "{$driverName} 驱动参数",
                    'description' => self::storageDriverDescription($type),
                ], function ($section) use ($type, $points) {
                    $section->fields(function ($fields) use ($type, $points) {
                        foreach (self::storageDriverFields($type, $points) as $field) {
                            $name = strval($field['name'] ?? '');
                            $title = strval($field['title'] ?? '');
                            $subtitle = strval($field['subtitle'] ?? '');
                            $required = !empty($field['required']);
                            $remark = strval($field['remark'] ?? '');
                            $attrs = is_array($field['attrs'] ?? null) ? $field['attrs'] : [];
                            if (($field['type'] ?? 'text') === 'select') {
                                $fields->select($name, $title, $subtitle, $required, $remark, is_array($field['options'] ?? null) ? $field['options'] : [], '', $attrs);
                            } else {
                                $fields->text($name, $title, $subtitle, $required, $remark, null, $attrs);
                            }
                        }
                    });
                });

                $form->div()->html(sprintf('<input type="hidden" name="storage[default_driver]" value="%s">', htmlentities($type, ENT_QUOTES, 'UTF-8')));
                $form->actions(function ($actions) {
                    $actions->submit('保存配置')->cancel('取消修改', '确定要取消修改吗？');
                });
            })
            ->build();
    }

    private static function buildHeaderButtons(PageNode $page, bool $isSuper, bool $showSystemButton): void
    {
        $page->buttons(function ($buttons) use ($isSuper, $showSystemButton) {
            if ($isSuper) {
                $buttons->load('清理无效配置', apiuri('system/system/config'));
            }
            if ($showSystemButton) {
                $buttons->open('系统参数设置', url('system')->build());
            }
        });
    }

    /**
     * @param array<string, mixed> $context
     */
    private static function buildSummaryPanels(PageNode $page, array $context): void
    {
        $grid = $page->div()->class('layui-row layui-col-space15');
        $left = $grid->div()->class('layui-col-xs12 layui-col-md6');
        $right = $grid->div()->class('layui-col-xs12 layui-col-md6');

        self::buildRuntimeCard($left, $context);
        self::buildStorageCard($left, $context);
        if (!empty($context['isDebug'])) {
            self::buildSystemInfoCard($left, $context);
        }

        self::buildEditorCard($right, $context);
        self::buildSiteCard($right, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    private static function buildRuntimeCard(PageNode $parent, array $context): void
    {
        $isDebug = !empty($context['isDebug']);
        PageModules::card($parent, ['title' => '运行模式', 'class' => 'layui-card mb15'], function (PageNode $body) use ($isDebug) {
            PageModules::buttonGroup($body, $isDebug ? [
                ['label' => '开发模式', 'class' => 'layui-btn layui-btn-sm layui-btn-active'],
                ['label' => '生产模式', 'url' => apiuri('system/system/debug') . '?state=1', 'data_key' => 'data-load', 'class' => 'layui-btn layui-btn-sm layui-btn-primary', 'attrs' => ['data-confirm' => '确定要切换到生产模式吗？']],
            ] : [
                ['label' => '开发模式', 'url' => apiuri('system/system/debug') . '?state=0', 'data_key' => 'data-load', 'class' => 'layui-btn layui-btn-sm layui-btn-primary', 'attrs' => ['data-confirm' => '确定要切换到开发模式吗？']],
                ['label' => '生产模式', 'class' => 'layui-btn layui-btn-sm layui-btn-active'],
            ]);
            PageModules::paragraphs($body, [
                '开发模式：适合本地开发和联调，发生异常时会输出完整错误和调用上下文。',
                sprintf('生产模式：适合正式环境，异常时统一输出友好提示“%s”。', config('app.error_message')),
            ]);
        });
    }

    /**
     * @param array<string, mixed> $context
     */
    private static function buildStorageCard(PageNode $parent, array $context): void
    {
        $storage = is_array($context['storage'] ?? null) ? $context['storage'] : [];
        $storageName = strval($context['storageName'] ?? 'local');
        $storageDriver = strval($context['storageDriver'] ?? 'local');
        $storageEditable = !empty($context['storageEditable']);

        PageModules::card($parent, ['title' => '存储中心', 'class' => 'layui-card mb15'], function (PageNode $body) use ($storage, $storageName, $storageDriver, $storageEditable) {
            if ($storageEditable) {
                PageModules::buttonGroup($body, [
                    ['label' => '进入存储中心', 'url' => sysuri('system/config/storage'), 'data_key' => 'data-open', 'class' => 'layui-btn layui-btn-sm layui-btn-active'],
                    ['label' => "配置当前驱动", 'url' => sysuri('system/config/storage') . '?type=' . $storageDriver, 'data_key' => 'data-open', 'class' => 'layui-btn layui-btn-sm layui-btn-primary'],
                ]);
            } else {
                PageModules::buttonGroup($body, [
                    ['label' => "当前驱动：{$storageName}", 'class' => 'layui-btn layui-btn-sm layui-btn-active'],
                ]);
            }

            PageModules::kvGrid($body, [
                ['label' => '当前默认驱动', 'value' => $storageName],
                ['label' => '命名策略', 'value' => strval($storage['naming_rule'] ?? 'xmd5')],
                ['label' => '链接策略', 'value' => strval($storage['link_mode'] ?? 'none')],
                ['label' => '允许类型', 'value' => strval($storage['allowed_extensions_text'] ?? '-')],
            ]);
            PageModules::paragraphs($body, [
                '上传驱动、命名规则、外链输出与文件管理统一由 System 收口。',
            ]);
        });
    }

    /**
     * @param array<string, mixed> $context
     */
    private static function buildSystemInfoCard(PageNode $parent, array $context): void
    {
        $rows = [];
        foreach (is_array($context['systemInfo'] ?? null) ? $context['systemInfo'] : [] as $label => $item) {
            if (!is_array($item)) {
                continue;
            }
            $rows[] = [
                'label' => strval($label),
                'value' => strval($item['value'] ?? ''),
                'url' => strval($item['url'] ?? ''),
            ];
        }

        PageModules::card($parent, ['title' => '系统信息', 'class' => 'layui-card mb15'], function (PageNode $body) use ($rows) {
            PageModules::keyValueTable($body, $rows);
        });
    }

    /**
     * @param array<string, mixed> $context
     */
    private static function buildEditorCard(PageNode $parent, array $context): void
    {
        $runtime = is_array($context['runtime'] ?? null) ? $context['runtime'] : [];
        $driver = strval($runtime['editor_driver'] ?? 'ckeditor5');
        $items = [
            'ckeditor4' => 'CKEditor4',
            'ckeditor5' => 'CKEditor5',
            'wangEditor' => 'wangEditor',
            'auto' => '自动适配',
        ];

        PageModules::card($parent, ['title' => '富编辑器', 'class' => 'layui-card mb15'], function (PageNode $body) use ($driver, $items) {
            $buttons = [];
            foreach ($items as $key => $label) {
                if ($driver === $key) {
                    $buttons[] = ['label' => $label, 'class' => 'layui-btn layui-btn-sm layui-btn-active'];
                } else {
                    $buttons[] = [
                        'label' => $label,
                        'url' => apiuri('system/system/editor'),
                        'data_key' => 'data-action',
                        'class' => 'layui-btn layui-btn-sm layui-btn-primary',
                        'attrs' => ['data-value' => "editor#{$key}"],
                    ];
                }
            }
            PageModules::buttonGroup($body, $buttons);
            PageModules::paragraphs($body, [
                'CKEditor4：兼容性更稳，适合老页面或遗留内容迁移。',
                'CKEditor5：体验更现代，适合当前后台优先使用。',
                'wangEditor：交互更轻，适合轻量编辑场景。',
                '自动适配：优先使用新编辑器，在条件不满足时自动回退。',
            ]);
        });
    }

    /**
     * @param array<string, mixed> $context
     */
    private static function buildSiteCard(PageNode $parent, array $context): void
    {
        $site = is_array($context['site'] ?? null) ? $context['site'] : [];

        PageModules::card($parent, ['title' => '系统参数', 'class' => 'layui-card mb15'], function (PageNode $body) use ($site) {
            PageModules::readonlyFields($body, [
                [
                    'label' => '网站名称',
                    'meta' => 'Website',
                    'value' => strval($site['website_name'] ?? ''),
                    'copy' => strval($site['website_name'] ?? ''),
                    'help' => '显示在浏览器标签页和页面标题中。',
                ],
                [
                    'label' => '后台程序名称',
                    'meta' => 'Name',
                    'value' => strval($site['application_name'] ?? ''),
                    'copy' => strval($site['application_name'] ?? ''),
                    'help' => '显示在后台左上角品牌位和主框架标题处。',
                ],
                [
                    'label' => '后台程序版本',
                    'meta' => 'Version',
                    'value' => strval($site['application_version'] ?? ''),
                    'copy' => strval($site['application_version'] ?? ''),
                    'help' => '用于后台版本标识和发布后的版本核验展示。',
                ],
                [
                    'label' => '公安备案号',
                    'meta' => 'Beian',
                    'value' => strval($site['public_security_filing'] ?? '-'),
                    'copy' => strval($site['public_security_filing'] ?? ''),
                    'help' => '登录页底部可展示公安备案信息。',
                ],
                [
                    'label' => '网站备案号',
                    'meta' => 'Miitbeian',
                    'value' => strval($site['miit_filing'] ?? '-'),
                    'copy' => strval($site['miit_filing'] ?? ''),
                    'help' => '登录页底部可展示工信部备案信息。',
                ],
                [
                    'label' => '网站版权信息',
                    'meta' => 'Copyright',
                    'value' => strval($site['copyright'] ?? ''),
                    'copy' => strval($site['copyright'] ?? ''),
                    'help' => '显示在登录页底部，通常用于版权声明和品牌主体信息。',
                ],
            ]);
        });
    }

    private static function buildStorageDriverCard(PageNode $parent, string $code, string $name, string $driver, bool $canEdit): void
    {
        $active = $driver === $code;
        $cardClass = $active ? 'layui-card border-color-green' : 'layui-card';

        PageModules::card($parent, ['title' => $name, 'remark' => strtoupper($code), 'class' => $cardClass], function (PageNode $body) use ($code, $name, $canEdit, $active) {
            PageModules::kvGrid($body, [
                ['label' => '驱动状态', 'value' => $active ? '当前默认' : '可切换'],
                ['label' => '驱动标识', 'value' => $code],
                ['label' => '驱动类型', 'value' => self::storageTypeLabel($code)],
            ]);
            $body->div()->class('mt10 color-desc lh24')->text(self::storageDescription($code));
            PageModules::buttonGroup($body, [[
                'label' => $canEdit ? ($active ? '当前使用' : '进入配置') : '当前账号仅可查看存储策略',
                'url' => $canEdit ? (sysuri('system/config/storage') . '?type=' . $code) : '',
                'data_key' => $canEdit ? 'data-modal' : '',
                'class' => $active ? 'layui-btn layui-btn-sm layui-btn-active mt15' : 'layui-btn layui-btn-sm layui-btn-primary mt15',
                'attrs' => $canEdit ? ['data-title' => "配置{$name}", 'data-width' => '980px'] : [],
            ]], ['class' => '']);
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function storageDriverFields(string $type, array $points = []): array
    {
        $protocolOptions = match ($type) {
            'local' => [
                'follow' => 'FOLLOW',
                'http' => 'HTTP',
                'https' => 'HTTPS',
                'path' => 'PATH',
                'auto' => 'AUTO',
            ],
            default => [
                'http' => 'HTTP',
                'https' => 'HTTPS',
                'auto' => 'AUTO',
            ],
        };

        $prefix = "storage[drivers][{$type}]";
        $fields = [[
            'type' => 'select',
            'name' => "{$prefix}[protocol]",
            'title' => '访问协议',
            'subtitle' => 'Protocol',
            'required' => true,
            'remark' => self::storageProtocolHelp($type),
            'options' => $protocolOptions,
        ]];

        if ($points !== []) {
            $fields[] = [
                'type' => 'select',
                'name' => "{$prefix}[region]",
                'title' => '存储区域',
                'subtitle' => 'Region',
                'required' => true,
                'remark' => '必须与实际 Bucket 所在区域保持一致。',
                'options' => $points,
                'attrs' => ['lay-search' => null],
            ];
        }

        return array_merge($fields, match ($type) {
            'local' => [[
                'name' => "{$prefix}[domain]",
                'title' => '访问域名',
                'subtitle' => 'Domain',
                'required' => false,
                'remark' => '无需填写 http/https 协议，例如 static.example.com。留空时会自动推断。',
                'attrs' => ['placeholder' => '请输入上传后的访问域名（非必填）'],
            ]],
            'alist' => [
                [
                    'name' => "{$prefix}[domain]",
                    'title' => '访问域名',
                    'subtitle' => 'Domain',
                    'required' => true,
                    'remark' => '例如 storage.example.com，不需要携带协议头。',
                    'attrs' => ['vali-name' => '访问域名', 'placeholder' => '请输入 Alist 访问域名'],
                ],
                [
                    'name' => "{$prefix}[path]",
                    'title' => '存储目录',
                    'subtitle' => 'Directory',
                    'required' => true,
                    'remark' => '填写相对于当前 Alist 用户根目录的路径，/ 表示根目录。',
                    'attrs' => ['vali-name' => '存储目录', 'placeholder' => '请输入 Alist 目标目录'],
                ],
                [
                    'name' => "{$prefix}[username]",
                    'title' => '用户账号',
                    'subtitle' => 'Username',
                    'required' => true,
                    'remark' => '该账号需要具备目标目录的读取和写入权限。',
                    'attrs' => ['maxlength' => 100, 'vali-name' => '用户账号', 'placeholder' => '请输入 Alist 上传账号'],
                ],
                [
                    'name' => "{$prefix}[password]",
                    'title' => '用户密码',
                    'subtitle' => 'Password',
                    'required' => true,
                    'remark' => '用于换取上传令牌，填写错误时无法下发上传授权。',
                    'attrs' => ['maxlength' => 100, 'vali-name' => '用户密码', 'placeholder' => '请输入 Alist 上传密码'],
                ],
            ],
            'upyun' => [
                [
                    'name' => "{$prefix}[bucket]",
                    'title' => '空间名称',
                    'subtitle' => 'Bucket',
                    'required' => true,
                    'remark' => '空间名称通常需要全局唯一。',
                    'attrs' => ['vali-name' => '空间名称', 'placeholder' => '请输入又拍云 Bucket'],
                ],
                [
                    'name' => "{$prefix}[domain]",
                    'title' => '访问域名',
                    'subtitle' => 'Domain',
                    'required' => true,
                    'remark' => '例如 static.upyun.example.com，不需要协议头。',
                    'attrs' => ['vali-name' => '访问域名', 'placeholder' => '请输入又拍云外链域名'],
                ],
                [
                    'name' => "{$prefix}[username]",
                    'title' => '操作员账号',
                    'subtitle' => 'Operator',
                    'required' => true,
                    'remark' => '请为该操作员授予目标空间的读写权限。',
                    'attrs' => ['maxlength' => 100, 'vali-name' => '操作员账号', 'placeholder' => '请输入又拍云操作员账号'],
                ],
                [
                    'name' => "{$prefix}[password]",
                    'title' => '操作员密码',
                    'subtitle' => 'Password',
                    'required' => true,
                    'remark' => '用于生成上传授权，请妥善保管。',
                    'attrs' => ['maxlength' => 100, 'vali-name' => '操作员密码', 'placeholder' => '请输入又拍云操作员密码'],
                ],
            ],
            'alioss' => self::storageObjectFields($prefix, '阿里云', 'AccessKeyId', 'AccessKeySecret'),
            'qiniu' => self::storageObjectFields($prefix, '七牛云', 'AccessKey', 'SecretKey'),
            'txcos' => self::storageObjectFields($prefix, '腾讯云 COS', 'SecretId', 'SecretKey'),
            default => [],
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function storageObjectFields(string $prefix, string $vendor, string $accessLabel, string $secretLabel): array
    {
        return [
            [
                'name' => "{$prefix}[bucket]",
                'title' => '空间名称',
                'subtitle' => 'Bucket',
                'required' => true,
                'remark' => '必须与控制台中实际配置的 Bucket 名称保持一致。',
                'attrs' => ['vali-name' => '空间名称', 'placeholder' => "请输入{$vendor} Bucket"],
            ],
            [
                'name' => "{$prefix}[domain]",
                'title' => '访问域名',
                'subtitle' => 'Domain',
                'required' => true,
                'remark' => '请输入绑定后的外链域名，不需要携带协议头。',
                'attrs' => ['vali-name' => '访问域名', 'placeholder' => "请输入{$vendor}外链域名"],
            ],
            [
                'name' => "{$prefix}[access_key]",
                'title' => '访问密钥',
                'subtitle' => $accessLabel,
                'required' => true,
                'remark' => "请使用具备目标存储读写权限的 {$accessLabel}。",
                'attrs' => ['vali-name' => '访问密钥', 'placeholder' => "请输入{$vendor} {$accessLabel}"],
            ],
            [
                'name' => "{$prefix}[secret_key]",
                'title' => '安全密钥',
                'subtitle' => $secretLabel,
                'required' => true,
                'remark' => '请妥善保管，避免泄露到前端或公共仓库。',
                'attrs' => ['maxlength' => 100, 'vali-name' => '安全密钥', 'placeholder' => "请输入{$vendor} {$secretLabel}"],
            ],
        ];
    }

    private static function storageFormSummary(string $type): string
    {
        return match ($type) {
            'local' => '文件将保存在当前服务器目录，适合单机或轻量部署场景。',
            'alist' => '文件将上传到 Alist 统一存储网关，适合接入自建存储服务或多云盘整合。',
            'qiniu' => '文件将上传到七牛云对象存储，请确保 Bucket 已开放上传并配置好外链域名。',
            'upyun' => '文件将上传到又拍云 USS，请确保空间已开启公开访问并已配置 CORS。',
            'txcos' => '文件将上传到腾讯云 COS，请确保 Bucket 已配置公开读、私有写和浏览器跨域策略。',
            'alioss' => '文件将上传到阿里云 OSS，请确保 Bucket 已配置公开读、私有写和浏览器跨域策略。',
            default => '统一维护上传驱动、命名策略与外链输出参数。',
        };
    }

    private static function storageDriverDescription(string $type): string
    {
        return match ($type) {
            'local' => '用于控制本地上传后的访问协议和输出域名。',
            'alist' => '请填写访问入口、目标目录以及上传账号信息。',
            'qiniu' => '请确认存储区域、Bucket、外链域名和访问密钥与控制台配置一致。',
            'upyun' => '请填写空间名称、访问域名和操作员账号信息。',
            'txcos' => '请保持区域、Bucket、外链域名和云 API 密钥与实际配置一致。',
            'alioss' => '请确认区域、Bucket、外链域名和 AccessKey 与控制台配置一致。',
            default => '请按驱动要求填写对应配置参数。',
        };
    }

    private static function storageProtocolHelp(string $type): string
    {
        return match ($type) {
            'local' => 'FOLLOW 跟随当前请求协议，PATH 返回相对路径，AUTO 返回协议相对地址。',
            'alist' => 'AUTO 返回协议相对地址；如果开启 HTTPS，请确保入口已经正确部署证书。',
            'upyun' => 'AUTO 返回协议相对地址；如需 HTTPS，请先为绑定域名完成证书配置。',
            'qiniu' => 'AUTO 返回协议相对地址；启用 HTTPS 时请确认绑定域名已签发证书。',
            'txcos' => 'AUTO 返回协议相对地址；HTTPS 需要绑定域名证书。',
            'alioss' => 'AUTO 返回协议相对地址；HTTPS 依赖已绑定的证书和域名配置。',
            default => '请按实际驱动部署情况选择返回协议。',
        };
    }

    private static function storageDescription(string $code): string
    {
        return match ($code) {
            'local' => '文件直接保存到当前服务器目录，适合单机或轻量业务。',
            'alist' => '通过 Alist 统一存储网关落盘，适合自建存储集群或多云盘整合。',
            'qiniu' => '适合静态资源托管、CDN 分发和图片处理需求较多的场景。',
            'upyun' => '适合资源分发、浏览器直传和静态文件托管需求较强的业务。',
            'txcos' => '适合腾讯云生态或需要对象存储与 CDN 联动的部署方案。',
            'alioss' => '适合阿里云生态、多地域部署以及对象存储接入场景。',
            default => '可按业务需求维护驱动参数和默认策略。',
        };
    }

    private static function storageTypeLabel(string $code): string
    {
        return match ($code) {
            'local' => '本机磁盘',
            'alist' => '统一网关',
            'qiniu' => '对象存储',
            'upyun' => 'USS',
            'txcos' => 'COS',
            'alioss' => 'OSS',
            default => '-',
        };
    }
}
