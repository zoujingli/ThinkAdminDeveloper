<?php

declare(strict_types=1);

namespace think\admin\builder\page;

use think\admin\builder\base\render\BuilderAttributesRenderer;

/**
 * 页面列表列配置归一器.
 * @class PageColumnNormalizer
 */
class PageColumnNormalizer
{
    private BuilderAttributesRenderer $attributesRenderer;

    public function __construct(
        private string $tableId = 'PageDataTable',
        private string $toolbarId = 'toolbar',
        private $jsEncoder = null,
        ?BuilderAttributesRenderer $attributesRenderer = null,
    ) {
        $this->attributesRenderer = $attributesRenderer ?? new BuilderAttributesRenderer();
        $this->jsEncoder = is_callable($jsEncoder) ? $jsEncoder : static fn(mixed $value): string => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: 'null';
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function sortInput(string $actionUrl = '{:sysuri()}', array $options = []): array
    {
        $templateId = trim(strval($options['templateId'] ?? $this->buildTemplateId('SortInput')));
        $dataValue = strval($options['dataValue'] ?? 'id#{{d.id}};action#sort;sort#{value}');
        $inputValue = strval($options['inputValue'] ?? '{{d.sort}}');
        $inputAttrs = is_array($options['inputAttrs'] ?? null) ? $options['inputAttrs'] : [];
        unset($options['templateId'], $options['dataValue'], $options['inputValue'], $options['inputAttrs']);

        $column = array_merge([
            'field' => 'sort',
            'title' => '排序权重',
            'width' => 100,
            'align' => 'center',
            'sort' => true,
            'templet' => "#{$templateId}",
        ], $options);

        $attrs = array_merge([
            'type' => 'number',
            'min' => '0',
            'data-blur-number' => '0',
            'data-action-blur' => $actionUrl,
            'data-value' => $dataValue,
            'data-loading' => 'false',
            'value' => $inputValue,
            'class' => 'layui-input text-center',
        ], $inputAttrs);

        return [
            'column' => $column,
            'templates' => [$templateId => '<input ' . $this->attributesRenderer->render($attrs) . '>'],
            'scripts' => [],
        ];
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function toolbar(string $title = '操作面板', array $options = []): array
    {
        return [
            'column' => array_merge([
                'toolbar' => "#{$this->toolbarId}",
                'title' => $title,
                'align' => 'center',
                'minWidth' => 150,
                'fixed' => 'right',
            ], $options),
            'templates' => [],
            'scripts' => [],
        ];
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function statusSwitch(string $actionUrl, array $options = []): array
    {
        $templateId = trim(strval($options['templateId'] ?? $this->buildTemplateId('StatusSwitch')));
        $filter = trim(strval($options['filter'] ?? preg_replace('/Tpl$/', '', $templateId)));
        $auth = trim(strval($options['auth'] ?? 'state'));
        $valueExpr = strval($options['value'] ?? '{{d.id}}');
        $checkedExpr = strval($options['checked'] ?? "{{-d.status>0?'checked':''}}");
        $toggleText = strval($options['text'] ?? '已激活|已禁用');
        $activeHtml = strval($options['activeHtml'] ?? '<b class="color-green">已激活</b>');
        $inactiveHtml = strval($options['inactiveHtml'] ?? '<b class="color-red">已禁用</b>');
        $dataScript = trim(strval($options['dataScript'] ?? 'var data = {id: obj.value, status: obj.elem.checked > 0 ? 1 : 0};'));
        $reloadSelector = strval($options['reloadSelector'] ?? "#{$this->tableId}");
        $reloadOnError = !array_key_exists('reloadOnError', $options) || intval($options['reloadOnError']) > 0;
        $reloadOnSuccess = intval($options['reloadOnSuccess'] ?? 0) > 0;
        unset(
            $options['templateId'],
            $options['filter'],
            $options['auth'],
            $options['value'],
            $options['checked'],
            $options['text'],
            $options['activeHtml'],
            $options['inactiveHtml'],
            $options['dataScript'],
            $options['reloadSelector'],
            $options['reloadOnError'],
            $options['reloadOnSuccess']
        );

        $column = array_merge([
            'field' => 'status',
            'title' => '使用状态',
            'align' => 'center',
            'minWidth' => 110,
            'templet' => "#{$templateId}",
        ], $options);

        $attrs = [
            'type' => 'checkbox',
            'value' => $valueExpr,
            'lay-skin' => 'switch',
            'lay-text' => $toggleText,
            'lay-filter' => $filter,
        ];
        $toggle = '<input ' . $this->attributesRenderer->render($attrs) . ' ' . $checkedExpr . '>';
        $activeLiteral = $this->encodeJs($activeHtml);
        $inactiveLiteral = $this->encodeJs($inactiveHtml);
        $template = sprintf(
            '<!--{if auth("%s")}-->%s<!--{else}-->{{-d.status ? %s : %s}}<!--{/if}-->',
            addslashes($auth),
            $toggle,
            $activeLiteral,
            $inactiveLiteral
        );

        $reloadScript = sprintf('$(%s).trigger("reload");', $this->encodeJs($reloadSelector));
        $errorScript = $reloadOnError
            ? sprintf('$.msg.error(ret.info, 3, function () { %s });', $reloadScript)
            : '$.msg.error(ret.info);';
        $successScript = $reloadOnSuccess ? "        else {\n            {$reloadScript}\n        }\n" : '';
        $script = <<<SCRIPT
layui.form.on('switch({$filter})', function (obj) {
    {$dataScript}
    $.form.load({$this->encodeJs($actionUrl)}, data, 'post', function (ret) {
        if (ret.code < 1) {
            {$errorScript}
        }
{$successScript}        return false;
    }, false);
});
SCRIPT;

        return [
            'column' => $column,
            'templates' => [$templateId => $template],
            'scripts' => [$script],
        ];
    }

    private function buildTemplateId(string $prefix): string
    {
        $tableId = preg_replace('/[^\w]+/', '', $this->tableId);
        return trim($prefix . ($tableId ?: 'Table') . 'Tpl');
    }

    private function encodeJs(mixed $value): string
    {
        return ($this->jsEncoder)($value);
    }
}
