<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdmin
 * +----------------------------------------------------------------------
 * | 版权所有 2014~2026 ThinkAdmin [ thinkadmin.top ]
 * +----------------------------------------------------------------------
 * | 官方网站: https://thinkadmin.top
 * +----------------------------------------------------------------------
 * | 开源协议 ( https://mit-license.org )
 * | 免责声明 ( https://thinkadmin.top/disclaimer )
 * | 会员特权 ( https://thinkadmin.top/vip-introduce )
 * +----------------------------------------------------------------------
 * | gitee 代码仓库：https://gitee.com/zoujingli/ThinkAdmin
 * | github 代码仓库：https://github.com/zoujingli/ThinkAdmin
 * +----------------------------------------------------------------------
 */

namespace think\admin\helper;

use think\admin\Controller;
use think\admin\Library;
use think\exception\HttpResponseException;

/**
 * 轻量表单视图构建器。
 * @class FormBuilder
 */
class FormBuilder
{
    /**
     * 前端别名规则映射到后端 _vali 规则.
     * @var array
     */
    private const PATTERN_RULES = [
        'qq' => 'regex:^[1-9][0-9]{4,11}$',
        'ip' => 'ip',
        'url' => 'url',
        'phone' => 'mobile',
        'mobile' => 'mobile',
        'email' => 'email',
        'wechat' => 'regex:^[a-zA-Z]([-_a-zA-Z0-9]{5,19})+$',
        'cardid' => 'idCard',
        'userame' => 'regex:^[a-zA-Z0-9_-]{4,16}$',
        'username' => 'regex:^[a-zA-Z0-9_-]{4,16}$',
    ];

    /**
     * 生成类型.
     * @var string
     */
    private $type;

    /**
     * 显示方式.
     * @var string
     */
    private $mode;

    /**
     * 当前控制器.
     * @var Controller
     */
    private $class;

    /**
     * 提交地址
     * @var string
     */
    private $action;

    /**
     * 表单变量.
     * @var string
     */
    private $variable = '$vo';

    /**
     * 表单项目 HTML.
     * @var array
     */
    private $fields = [];

    /**
     * 表单项目规则.
     * @var array
     */
    private $items = [];

    /**
     * 按钮 HTML.
     * @var array
     */
    private $buttons = [];

    /**
     * 按钮配置.
     * @var array
     */
    private $buttonItems = [];

    /**
     * 附加脚本.
     * @var array
     */
    private $scripts = [];

    /**
     * _vali 兼容规则.
     * @var array
     */
    private $rules = [];

    /**
     * 表单附加属性.
     * @var array
     */
    private $formAttrs = [];

    /**
     * Constructer.
     * @param string $type 页面类型
     * @param string $mode 页面模式
     */
    public function __construct(string $type, string $mode, Controller $class)
    {
        $this->type = $type;
        $this->mode = $mode;
        $this->class = $class;
    }

    /**
     * 创建表单生成器.
     * @param string $type 页面类型
     * @param string $mode 页面模式
     */
    public static function mk(string $type = 'form', string $mode = 'modal'): self
    {
        return Library::$sapp->invokeClass(static::class, ['type' => $type, 'mode' => $mode]);
    }

    /**
     * 设置表单地址
     * @return $this
     */
    public function setAction(string $url): self
    {
        $this->action = $url;
        return $this;
    }

    /**
     * 设置变量名称.
     * @return $this
     */
    public function setVariable(string $name): self
    {
        $name = trim($name);
        $this->variable = $name === '' ? '$vo' : ('$' . ltrim($name, '$'));
        return $this;
    }

    /**
     * 设置表单属性.
     * @return $this
     */
    public function setFormAttrs(array $attrs): self
    {
        $this->formAttrs = array_merge($this->formAttrs, $attrs);
        return $this;
    }

    /**
     * 添加页面脚本.
     * @return $this
     */
    public function addScript(string $script): self
    {
        $script = trim($script);
        if ($script !== '') {
            $this->scripts[] = $script;
        }
        return $this;
    }

    /**
     * 使用收集到的规则验证请求数据.
     * @param mixed $input
     */
    public function validate($input = '', ?callable $callable = null): array
    {
        return ValidateHelper::instance()->init($this->getRequestRules(), $input, $callable);
    }

    /**
     * 获取可直接用于 _vali 的请求规则.
     */
    public function getRequestRules(): array
    {
        $rules = [];
        foreach ($this->items as $field) {
            $rules[sprintf('%s.default', $field['name'])] = $this->resolveFieldDefault($field);
        }
        return array_merge($rules, $this->getValidateRules());
    }

    /**
     * 获取 _vali 兼容规则.
     */
    public function getValidateRules(): array
    {
        return $this->rules;
    }

    /**
     * 批量添加 _vali 验证规则.
     * @return $this
     */
    public function addValidateRules(array $rules): self
    {
        foreach ($rules as $key => $value) {
            if (is_string($key)) {
                $this->rules[$key] = $value;
            }
        }
        return $this;
    }

    /**
     * 动态添加多个字段.
     * @return $this
     */
    public function addFields(array $fields): self
    {
        foreach ($fields as $field) {
            is_array($field) && $this->addField($field);
        }
        return $this;
    }

    /**
     * 动态添加单个字段.
     * @return $this
     */
    public function addField(array $field): self
    {
        $field = $this->normalizeField($field);
        $this->collectField($field);
        $this->fields[] = $this->renderField($field);
        return $this;
    }

    /**
     * 添加单条 _vali 验证规则.
     * @return $this
     */
    public function addValidateRule(string $name, string $rule, string $message): self
    {
        $name = trim($name);
        $rule = trim($rule);
        if ($name !== '' && $rule !== '') {
            $this->rules["{$name}.{$rule}"] = $message;
        }
        return $this;
    }

    /**
     * 创建文本输入框架.
     * @param string $name 字段名称
     * @param string $title 字段标题
     * @param string $substr 字段子标题
     * @param mixed $remark
     * @param array $attrs 附加属性
     * @return $this
     */
    public function addTextArea(string $name, string $title, string $substr = '', bool $required = false, $remark = '', array $attrs = []): self
    {
        return $this->addField([
            'type' => 'textarea',
            'name' => $name,
            'title' => $title,
            'subtitle' => $substr,
            'required' => $required,
            'remark' => (string)$remark,
            'attrs' => $attrs,
        ]);
    }

    /**
     * 创建密钥输入框.
     * @param string $name 字段名称
     * @param string $title 字段标题
     * @param string $substr 字段子标题
     * @param string $remark 字段备注
     * @param bool $required 是否必填
     * @param ?string $pattern 验证规则
     * @param array $attrs 附加属性
     * @return $this
     */
    public function addPassInput(string $name, string $title, string $substr = '', bool $required = false, string $remark = '', ?string $pattern = null, array $attrs = []): self
    {
        $attrs['type'] = 'password';
        return $this->addTextInput($name, $title, $substr, $required, $remark, $pattern, $attrs);
    }

    /**
     * 创建 Text 输入.
     * @param string $name 字段名称
     * @param string $title 字段标题
     * @param string $substr 字段子标题
     * @param string $remark 字段备注
     * @param bool $required 是否必填
     * @param ?string $pattern 验证规则
     * @param array $attrs 附加属性
     * @return $this
     */
    public function addTextInput(string $name, string $title, string $substr = '', bool $required = false, string $remark = '', ?string $pattern = null, array $attrs = []): self
    {
        return $this->addField([
            'type' => $attrs['type'] ?? 'text',
            'name' => $name,
            'title' => $title,
            'subtitle' => $substr,
            'required' => $required,
            'remark' => $remark,
            'pattern' => $pattern,
            'attrs' => $attrs,
        ]);
    }

    /**
     * 添加取消按钮.
     * @param string $name 按钮名称
     * @param string $confirm 确认提示
     * @return $this
     */
    public function addCancelButton(string $name = '取消编辑', string $confirm = '确定要取消编辑吗？'): self
    {
        return $this->addButton($name, $confirm, 'button', 'layui-btn-danger', ['data-close' => null]);
    }

    /**
     * 添加提交按钮.
     * @param string $name 按钮名称
     * @param string $confirm 确认提示
     * @return $this
     */
    public function addSubmitButton(string $name = '保存数据', string $confirm = ''): self
    {
        return $this->addButton($name, $confirm, 'submit');
    }

    /**
     * 添加上传单图字段.
     * @param string $name 字段名称
     * @param string $title 字段标题
     * @param string $substr 字段子标题
     * @param bool $required 必填字段
     * @param array $attrs 附加属性
     * @return $this
     */
    public function addUploadOneImage(string $name, string $title, string $substr = '', bool $required = false, array $attrs = []): self
    {
        return $this->addField([
            'type' => 'image',
            'name' => $name,
            'title' => $title,
            'subtitle' => $substr,
            'required' => $required,
            'attrs' => $attrs,
        ]);
    }

    /**
     * 添加上传视频字段.
     * @param string $name 字段名称
     * @param string $title 字段标题
     * @param string $substr 字段子标题
     * @param bool $required 必填字段
     * @param array $attrs 附加属性
     * @return $this
     */
    public function addUploadOneVideo(string $name, string $title, string $substr = '', bool $required = false, array $attrs = []): self
    {
        return $this->addField([
            'type' => 'video',
            'name' => $name,
            'title' => $title,
            'subtitle' => $substr,
            'required' => $required,
            'attrs' => $attrs,
        ]);
    }

    /**
     * 创建上传多图字段.
     * @param string $name 字段名称
     * @param string $title 字段标题
     * @param string $substr 字段子标题
     * @param bool $required 必填字段
     * @param array $attrs 附加属性
     * @return $this
     */
    public function addUploadMulImage(string $name, string $title, string $substr = '', bool $required = false, array $attrs = []): self
    {
        return $this->addField([
            'type' => 'images',
            'name' => $name,
            'title' => $title,
            'subtitle' => $substr,
            'required' => $required,
            'attrs' => $attrs,
        ]);
    }

    /**
     * 添加单选框架字段.
     * @param string $name 字段名称
     * @param string $title 字段标题
     * @param string $substr 字段子标题
     * @param string $vname 变量名称
     * @param bool $required 是否必选
     * @param array $attrs 附加属性
     * @return $this
     */
    public function addRadioInput(string $name, string $title, string $substr, string $vname, bool $required = false, array $attrs = []): self
    {
        return $this->addCheckInput($name, $title, $substr, $vname, $required, $attrs, 'radio');
    }

    /**
     * 创建复选框字段.
     * @param string $name 字段名称
     * @param string $title 字段标题
     * @param string $substr 字段子标题
     * @param string $vname 变量名称
     * @param bool $required 是否必选
     * @param array $attrs 附加属性
     * @return $this
     */
    public function addCheckInput(string $name, string $title, string $substr, string $vname, bool $required = false, array $attrs = [], string $type = 'checkbox'): self
    {
        return $this->addField([
            'type' => $type,
            'name' => $name,
            'title' => $title,
            'subtitle' => $substr,
            'required' => $required,
            'attrs' => $attrs,
            'vname' => $vname,
        ]);
    }

    /**
     * 添加下拉选择字段.
     * @param string $name 字段名称
     * @param string $title 字段标题
     * @param string $substr 字段子标题
     * @param bool $required 是否必填
     * @param string $remark 字段备注
     * @param array $options 静态选项
     * @param string $vname 变量名称
     * @param array $attrs 附加属性
     * @return $this
     */
    public function addSelectInput(string $name, string $title, string $substr = '', bool $required = false, string $remark = '', array $options = [], string $vname = '', array $attrs = []): self
    {
        return $this->addField([
            'type' => 'select',
            'name' => $name,
            'title' => $title,
            'subtitle' => $substr,
            'required' => $required,
            'remark' => $remark,
            'options' => $options,
            'vname' => $vname,
            'attrs' => $attrs,
        ]);
    }

    /**
     * 显示模板内容.
     * @return mixed
     */
    public function fetch(array $vars = [])
    {
        $html = '';
        $type = "{$this->type}.{$this->mode}";
        if ($type === 'form.page') {
            $html = $this->_buildFormPage();
        } elseif ($type === 'form.modal') {
            $html = $this->_buildFormModal();
        }
        $vars['formBuilder'] = $vars['formBuilder'] ?? $this;
        $vars['formSchema'] = $vars['formSchema'] ?? $this->toArray();
        $vars['formRules'] = $vars['formRules'] ?? $this->getValidateRules();
        foreach ($this->class as $k => $v) {
            $vars[$k] = $v;
        }
        throw new HttpResponseException(display($html, $vars));
    }

    /**
     * 获取构建数据.
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'mode' => $this->mode,
            'action' => $this->action ?? '',
            'variable' => $this->variable,
            'fields' => $this->getFields(),
            'buttons' => $this->buttonItems,
            'rules' => $this->getValidateRules(),
        ];
    }

    /**
     * 获取字段规则配置.
     */
    public function getFields(): array
    {
        return array_values($this->items);
    }

    /**
     * 字段属性转换.
     */
    protected function _attrs(array $attrs, string $html = ''): string
    {
        foreach ($attrs as $k => $v) {
            $name = htmlspecialchars((string)$k, ENT_QUOTES, 'UTF-8');
            $html .= is_null($v) ? sprintf(' %s', $name) : sprintf(' %s="%s"', $name, htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'));
        }
        return $html;
    }

    /**
     * 添加表单按钮.
     * @param string $name 按钮名称
     * @param string $confirm 确认提示
     * @param string $type 按钮类型
     * @param string $class 按钮样式
     * @param array $attrs 附加属性
     * @return $this
     */
    protected function addButton(string $name, string $confirm, string $type, string $class = '', array $attrs = []): self
    {
        $attrs['type'] = $type;
        if ($confirm !== '') {
            $attrs['data-confirm'] = $confirm;
        }
        $attrs = $this->mergeClass($attrs, trim("layui-btn {$class}"));
        $this->buttons[] = sprintf('<button %s>%s</button>', $this->_attrs($attrs), $name);
        $this->buttonItems[] = [
            'name' => $name,
            'confirm' => $confirm,
            'type' => $type,
            'class' => trim("layui-btn {$class}"),
            'attrs' => $attrs,
        ];
        return $this;
    }

    /**
     * 兼容旧版受保护输入方法.
     * @param string $name 字段名称
     * @param string $title 字段标题
     * @param string $subtitle 字段子标题
     * @param string $remark 字段备注
     * @param array $attrs 附加属性
     * @return $this
     */
    protected function addInput(string $name, string $title, string $subtitle = '', string $remark = '', array $attrs = []): self
    {
        return $this->addField([
            'type' => $attrs['type'] ?? 'text',
            'name' => $name,
            'title' => $title,
            'subtitle' => $subtitle,
            'remark' => $remark,
            'required' => !empty($attrs['required']),
            'pattern' => $attrs['pattern'] ?? null,
            'attrs' => $attrs,
        ]);
    }

    /**
     * 解析字段默认值，确保可回填全部输入字段.
     */
    private function resolveFieldDefault(array $field)
    {
        return $field['type'] === 'checkbox' ? [] : '';
    }

    /**
     * 规范字段配置.
     */
    private function normalizeField(array $field): array
    {
        $field = array_merge([
            'type' => 'text',
            'name' => '',
            'title' => '',
            'subtitle' => '',
            'substr' => '',
            'remark' => '',
            'required' => false,
            'pattern' => null,
            'attrs' => [],
            'rules' => [],
            'vname' => '',
            'options' => [],
        ], $field);
        if ($field['subtitle'] === '' && $field['substr'] !== '') {
            $field['subtitle'] = (string)$field['substr'];
        }
        $field['type'] = $this->normalizeType((string)$field['type']);
        $field['name'] = trim((string)$field['name']);
        $field['title'] = trim((string)$field['title']);
        $field['subtitle'] = (string)$field['subtitle'];
        $field['remark'] = (string)$field['remark'];
        $field['required'] = !empty($field['required']);
        $field['pattern'] = $field['pattern'] === null || $field['pattern'] === '' ? null : (string)$field['pattern'];
        $field['attrs'] = is_array($field['attrs']) ? $field['attrs'] : [];
        $field['rules'] = is_array($field['rules']) ? $field['rules'] : [];
        $field['vname'] = trim((string)$field['vname']);
        $field['options'] = is_array($field['options']) ? $field['options'] : [];
        if ($field['name'] === '' || $field['title'] === '') {
            throw new \InvalidArgumentException('FormBuilder 字段 name 与 title 不能为空');
        }

        $attrs = $field['attrs'];
        if (!isset($attrs['vali-name']) && !isset($attrs['data-vali-name'])) {
            $attrs['vali-name'] = $field['title'];
        }
        if ($field['required']) {
            $attrs['required'] = 'required';
            $attrs['required-error'] = $attrs['required-error'] ?? sprintf('%s不能为空！', $field['title']);
            $this->addValidateRule($field['name'], 'require', (string)$attrs['required-error']);
        }
        if (is_string($field['pattern'])) {
            $attrs['pattern'] = $field['pattern'];
            $attrs['pattern-error'] = $attrs['pattern-error'] ?? sprintf('%s格式错误！', $field['title']);
            if ($rule = $this->resolvePatternRule($field['pattern'])) {
                $this->addValidateRule($field['name'], $rule, (string)$attrs['pattern-error']);
            }
        }
        foreach ($field['rules'] as $rule => $message) {
            if (is_string($rule) && $rule !== '') {
                $this->addValidateRule($field['name'], $rule, (string)$message);
            }
        }
        $field['attrs'] = $attrs;
        $field['validate'] = [
            'messages' => array_filter([
                'required' => $attrs['required-error'] ?? '',
                'pattern' => $attrs['pattern-error'] ?? '',
            ]),
            'portable' => array_filter($this->extractFieldRules($field['name'])),
        ];

        return $field;
    }

    /**
     * 规范组件类型.
     */
    private function normalizeType(string $type): string
    {
        $type = strtolower(trim($type));
        return match ($type) {
            '', 'input' => 'text',
            'pass' => 'password',
            'upload-image', 'upload-one-image' => 'image',
            'upload-video', 'upload-one-video' => 'video',
            'upload-images', 'upload-mul-image' => 'images',
            default => $type,
        };
    }

    /**
     * 解析 pattern 对应的后端规则.
     */
    private function resolvePatternRule(string $pattern): ?string
    {
        $pattern = trim($pattern);
        if ($pattern === '') {
            return null;
        }
        if (isset(self::PATTERN_RULES[$pattern])) {
            return self::PATTERN_RULES[$pattern];
        }
        if (str_contains($pattern, '|')) {
            return null;
        }

        $regex = preg_replace('~(?<!\\\)/~', '\/', $pattern) ?? $pattern;
        if (!str_starts_with($regex, '^')) {
            $regex = '^' . $regex;
        }
        if (!str_ends_with($regex, '$')) {
            $regex .= '$';
        }

        return "regex:/{$regex}/";
    }

    /**
     * 提取当前字段的 _vali 规则.
     */
    private function extractFieldRules(string $name): array
    {
        $rules = [];
        foreach ($this->rules as $rule => $message) {
            if (strpos($rule, "{$name}.") === 0) {
                $rules[$rule] = $message;
            }
        }
        return $rules;
    }

    /**
     * 收集字段配置.
     */
    private function collectField(array $field): void
    {
        $this->items[] = [
            'name' => $field['name'],
            'type' => $field['type'],
            'title' => $field['title'],
            'subtitle' => $field['subtitle'],
            'remark' => $field['remark'],
            'required' => $field['required'],
            'pattern' => $field['pattern'],
            'attrs' => $field['attrs'],
            'vname' => $field['vname'],
            'options' => $field['options'],
            'validate' => $field['validate'],
        ];
    }

    /**
     * 渲染字段 HTML.
     */
    private function renderField(array $field): string
    {
        if ($field['type'] === 'textarea') {
            return $this->renderTextArea($field);
        }
        if ($field['type'] === 'image') {
            return $this->renderUploadOne($field, 'image');
        }
        if ($field['type'] === 'video') {
            return $this->renderUploadOne($field, 'video');
        }
        if ($field['type'] === 'images') {
            return $this->renderUploadMultiple($field);
        }
        if ($field['type'] === 'select') {
            return $this->renderSelect($field);
        }
        if ($field['type'] === 'checkbox' || $field['type'] === 'radio') {
            return $this->renderCheckInput($field);
        }
        return $this->renderInput($field);
    }

    /**
     * 渲染文本域.
     */
    private function renderTextArea(array $field): string
    {
        $attrs = $this->mergeClass($field['attrs'], 'layui-textarea');
        $attrs['placeholder'] = $attrs['placeholder'] ?? "请输入{$field['title']}";
        $html = "\n\t\t" . sprintf('<label class="layui-form-item block relative" data-field-name="%s">', htmlspecialchars($field['name'], ENT_QUOTES, 'UTF-8'));
        $html .= "\n\t\t\t" . $this->renderLabel($field['title'], $field['subtitle'], !empty($attrs['required']));
        $html .= "\n\t\t\t" . sprintf('<textarea name="%s" %s>%s</textarea>', $field['name'], $this->_attrs($attrs), $this->valueExpression($field['name']));
        if ($field['remark'] !== '') {
            $html .= "\n\t\t\t" . sprintf('<span class="help-block">%s</span>', $field['remark']);
        }
        return "{$html}\n\t\t</label>";
    }

    /**
     * 合并样式类名.
     */
    private function mergeClass(array $attrs, string $class): array
    {
        $class = trim($class);
        if ($class === '') {
            return $attrs;
        }
        $attrs['class'] = trim(($attrs['class'] ?? '') . ' ' . $class);
        return $attrs;
    }

    /**
     * 渲染字段标题.
     */
    private function renderLabel(string $title, string $subtitle, bool $required): string
    {
        return sprintf('<span class="help-label %s"><b>%s</b>%s</span>', $required ? 'label-required-prev' : '', $title, $subtitle);
    }

    /**
     * 获取字段值表达式.
     */
    private function valueExpression(string $name): string
    {
        return sprintf('{%s.%s|default=\'\'}', $this->variable, $name);
    }

    /**
     * 渲染上传单图或单视频.
     */
    private function renderUploadOne(array $field, string $type): string
    {
        $attrs = $this->mergeClass($field['attrs'], 'layui-input layui-bg-gray');
        $attrs['type'] = 'text';
        $attrs['placeholder'] = $attrs['placeholder'] ?? "请上传{$field['title']}";
        $html = "\n\t\t" . sprintf('<div class="layui-form-item" data-field-name="%s">', htmlspecialchars($field['name'], ENT_QUOTES, 'UTF-8'));
        $html .= "\n\t\t\t" . $this->renderLabel($field['title'], $field['subtitle'], !empty($attrs['required']));
        $html .= "\n\t\t\t" . '<div class="relative block label-required-null">';
        $html .= "\n\t\t\t\t" . sprintf('<input name="%s" %s value="%s">', $field['name'], $this->_attrs($attrs), $this->valueExpression($field['name']));
        if ($type === 'image') {
            $html .= "\n\t\t\t\t" . sprintf('<a class="layui-icon layui-icon-upload input-right-icon" data-file="image" data-field="%s" data-type="gif,png,jpg,jpeg"></a>', $field['name']);
        } else {
            $html .= "\n\t\t\t\t" . sprintf('<a class="layui-icon layui-icon-upload input-right-icon" data-file data-field="%s" data-type="mp4"></a>', $field['name']);
        }
        $html .= "\n\t\t\t" . '</div>';
        if ($field['remark'] !== '') {
            $html .= "\n\t\t\t" . sprintf('<span class="help-block">%s</span>', $field['remark']);
        }
        $html .= "\n\t\t" . '</div>';
        if ($type === 'image') {
            $html .= "\n\t\t" . sprintf('<script>$("input[name=%s]").uploadOneImage()</script>', $field['name']);
        } else {
            $html .= "\n\t\t" . sprintf('<script>$("input[name=%s]").uploadOneVideo()</script>', $field['name']);
        }
        return $html;
    }

    /**
     * 渲染上传多图.
     */
    private function renderUploadMultiple(array $field): string
    {
        $attrs = $field['attrs'];
        $attrs['type'] = 'hidden';
        $attrs['placeholder'] = $attrs['placeholder'] ?? "请上传{$field['title']} ( 多图 )";
        $html = "\n\t\t" . sprintf('<div class="layui-form-item" data-field-name="%s">', htmlspecialchars($field['name'], ENT_QUOTES, 'UTF-8'));
        $html .= "\n\t\t\t" . $this->renderLabel($field['title'], $field['subtitle'], !empty($attrs['required']));
        $html .= "\n\t\t\t" . '<div class="layui-textarea help-images layui-bg-gray">';
        $html .= "\n\t\t\t\t" . sprintf('<input name="%s" %s value="%s">', $field['name'], $this->_attrs($attrs), $this->valueExpression($field['name']));
        $html .= "\n\t\t\t" . '</div>';
        if ($field['remark'] !== '') {
            $html .= "\n\t\t\t" . sprintf('<span class="help-block">%s</span>', $field['remark']);
        }
        $html .= "\n\t\t" . '</div>';
        $html .= "\n\t\t" . sprintf('<script>$("input[name=%s]").uploadMultipleImage()</script>', $field['name']);
        return $html;
    }

    /**
     * 渲染下拉选择.
     */
    private function renderSelect(array $field): string
    {
        $attrs = $this->mergeClass($field['attrs'], 'layui-select');
        $attrs['name'] = $field['name'];
        $html = "\n\t\t" . sprintf('<label class="layui-form-item block relative" data-field-name="%s">', htmlspecialchars($field['name'], ENT_QUOTES, 'UTF-8'));
        $html .= "\n\t\t\t" . $this->renderLabel($field['title'], $field['subtitle'], !empty($attrs['required']));
        $html .= "\n\t\t\t" . sprintf('<select %s>', $this->_attrs($attrs));
        $html .= "\n\t\t\t\t" . '<option value="">-- 请选择 --</option>';
        $html .= "\n\t\t\t\t" . $this->renderSelectOptions($field);
        $html .= "\n\t\t\t" . '</select>';
        if ($field['remark'] !== '') {
            $html .= "\n\t\t\t" . sprintf('<span class="help-block">%s</span>', $field['remark']);
        }
        return "{$html}\n\t\t</label>";
    }

    /**
     * 渲染下拉选项.
     */
    private function renderSelectOptions(array $field): string
    {
        if ($field['vname'] !== '') {
            $html = sprintf('{foreach $%s as $k=>$v}', $field['vname']);
            $html .= sprintf('{if isset(%s.%s) and strval(%s.%s) eq strval($k)}<option selected value="{$k|default=\'\'}">{$v|default=\'\'}</option>{else}<option value="{$k|default=\'\'}">{$v|default=\'\'}</option>{/if}', $this->variable, $field['name'], $this->variable, $field['name']);
            $html .= '{/foreach}';
            return $html;
        }

        $html = '';
        foreach ($field['options'] as $value => $label) {
            $value = htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
            $label = htmlspecialchars((string)$label, ENT_QUOTES, 'UTF-8');
            $html .= sprintf('{if isset(%s.%s) and strval(%s.%s) eq \'%s\'}<option selected value="%s">%s</option>{else}<option value="%s">%s</option>{/if}', $this->variable, $field['name'], $this->variable, $field['name'], addslashes($value), $value, $label, $value, $label);
        }
        return $html;
    }

    /**
     * 渲染单选或复选.
     */
    private function renderCheckInput(array $field): string
    {
        if ($field['vname'] === '' && count($field['options']) < 1) {
            throw new \InvalidArgumentException('FormBuilder 复选或单选字段需要提供 vname 或 options');
        }
        $type = $field['type'];
        $attrs = $field['attrs'];
        $attrs['type'] = $type;
        $attrs['lay-ignore'] = null;
        $attrs['name'] = $field['name'] . ($type === 'checkbox' ? '[]' : '');
        $html = "\n\t\t" . sprintf('<div class="layui-form-item" data-field-name="%s">', htmlspecialchars($field['name'], ENT_QUOTES, 'UTF-8'));
        $html .= "\n\t\t\t" . $this->renderLabel($field['title'], $field['subtitle'], !empty($attrs['required']));
        $html .= "\n\t\t\t" . '<div class="layui-textarea help-checks layui-bg-gray">';
        if ($field['vname'] !== '') {
            $html .= "\n\t\t\t\t" . sprintf('<!--{foreach $%s as $k=>$v}item-->', $field['vname']);
            $html .= "\n\t\t\t\t" . sprintf('<label class="think-%s label-required-null">', $type);
            $html .= "\n\t\t\t\t\t" . $this->renderCheckCondition($field['name'], $type);
            $html .= "\n\t\t\t\t\t" . sprintf('<input value="{$k|default=\'\'}" %s checked> {$v|default=\'\'}', $this->_attrs($attrs));
            $html .= "\n\t\t\t\t\t" . '<!--{else}else-->';
            $html .= "\n\t\t\t\t\t" . sprintf('<input value="{$k|default=\'\'}" %s> {$v|default=\'\'}', $this->_attrs($attrs));
            $html .= "\n\t\t\t\t\t" . '<!--{/if}if-->';
            $html .= "\n\t\t\t\t" . '</label>';
            $html .= "\n\t\t\t\t" . '<!--{/foreach}end-->';
        } else {
            foreach ($field['options'] as $value => $label) {
                $html .= "\n\t\t\t\t" . sprintf('<label class="think-%s label-required-null">', $type);
                $html .= "\n\t\t\t\t\t" . $this->renderCheckStaticCondition($field['name'], $type, (string)$value);
                $html .= "\n\t\t\t\t\t" . sprintf('<input value="%s" %s checked> %s', htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'), $this->_attrs($attrs), htmlspecialchars((string)$label, ENT_QUOTES, 'UTF-8'));
                $html .= "\n\t\t\t\t\t" . '<!--{else}else-->';
                $html .= "\n\t\t\t\t\t" . sprintf('<input value="%s" %s> %s', htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'), $this->_attrs($attrs), htmlspecialchars((string)$label, ENT_QUOTES, 'UTF-8'));
                $html .= "\n\t\t\t\t\t" . '<!--{/if}if-->';
                $html .= "\n\t\t\t\t" . '</label>';
            }
        }
        $html .= "\n\t\t\t" . '</div>';
        if ($field['remark'] !== '') {
            $html .= "\n\t\t\t" . sprintf('<span class="help-block">%s</span>', $field['remark']);
        }
        return $html . "\n\t\t</div>";
    }

    /**
     * 渲染单选或复选选中条件.
     */
    private function renderCheckCondition(string $name, string $type): string
    {
        $variable = $this->variable;
        if ($type === 'checkbox') {
            return sprintf('<!--if{if isset(%s.%s) and is_array(%s.%s) and in_array($k,%s.%s)}-->', $variable, $name, $variable, $name, $variable, $name);
        }
        return sprintf('<!--if{if isset(%s.%s) and strval($k)==strval(%s.%s)}-->', $variable, $name, $variable, $name);
    }

    /**
     * 渲染静态单选或复选选中条件.
     */
    private function renderCheckStaticCondition(string $name, string $type, string $value): string
    {
        $variable = $this->variable;
        $value = addslashes($value);
        if ($type === 'checkbox') {
            return sprintf('<!--if{if isset(%s.%s) and is_array(%s.%s) and in_array(\'%s\',%s.%s)}-->', $variable, $name, $variable, $name, $value, $variable, $name);
        }
        return sprintf('<!--if{if isset(%s.%s) and strval(\'%s\')==strval(%s.%s)}-->', $variable, $name, $value, $variable, $name);
    }

    /**
     * 渲染普通输入框.
     */
    private function renderInput(array $field): string
    {
        $attrs = $this->mergeClass($field['attrs'], 'layui-input');
        if ($field['type'] !== 'text' && !isset($attrs['type'])) {
            $attrs['type'] = $field['type'];
        }
        $attrs['placeholder'] = $attrs['placeholder'] ?? "请输入{$field['title']}";
        $html = "\n\t\t" . sprintf('<label class="layui-form-item block relative" data-field-name="%s">', htmlspecialchars($field['name'], ENT_QUOTES, 'UTF-8'));
        $html .= "\n\t\t\t" . $this->renderLabel($field['title'], $field['subtitle'], !empty($attrs['required']));
        $html .= "\n\t\t\t" . sprintf('<input name="%s" %s value="%s">', $field['name'], $this->_attrs($attrs), $this->valueExpression($field['name']));
        if ($field['remark'] !== '') {
            $html .= "\n\t\t\t" . sprintf('<span class="help-block">%s</span>', $field['remark']);
        }
        return "{$html}\n\t\t</label>";
    }

    /**
     * 生成页面表单模板
     */
    private function _buildFormPage(): string
    {
        return $this->_buildFormModal();
    }

    /**
     * 生成弹层表单模板
     */
    private function _buildFormModal(): string
    {
        $attrs = array_merge([
            'action' => $this->resolveAction(),
            'method' => 'post',
            'data-auto' => 'true',
        ], $this->formAttrs);
        $attrs = $this->mergeClass($attrs, 'layui-form layui-card');
        $html = sprintf('<form %s>', $this->_attrs($attrs));
        $html .= "\n\t" . '<div class="layui-card-body padding-left-40">' . join("\n", $this->fields);
        if (count($this->buttons)) {
            $html .= "\n\n\t\t" . '<div class="hr-line-dashed"></div>';
            $html .= "\n\t\t" . sprintf('{notempty name="%s.id"}<input type="hidden" value="{%s.id}" name="id">{/notempty}', $this->variableName(), $this->variable);
            $html .= "\n\t\t" . sprintf('<div class="layui-form-item text-center">%s</div>', "\n\t\t\t" . join("\n\t\t\t", $this->buttons) . "\n\t\t");
        }
        $html .= "\n\t\t" . $this->renderSchemaScript();
        $html .= "\n\t" . '</div>';
        return $html . "\n</form>" . $this->renderScripts();
    }

    /**
     * 获取提交地址.
     */
    private function resolveAction(): string
    {
        return $this->action ?? url()->build();
    }

    /**
     * 获取表单变量名称.
     */
    private function variableName(): string
    {
        return ltrim($this->variable, '$');
    }

    /**
     * 渲染表单结构 JSON.
     */
    private function renderSchemaScript(): string
    {
        $json = json_encode($this->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
        return $json ? sprintf('<script type="application/json" class="form-builder-schema">%s</script>', $json) : '';
    }

    /**
     * 渲染附加脚本.
     */
    private function renderScripts(): string
    {
        if (count($this->scripts) < 1) {
            return '';
        }

        $html = '';
        foreach ($this->scripts as $script) {
            $html .= "\n<script>\n{$script}\n</script>";
        }

        return $html;
    }
}
