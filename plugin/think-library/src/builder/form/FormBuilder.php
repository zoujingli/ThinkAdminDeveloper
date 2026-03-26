<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdminDeveloper
 * +----------------------------------------------------------------------
 * | Copyright (c) 2014~2026 ThinkAdmin [ thinkadmin.top ]
 * +----------------------------------------------------------------------
 * | Official Website: https://thinkadmin.top
 * +----------------------------------------------------------------------
 * | Licensed: https://mit-license.org
 * | Disclaimer: https://thinkadmin.top/disclaimer
 * | Vip Rights: https://thinkadmin.top/vip-introduce
 * +----------------------------------------------------------------------
 * | Gitee Repository: https://gitee.com/zoujingli/ThinkAdmin
 * | Github Repository: https://github.com/zoujingli/ThinkAdmin
 * +----------------------------------------------------------------------
 */

namespace think\admin\builder\form;

use think\admin\builder\base\BuilderAttributeBag;
use think\admin\builder\base\BuilderModule;
use think\admin\builder\base\render\BuilderAttributes;
use think\admin\builder\base\render\BuilderAttributesRenderer;
use think\admin\builder\form\render\FormNodeRenderContext;
use think\admin\builder\form\render\FormNodeRendererFactory;
use think\admin\builder\form\render\FormRenderPipeline;
use think\admin\builder\form\render\FormRenderState;
use think\admin\Controller;
use think\admin\Exception;
use think\admin\Library;
use think\admin\service\AppService;
use think\admin\helper\ValidateHelper;
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
     */
    private string $type;

    /**
     * 显示方式.
     */
    private string $mode;

    /**
     * 当前控制器.
     */
    private Controller $class;

    /**
     * 提交地址
     */
    private string $action;

    /**
     * 表单变量.
     */
    private string $variable = '$vo';

    /**
     * 表单标题.
     */
    private string $title = '';

    /**
     * 表单项目 HTML.
     */
    private array $fields = [];

    /**
     * 表单内容节点.
     */
    private array $contentNodes = [];

    /**
     * 表单项目规则.
     */
    private array $items = [];

    /**
     * 按钮 HTML.
     */
    private array $buttons = [];

    /**
     * 按钮配置.
     */
    private array $buttonItems = [];

    /**
     * 标题栏按钮 HTML.
     */
    private array $headerButtons = [];

    /**
     * 标题栏按钮配置.
     */
    private array $headerButtonItems = [];

    /**
     * 附加脚本.
     */
    private array $scripts = [];

    /**
     * 手动附加的 _vali 兼容规则.
     */
    private array $rules = [];

    /**
     * 表单附加属性.
     */
    private array $formAttrs = [];

    /**
     * 表单主体附加属性.
     */
    private array $bodyAttrs = [];

    /**
     * 表单模块配置.
     */
    private array $formModules = [];

    /**
     * 当前布局根节点.
     */
    private ?FormLayout $layout = null;

    /**
     * 当前节点渲染上下文.
     */
    private ?FormRenderState $renderState = null;

    /**
     * 构造函数.
     *
     * @param string $type 页面类型 (form/add/edit 等)
     * @param string $mode 页面模式 (modal/default 等)
     * @param Controller $class 控制器实例
     */
    public function __construct(string $type, string $mode, Controller $class)
    {
        $this->type = $type;
        $this->mode = $mode;
        $this->class = $class;
    }

    /**
     * 创建表单生成器实例.
     *
     * @param string $type 页面类型 (form=add/edit 等)
     * @param string $mode 页面模式 (modal=弹窗，default=默认)
     */
    public static function make(string $type = 'form', string $mode = 'modal'): self
    {
        return Library::$sapp->invokeClass(static::class, ['type' => $type, 'mode' => $mode]);
    }

    /**
     * 定义表单结构.
     * @param callable(FormLayout): void $callback
     * @return $this
     */
    public function define(callable $callback): self
    {
        $layout = new FormLayout($this);
        $this->layout = $layout;
        $callback($layout);
        $this->contentNodes = array_merge($this->contentNodes, $layout->exportChildren());
        return $this;
    }

    /**
     * 完成表单构建.
     * @return $this
     */
    public function build(): self
    {
        return $this;
    }

    /**
     * 设置表单提交地址
     *
     * @param string $url 提交地址
     * @return $this
     */
    public function setAction(string $url): self
    {
        $this->action = $url;
        return $this;
    }

    /**
     * 设置表单变量名称.
     *
     * @param string $name 变量名称 (如 'vo', 'data' 等)
     * @return $this
     */
    public function setVariable(string $name): self
    {
        $name = trim($name);
        $this->variable = $name === '' ? '$vo' : ('$' . ltrim($name, '$'));
        return $this;
    }

    /**
     * 设置表单标题.
     */
    public function setTitle(string $title): self
    {
        $this->title = trim($title);
        return $this;
    }

    /**
     * 设置表单属性.
     *
     * @param array $attrs 表单属性数组
     * @return $this
     */
    public function setFormAttrs(array $attrs): self
    {
        $merged = [];
        foreach ($attrs as $name => $value) {
            $name = is_string($name) ? trim($name) : '';
            if ($name !== '') {
                $merged[$name] = $value;
            }
        }
        $this->formAttrs = BuilderAttributes::make($this->formAttrs)->merge($merged)->all();
        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getFormAttrs(): array
    {
        return $this->formAttrs;
    }

    public function createFormAttributes(): BuilderAttributeBag
    {
        return new BuilderAttributeBag($this->layout, $this->formAttrs);
    }

    public function createBodyAttributes(): BuilderAttributeBag
    {
        return new BuilderAttributeBag($this->layout, $this->bodyAttrs);
    }

    public function attachFormAttributes(BuilderAttributeBag $attributes): BuilderAttributeBag
    {
        return $attributes->attach(fn(array $state): array => $this->replaceFormAttributes($state));
    }

    public function attachBodyAttributes(BuilderAttributeBag $attributes): BuilderAttributeBag
    {
        return $attributes->attach(fn(array $state): array => $this->replaceBodyAttributes($state));
    }

    /**
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    public function replaceFormAttributes(array $state): array
    {
        $this->formAttrs = is_array($state['attrs'] ?? null) ? BuilderAttributes::make($state['attrs'])->all() : [];
        return ['attrs' => $this->formAttrs];
    }

    /**
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    public function replaceBodyAttributes(array $state): array
    {
        $this->bodyAttrs = is_array($state['attrs'] ?? null) ? BuilderAttributes::make($state['attrs'])->all() : [];
        return ['attrs' => $this->bodyAttrs];
    }

    /**
     * 设置表单属性.
     *
     * @param string $name 属性名称
     * @param mixed $value 属性值
     * @return $this
     */
    public function setFormAttr(string $name, mixed $value = null): self
    {
        $name = trim($name);
        if ($name === '') {
            return $this;
        }
        if ($name === 'class') {
            $this->formAttrs = BuilderAttributes::make($this->formAttrs)->class(is_array($value) ? $value : strval($value))->all();
            return $this;
        }
        $this->formAttrs = BuilderAttributes::make($this->formAttrs)->merge([$name => $value])->all();
        return $this;
    }

    /**
     * 添加表单样式类.
     *
     * @param string|array $class 样式类
     * @return $this
     */
    public function addFormClass(string|array $class): self
    {
        $this->formAttrs = BuilderAttributes::make($this->formAttrs)->class($class)->all();
        return $this;
    }

    /**
     * 设置表单主体属性.
     *
     * @param array $attrs 表单主体属性数组
     * @return $this
     */
    public function setBodyAttrs(array $attrs): self
    {
        $merged = [];
        foreach ($attrs as $name => $value) {
            $name = is_string($name) ? trim($name) : '';
            if ($name !== '') {
                $merged[$name] = $value;
            }
        }
        $this->bodyAttrs = BuilderAttributes::make($this->bodyAttrs)->merge($merged)->all();
        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getBodyAttrs(): array
    {
        return $this->bodyAttrs;
    }

    /**
     * 设置表单主体属性.
     *
     * @param string $name 属性名称
     * @param mixed $value 属性值
     * @return $this
     */
    public function setBodyAttr(string $name, mixed $value = null): self
    {
        $name = trim($name);
        if ($name === '') {
            return $this;
        }
        if ($name === 'class') {
            $this->bodyAttrs = BuilderAttributes::make($this->bodyAttrs)->class(is_array($value) ? $value : strval($value))->all();
            return $this;
        }
        $this->bodyAttrs = BuilderAttributes::make($this->bodyAttrs)->merge([$name => $value])->all();
        return $this;
    }

    /**
     * 添加表单主体样式类.
     *
     * @param string|array $class 样式类
     * @return $this
     */
    public function addBodyClass(string|array $class): self
    {
        $this->bodyAttrs = BuilderAttributes::make($this->bodyAttrs)->class($class)->all();
        return $this;
    }

    /**
     * 设置表单主体 data 属性.
     *
     * @param string $name 属性名称
     * @param mixed $value 属性值
     * @return $this
     */
    public function setBodyData(string $name, mixed $value = null): self
    {
        $name = trim($name);
        if ($name !== '') {
            $this->setBodyAttr('data-' . ltrim($name, '-'), $value);
        }
        return $this;
    }

    /**
     * 设置表单 data 属性.
     *
     * @param string $name 属性名称
     * @param mixed $value 属性值
     * @return $this
     */
    public function setFormData(string $name, mixed $value = null): self
    {
        $name = trim($name);
        if ($name !== '') {
            $this->setFormAttr('data-' . ltrim($name, '-'), $value);
        }
        return $this;
    }

    /**
     * 添加表单模块.
     *
     * @param string $name 模块名称
     * @param array $config 模块配置
     * @return $this
     */
    public function addFormModule(string $name, array $config = []): self
    {
        $this->attachFormModule($this->createFormModule($name, $config));
        return $this;
    }

    public function createFormModule(string $name, array $config = []): BuilderModule
    {
        return new BuilderModule($name, $config, $this->layout);
    }

    public function attachFormModule(BuilderModule $module): BuilderModule
    {
        $normalized = $this->normalizeFormModule($module->export());
        if ($normalized['name'] === '') {
            return $module;
        }
        $index = count($this->formModules);
        $this->formModules[$index] = $normalized;
        return $module->attach($index, $normalized, fn(int $index, array $module): array => $this->replaceFormModule($index, $module));
    }

    /**
     * @param array<string, mixed> $module
     * @return array<string, mixed>
     */
    public function replaceFormModule(int $index, array $module): array
    {
        $normalized = $this->normalizeFormModule($module);
        if ($normalized['name'] !== '') {
            $this->formModules[$index] = $normalized;
        }
        return $this->formModules[$index] ?? $normalized;
    }

    /**
     * 添加页面脚本.
     *
     * @param string $script JavaScript 脚本代码
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
     *
     * @param array|string $input 输入数据 (默认为空，自动从请求获取)
     * @param null|callable $callable 自定义验证回调
     * @return array 验证后的数据
     * @throws Exception
     */
    public function validate(array|string $input = '', ?callable $callable = null): array
    {
        return ValidateHelper::instance()->init($this->getRequestRules(), $input, $callable);
    }

    /**
     * 获取可直接用于 _vali 的请求规则.
     *
     * @return array 验证规则数组
     */
    public function getRequestRules(): array
    {
        $rules = [];
        foreach ($this->getFields() as $field) {
            $rules[sprintf('%s.default', $field['name'])] = $this->resolveFieldDefault($field);
        }
        return array_merge($rules, $this->getValidateRules());
    }

    /**
     * 获取 _vali 兼容规则.
     */
    public function getValidateRules(): array
    {
        $rules = [];
        foreach ($this->getFields() as $field) {
            foreach ($this->buildFieldRules($field) as $rule => $message) {
                $rules[$rule] = $message;
            }
        }
        return array_merge($rules, $this->rules);
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
     * 向指定节点追加字段.
     * @param array<string, mixed> $field
     */
    public function addFieldToNode(FormNode $parent, array $field): FormField
    {
        $field = $this->normalizeField($field);
        $this->collectField($field);
        $this->fields[] = $this->renderField($field);
        return $parent->append($this->createFieldNode($parent, $field));
    }

    /**
     * 创建字段节点对象.
     * @param array<string, mixed> $field
     */
    private function createFieldNode(FormNode $parent, array $field): FormField
    {
        return match ($field['type']) {
            'text', 'password', 'textarea' => new FormTextField($this, $parent, $field),
            'select' => new FormSelectField($this, $parent, $field),
            'checkbox', 'radio' => new FormChoiceField($this, $parent, $field),
            'image', 'video', 'images' => new FormUploadField($this, $parent, $field),
            default => new FormField($this, $parent, $field),
        };
    }

    /**
     * @param array<string, mixed> $module
     * @return array<string, mixed>
     */
    private function normalizeFormModule(array $module): array
    {
        return [
            'name' => trim(strval($module['name'] ?? '')),
            'config' => is_array($module['config'] ?? null) ? $module['config'] : [],
        ];
    }

    /**
     * 动态添加单个字段.
     * @return $this
     */
    public function addField(array $field): self
    {
        if ($this->layout instanceof FormLayout) {
            $this->addFieldToNode($this->layout, $field);
            return $this;
        }
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
    public function addCancelButton(string $name = '取消编辑', string $confirm = '确定要取消编辑吗？', array $attrs = [], string $class = 'layui-btn-danger'): self
    {
        return $this->addButton($name, $confirm, 'button', $class, array_merge($this->buildCancelAttrs(), $attrs));
    }

    /**
     * 向指定动作条追加取消按钮.
     */
    public function addCancelButtonToNode(FormActionBar $parent, string $name = '取消编辑', string $confirm = '确定要取消编辑吗？', array $attrs = [], string $class = 'layui-btn-danger'): self
    {
        return $this->addButtonToNode($parent, $name, $confirm, 'button', $class, array_merge($this->buildCancelAttrs(), $attrs));
    }

    /**
     * 添加提交按钮.
     * @param string $name 按钮名称
     * @param string $confirm 确认提示
     * @return $this
     */
    public function addSubmitButton(string $name = '保存数据', string $confirm = '', array $attrs = [], string $class = ''): self
    {
        return $this->addButton($name, $confirm, 'submit', $class, $attrs);
    }

    /**
     * 向指定动作条追加提交按钮.
     */
    public function addSubmitButtonToNode(FormActionBar $parent, string $name = '保存数据', string $confirm = '', array $attrs = [], string $class = ''): self
    {
        return $this->addButtonToNode($parent, $name, $confirm, 'submit', $class, $attrs);
    }

    /**
     * 添加通用动作按钮.
     * @return $this
     */
    public function addActionButton(string $name, string $type = 'button', string $confirm = '', array $attrs = [], string $class = ''): self
    {
        return $this->addButton($name, $confirm, $type, $class, $attrs);
    }

    /**
     * 向指定动作条追加通用按钮.
     */
    public function addActionButtonToNode(FormActionBar $parent, string $name, string $type = 'button', string $confirm = '', array $attrs = [], string $class = ''): self
    {
        return $this->addButtonToNode($parent, $name, $confirm, $type, $class, $attrs);
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
        $vars['staticRoot'] = strval($vars['staticRoot'] ?? AppService::uri('static'));
        foreach (get_object_vars($this->class) as $k => $v) {
            $vars[$k] = $v;
        }
        $html = $this->renderRuntimeTemplate($html, $vars);
        throw new HttpResponseException(display($html, $vars));
    }

    /**
     * 添加按钮 HTML.
     * @return $this
     */
    public function addButtonHtml(string $html, array $schema = []): self
    {
        $this->buttons[] = $html;
        $this->buttonItems[] = array_merge(['type' => 'html', 'html' => $html], $schema);
        if ($this->layout instanceof FormLayout) {
            $bar = $this->layout->actionBar();
            $bar->append(new FormButton($this, array_merge(['type' => 'html', 'html' => $html], $schema), $html));
        }
        return $this;
    }

    /**
     * 添加标题栏按钮 HTML.
     */
    public function addHeaderButtonHtml(string $html, array $schema = []): self
    {
        $this->headerButtons[] = $html;
        $this->headerButtonItems[] = array_merge(['type' => 'html', 'html' => $html], $schema);
        return $this;
    }

    /**
     * 向指定动作条追加按钮 HTML.
     */
    public function addButtonHtmlToNode(FormActionBar $parent, string $html, array $schema = []): self
    {
        $button = array_merge(['type' => 'html', 'html' => $html], $schema);
        $this->buttons[] = $html;
        $this->buttonItems[] = $button;
        $parent->append(new FormButton($this, $button, $html));
        return $this;
    }

    /**
     * 获取构建数据.
     */
    public function toArray(): array
    {
        $content = $this->layout instanceof FormLayout ? $this->layout->exportChildren() : $this->contentNodes;
        return [
            'type' => $this->type,
            'mode' => $this->mode,
            'title' => $this->title,
            'action' => $this->action ?? '',
            'variable' => $this->variable,
            'attrs' => $this->buildFormAttrs(false),
            'body_attrs' => $this->buildBodyAttrs(),
            'modules' => $this->formModules,
            'content' => $content,
            'fields' => $this->getFields(),
            'buttons' => $this->buttonItems,
            'header_buttons' => $this->headerButtonItems,
            'rules' => $this->getValidateRules(),
        ];
    }

    /**
     * 获取字段规则配置.
     */
    public function getFields(): array
    {
        if ($this->layout instanceof FormLayout) {
            return $this->extractFieldsFromNodes($this->layout->exportChildren());
        }
        return array_values($this->items);
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
        $renderer = new BuilderAttributesRenderer();
        $attrs['type'] = $type;
        if ($confirm !== '') {
            $attrs['data-confirm'] = $confirm;
        }
        $attrs = BuilderAttributes::make($attrs)->class(trim("layui-btn {$class}"))->all();
        $html = sprintf('<button %s>%s</button>', $renderer->render($attrs), $name);
        $button = [
            'name' => $name,
            'confirm' => $confirm,
            'type' => $type,
            'class' => trim("layui-btn {$class}"),
            'attrs' => $attrs,
        ];
        $this->buttons[] = $html;
        $this->buttonItems[] = $button;
        if ($this->layout instanceof FormLayout) {
            $bar = $this->layout->actionBar();
            $bar->append(new FormButton($this, $button, $html));
        }
        return $this;
    }

    /**
     * 添加标题栏按钮.
     */
    public function addHeaderButton(string $name, string $type = 'button', string $confirm = '', array $attrs = [], string $class = ''): self
    {
        $renderer = new BuilderAttributesRenderer();
        $attrs['type'] = $type;
        if ($confirm !== '') {
            $attrs['data-confirm'] = $confirm;
        }
        $attrs = BuilderAttributes::make($attrs)->class(trim("layui-btn {$class}"))->all();
        $html = sprintf('<button %s>%s</button>', $renderer->render($attrs), $name);
        $button = [
            'name' => $name,
            'confirm' => $confirm,
            'type' => $type,
            'class' => trim("layui-btn {$class}"),
            'attrs' => $attrs,
        ];
        $this->headerButtons[] = $html;
        $this->headerButtonItems[] = $button;
        return $this;
    }

    /**
     * 向指定动作条追加按钮.
     */
    protected function addButtonToNode(FormActionBar $parent, string $name, string $confirm, string $type, string $class = '', array $attrs = []): self
    {
        $renderer = new BuilderAttributesRenderer();
        $attrs['type'] = $type;
        if ($confirm !== '') {
            $attrs['data-confirm'] = $confirm;
        }
        $attrs = BuilderAttributes::make($attrs)->class(trim("layui-btn {$class}"))->all();
        $html = sprintf('<button %s>%s</button>', $renderer->render($attrs), $name);
        $button = [
            'name' => $name,
            'confirm' => $confirm,
            'type' => $type,
            'class' => trim("layui-btn {$class}"),
            'attrs' => $attrs,
        ];
        $this->buttons[] = $html;
        $this->buttonItems[] = $button;
        $parent->append(new FormButton($this, $button, $html));
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
    private function resolveFieldDefault(array $field): array|string
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
            'upload' => [],
            'parts' => [],
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
        $field['upload'] = is_array($field['upload']) ? $field['upload'] : [];
        $field['parts'] = is_array($field['parts']) ? $field['parts'] : [];
        if ($field['name'] === '' || $field['title'] === '') {
            throw new \InvalidArgumentException('FormBuilder 字段 name 与 title 不能为空');
        }

        $field['attrs'] = $this->normalizeFieldAttrs($field);
        $field['validate'] = [
            'messages' => array_filter([
                'required' => $field['attrs']['required-error'] ?? '',
                'pattern' => $field['attrs']['pattern-error'] ?? '',
            ]),
            'portable' => array_filter($this->buildFieldPortableRules($field)),
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
    private function normalizeFieldAttrs(array $field): array
    {
        $attrs = is_array($field['attrs'] ?? null) ? $field['attrs'] : [];
        if (!isset($attrs['vali-name']) && !isset($attrs['data-vali-name'])) {
            $attrs['vali-name'] = $field['title'];
        }
        if (!empty($field['required'])) {
            $attrs['required'] = 'required';
            $attrs['required-error'] = $attrs['required-error'] ?? sprintf('%s不能为空！', $field['title']);
        } else {
            unset($attrs['required']);
            unset($attrs['required-error']);
        }
        if (is_string($field['pattern'])) {
            $attrs['pattern'] = $field['pattern'];
            $attrs['pattern-error'] = $attrs['pattern-error'] ?? sprintf('%s格式错误！', $field['title']);
        } else {
            unset($attrs['pattern']);
            unset($attrs['pattern-error']);
        }
        return $attrs;
    }

    /**
     * 构建字段 _vali 规则.
     * @param array<string, mixed> $field
     * @return array<string, string>
     */
    private function buildFieldRules(array $field): array
    {
        $field = $this->normalizeField($field);
        return $this->buildFieldPortableRules($field);
    }

    /**
     * 根据已规范化字段构建 _vali 规则.
     * @param array<string, mixed> $field
     * @return array<string, string>
     */
    private function buildFieldPortableRules(array $field): array
    {
        $rules = [];
        if (!empty($field['required'])) {
            $rules["{$field['name']}.require"] = strval($field['attrs']['required-error'] ?? sprintf('%s不能为空！', $field['title']));
        }
        if (is_string($field['pattern']) && ($rule = $this->resolvePatternRule($field['pattern']))) {
            $rules["{$field['name']}.{$rule}"] = strval($field['attrs']['pattern-error'] ?? sprintf('%s格式错误！', $field['title']));
        }
        foreach ($field['rules'] as $rule => $message) {
            if (is_string($rule) && $rule !== '') {
                $rules["{$field['name']}.{$rule}"] = strval($message);
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
            'upload' => $field['upload'],
            'parts' => $field['parts'],
            'validate' => $field['validate'],
        ];
    }

    /**
     * 渲染字段 HTML.
     */
    private function renderField(array $field): string
    {
        $field = $this->normalizeField($field);
        return $this->renderPipeline()->renderField($field, $this->variable);
    }

    /**
     * 从内容节点提取字段数组.
     * @param array<int, array<string, mixed>> $nodes
     * @return array<int, array<string, mixed>>
     */
    private function extractFieldsFromNodes(array $nodes): array
    {
        $fields = [];
        foreach ($nodes as $node) {
            if (!is_array($node)) {
                continue;
            }
            if (($node['type'] ?? '') === 'field' && is_array($node['field'] ?? null)) {
                $field = $node['field'];
                if (is_array($node['attrs'] ?? null) && count($node['attrs']) > 0) {
                    $field['container_attrs'] = $node['attrs'];
                }
                if (is_array($node['modules'] ?? null) && count($node['modules']) > 0) {
                    $field['container_modules'] = $node['modules'];
                }
                if (is_array($node['parts'] ?? null) && count($node['parts']) > 0) {
                    $field['parts'] = $node['parts'];
                }
                $fields[] = $this->normalizeField($field);
                continue;
            }
            if (is_array($node['children'] ?? null)) {
                $fields = array_merge($fields, $this->extractFieldsFromNodes($node['children']));
            }
        }
        return $fields;
    }

    /**
     * 生成页面表单模板
     */
    private function _buildFormPage(): string
    {
        return $this->buildFormShell();
    }

    /**
     * 生成弹层表单模板
     */
    private function _buildFormModal(): string
    {
        return $this->buildFormShell();
    }

    /**
     * 渲染表单主体结构.
     */
    private function buildFormShell(): string
    {
        $content = $this->layout instanceof FormLayout ? $this->layout->exportChildren() : $this->contentNodes;
        $this->renderState = $this->createRenderState($this->toArray());
        try {
            return $this->renderPipeline()->renderShell(
                $this->buildFormAttrs(),
                $this->buildBodyAttrs(),
                $content,
                $this->fields,
                $this->headerButtons,
                $this->buttons,
                $this->renderState,
                $this->scripts
            );
        } finally {
            $this->renderState = null;
        }
    }

    /**
     * 渲染内容节点.
     * @param array<int, array<string, mixed>> $nodes
     */
    private function renderContentNodes(array $nodes): string
    {
        return $this->renderPipeline()->renderContentNodes($nodes, $this->currentRenderState());
    }

    /**
     * 获取提交地址.
     */
    private function resolveAction(): string
    {
        if (isset($this->action) && $this->action !== '') {
            return $this->action;
        }
        try {
            return url()->build();
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * 构建表单根节点属性.
     */
    private function buildFormAttrs(bool $resolveAction = true): array
    {
        $class = $this->mode === 'page' ? 'layui-form' : 'layui-form layui-card';
        return BuilderAttributes::make([
            'action' => $resolveAction ? $this->resolveAction() : ($this->action ?? ''),
            'method' => 'post',
            'data-auto' => 'true',
            'data-builder-scope' => 'form',
        ])->merge($this->formAttrs)
            ->modules($this->formModules)
            ->class($class)
            ->all();
    }

    /**
     * 构建表单主体属性.
     */
    private function buildBodyAttrs(): array
    {
        $class = $this->mode === 'page' ? 'pa40' : 'layui-card-body pa40';
        return BuilderAttributes::make($this->bodyAttrs)
            ->class($class)
            ->all();
    }

    /**
     * 构建取消动作属性.
     * @return array<string, null>
     */
    private function buildCancelAttrs(): array
    {
        if ($this->mode === 'page') {
            return ['data-target-backup' => null];
        }

        return ['data-close' => null];
    }

    /**
     * @param array<string, mixed> $schema
     */
    private function createRenderState(array $schema): FormRenderState
    {
        $attrsRenderer = new BuilderAttributesRenderer();
        return new FormRenderState(
            $schema,
            new FormNodeRendererFactory(),
            new FormNodeRenderContext(
                $this->variable,
                fn(array $nodes): string => $this->renderContentNodes($nodes),
                [$attrsRenderer, 'render'],
                fn(array $field): string => $this->renderField($field)
            )
        );
    }

    private function currentRenderState(): FormRenderState
    {
        return $this->renderState ?? $this->createRenderState($this->toArray());
    }

    private function renderPipeline(): FormRenderPipeline
    {
        return new FormRenderPipeline();
    }

    /**
     * 解析 Builder 运行时模板变量，避免依赖外层视图二次编译。
     * @param array<string, mixed> $vars
     */
    private function renderRuntimeTemplate(string $html, array $vars): string
    {
        $html = $this->renderForeachBlocks($html, $vars);
        $html = $this->renderConditionBlocks($html, $vars);
        $html = $this->renderJoinedValueExpressions($html, $vars);
        $html = $this->renderVariableExpressions($html, $vars);
        return $this->renderPlainExpressions($html, $vars);
    }

    /**
     * @param array<string, mixed> $vars
     */
    private function renderForeachBlocks(string $html, array $vars): string
    {
        $patterns = [
            '/\{foreach\s+\$(\w+)\s+as\s+\$(\w+)=\>\$(\w+)\}(.*?)\{\/foreach\}/s',
            '/<!--\{foreach\s+\$(\w+)\s+as\s+\$(\w+)=\>\$(\w+)\}item-->(.*?)<!--\{\/foreach\}end-->/s',
        ];

        foreach ($patterns as $pattern) {
            while (preg_match($pattern, $html)) {
                $html = preg_replace_callback($pattern, function (array $match) use ($vars): string {
                    $items = $this->resolveTemplateValue($vars, $match[1]);
                    if (!is_iterable($items)) {
                        return '';
                    }

                    $result = '';
                    foreach ($items as $key => $value) {
                        $local = $vars;
                        $local[$match[2]] = $key;
                        $local[$match[3]] = $value;
                        $result .= $this->renderRuntimeTemplate($match[4], $local);
                    }
                    return $result;
                }, $html) ?? $html;
            }
        }

        return $html;
    }

    /**
     * @param array<string, mixed> $vars
     */
    private function renderConditionBlocks(string $html, array $vars): string
    {
        $patterns = [
            '/\{notempty\s+name="([^"]+)"\}(.*?)\{\/notempty\}/s' => fn(array $m) => $this->isNotEmptyValue($this->resolveTemplateValue($vars, $m[1])) ? $this->renderRuntimeTemplate($m[2], $vars) : '',
            '/\{if\s+(.+?)\}(.*?)(?:\{else\}(.*?))?\{\/if\}/s' => function (array $m) use ($vars): string {
                return $this->evaluateTemplateCondition($m[1], $vars)
                    ? $this->renderRuntimeTemplate($m[2], $vars)
                    : $this->renderRuntimeTemplate($m[3] ?? '', $vars);
            },
            '/<!--if\{if\s+(.+?)\}-->(.*?)(?:<!--\{else\}else-->(.*?))?<!--\{\/if\}if-->/s' => function (array $m) use ($vars): string {
                return $this->evaluateTemplateCondition($m[1], $vars)
                    ? $this->renderRuntimeTemplate($m[2], $vars)
                    : $this->renderRuntimeTemplate($m[3] ?? '', $vars);
            },
        ];

        foreach ($patterns as $pattern => $renderer) {
            while (preg_match($pattern, $html)) {
                $html = preg_replace_callback($pattern, $renderer, $html) ?? $html;
            }
        }

        return $html;
    }

    /**
     * @param array<string, mixed> $vars
     */
    private function renderJoinedValueExpressions(string $html, array $vars): string
    {
        $pattern = '/\{:\$(\w+)\.([\w.]+)\s*\?\?\s*null\s*\?\s*\(is_array\(\$\1\.\2\)\s*\?\s*join\(\'([^\']*)\',\s*\$\1\.\2\)\s*:\s*\$\1\.\2\)\s*:\s*\'\'\}/';
        return preg_replace_callback($pattern, function (array $match) use ($vars): string {
            $value = $this->resolveTemplateValue($vars, $match[1] . '.' . $match[2]);
            if ($value === null) {
                return '';
            }
            if (is_array($value)) {
                return implode(stripslashes($match[3]), array_map('strval', $value));
            }
            return strval($value);
        }, $html) ?? $html;
    }

    /**
     * @param array<string, mixed> $vars
     */
    private function renderVariableExpressions(string $html, array $vars): string
    {
        $patterns = [
            '/\{(\$?[A-Za-z_]\w*(?:\.[A-Za-z_]\w*)*)\|default=(["\'])(.*?)\2\}/' => fn(array $match): string => $this->replaceVariableExpression($match, $vars, stripcslashes($match[3])),
            '/\{(\$?[A-Za-z_]\w*(?:\.[A-Za-z_]\w*)*)\|default=([^}\'"]+)\}/' => fn(array $match): string => $this->replaceVariableExpression($match, $vars, trim($match[2])),
        ];

        foreach ($patterns as $pattern => $renderer) {
            $html = preg_replace_callback($pattern, $renderer, $html) ?? $html;
        }

        return $html;
    }

    /**
     * @param array<string, mixed> $vars
     */
    private function renderPlainExpressions(string $html, array $vars): string
    {
        $pattern = '/\{(\$?[A-Za-z_]\w*(?:\.[A-Za-z_]\w*)*)\}/';
        return preg_replace_callback($pattern, function (array $match) use ($vars): string {
            return $this->stringifyTemplateValue($this->resolveTemplateValue($vars, $match[1]));
        }, $html) ?? $html;
    }

    /**
     * @param array<string, mixed> $vars
     */
    private function resolveTemplateValue(array $vars, string $path): mixed
    {
        $segments = array_values(array_filter(explode('.', ltrim($path, '$')), static fn(string $item): bool => $item !== ''));
        if (count($segments) < 1) {
            return null;
        }

        $value = $vars[$segments[0]] ?? null;
        foreach (array_slice($segments, 1) as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } elseif (is_object($value) && isset($value->{$segment})) {
                $value = $value->{$segment};
            } else {
                return null;
            }
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $vars
     */
    private function evaluateTemplateCondition(string $condition, array $vars): bool
    {
        foreach (preg_split('/\s+and\s+/', trim($condition)) ?: [] as $segment) {
            $segment = trim($segment);
            if ($segment === '') {
                continue;
            }

            if (preg_match('/^isset\((.+)\)$/', $segment, $match)) {
                if ($this->resolveTemplateOperand($vars, $match[1]) === null) {
                    return false;
                }
                continue;
            }

            if (preg_match('/^is_array\((.+)\)$/', $segment, $match)) {
                if (!is_array($this->resolveTemplateOperand($vars, $match[1]))) {
                    return false;
                }
                continue;
            }

            if (preg_match('/^in_array\((.+?),\s*(.+)\)$/', $segment, $match)) {
                $needle = $this->resolveTemplateOperand($vars, $match[1]);
                $haystack = $this->resolveTemplateOperand($vars, $match[2]);
                if (!is_array($haystack) || !in_array($needle, $haystack, false)) {
                    return false;
                }
                continue;
            }

            if (preg_match('/^strval\((.+)\)\s*(?:eq|==)\s*strval\((.+)\)$/', $segment, $match)) {
                if (strval($this->resolveTemplateOperand($vars, $match[1])) !== strval($this->resolveTemplateOperand($vars, $match[2]))) {
                    return false;
                }
                continue;
            }

            if (preg_match('/^strval\((.+)\)\s*(?:eq|==)\s*(.+)$/', $segment, $match)) {
                if (strval($this->resolveTemplateOperand($vars, $match[1])) !== strval($this->resolveTemplateOperand($vars, $match[2]))) {
                    return false;
                }
                continue;
            }

            return false;
        }

        return true;
    }

    /**
     * @param array<string, mixed> $vars
     */
    private function resolveTemplateOperand(array $vars, string $operand): mixed
    {
        $operand = trim($operand);
        if ($operand === 'null') {
            return null;
        }
        if (preg_match('/^[\'"](.*)[\'"]$/s', $operand, $match)) {
            return stripcslashes($match[1]);
        }
        return $this->resolveTemplateValue($vars, $operand);
    }

    private function isNotEmptyValue(mixed $value): bool
    {
        if (is_array($value)) {
            return count($value) > 0;
        }
        return !($value === null || $value === '' || $value === false);
    }

    private function stringifyTemplateValue(mixed $value): string
    {
        if (is_array($value)) {
            return implode(',', array_map('strval', $value));
        }
        if ($value === null) {
            return '';
        }
        return strval($value);
    }

    /**
     * @param array<int, string> $match
     * @param array<string, mixed> $vars
     */
    private function replaceVariableExpression(array $match, array $vars, string $default): string
    {
        $value = $this->resolveTemplateValue($vars, $match[1]);
        return $this->stringifyTemplateValue($value === null || $value === '' ? $default : $value);
    }

}
