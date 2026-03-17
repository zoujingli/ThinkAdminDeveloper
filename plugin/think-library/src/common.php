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
use think\admin\Exception;
use think\admin\extend\CodeToolkit;
use think\admin\extend\HttpClient;
use think\admin\helper\QueryHelper;
use think\admin\helper\ValidateHelper;
use think\admin\Library;
use think\admin\model\ModelFactory;
use think\admin\service\AppService;
use think\admin\service\CacheSession;
use think\admin\service\PluginService;
use think\admin\service\RuntimeService;
use think\admin\service\RuntimeTools;
use think\admin\service\Storage;
use think\db\BaseQuery;
use think\db\Query;
use think\helper\Str;
use think\Model;

if (!function_exists('p')) {
    /**
     * 输出调试数据到运行日志文件。
     *
     * @param mixed $data 调试数据
     * @param bool $new 是否覆盖原文件
     * @param ?string $file 指定日志文件
     */
    function p($data, bool $new = false, ?string $file = null): false|int
    {
        return RuntimeTools::putDebug($data, $new, $file);
    }
}

if (!function_exists('m')) {
    /**
     * 动态创建模型实例。
     *
     * @param string $name 模型名称
     * @param array $data 初始数据
     * @param string $conn 指定连接
     */
    function m(string $name, array $data = [], string $conn = ''): Model
    {
        return ModelFactory::build($name, $data, $conn);
    }
}

if (!function_exists('_vali')) {
    /**
     * 快捷读取输入并执行验证。
     *
     * @param array $rules 验证规则
     * @param array|string $type 输入源或输入数据
     * @param ?callable $callable 验证失败回调
     */
    function _vali(array $rules, array|string $type = '', ?callable $callable = null): array
    {
        return ValidateHelper::instance()->init($rules, $type, $callable);
    }
}

if (!function_exists('_query')) {
    /**
     * 创建快捷查询构造器。
     *
     * @param BaseQuery|Model|string $dbQuery 查询对象或模型名称
     * @param null|array|string $input 附加输入条件
     */
    function _query(BaseQuery|Model|string $dbQuery, array|string|null $input = null): QueryHelper
    {
        return QueryHelper::instance()->init($dbQuery, $input);
    }
}

if (!function_exists('sysvar')) {
    /**
     * 读写单次请求内的轻量级内存变量。
     *
     * 仅用于当前请求周期内的临时缓存。
     * 传入空字符串 `('', '')` 时会清空全部缓存。
     *
     * @param ?string $name 变量名
     * @param mixed $value 变量值
     * @return mixed
     */
    function sysvar(?string $name = null, $value = null)
    {
        static $swap = [];

        if ($name === '' && $value === '') {
            return $swap = [];
        }
        if ($value === null) {
            return $name === null ? $swap : ($swap[$name] ?? null);
        }

        return $swap[$name] = $value;
    }
}

if (!function_exists('sysuri')) {
    /**
     * 生成系统页面 URL。
     *
     * 相对路径会按当前应用、控制器、操作自动补全，
     * 绝对路径、命名路由和外部地址会直接交给路由器处理。
     *
     * @param string $url 路由地址
     * @param array $vars 路由参数
     * @param bool|string $suffix 后缀配置
     * @param bool|string $domain 域名配置
     */
    function sysuri(string $url = '', array $vars = [], bool|string $suffix = true, bool|string $domain = false): string
    {
        if (preg_match('#^(?:https?://|/|@)#', $url)) {
            return Library::$sapp->route->buildUrl($url, $vars)->suffix($suffix)->domain($domain)->build();
        }

        $attr = $url === '' ? [] : array_values(array_filter(explode('/', trim($url, '/')), 'strlen'));
        if (count($attr) > 3) {
            return Library::$sapp->route->buildUrl('/' . join('/', $attr), $vars)->suffix($suffix)->domain($domain)->build();
        }
        if (count($attr) < 3) {
            $map = [
                Library::$sapp->http->getName(),
                Library::$sapp->request->controller(),
                Library::$sapp->request->action(true),
            ];
            while (count($attr) < 3) {
                array_unshift($attr, $map[2 - count($attr)] ?? 'index');
            }
        }

        $attr[0] = Str::lower($attr[0]);
        $attr[1] = Str::snake($attr[1]);
        [$rcf, $tmp] = [Library::$sapp->config->get('route', []), uniqid('think_admin_replace_temp_vars_')];
        $map = [
            Str::lower(AppService::singleCode()),
            Str::snake($rcf['default_controller'] ?? ''),
            Str::lower($rcf['default_action'] ?? ''),
        ];

        for ($idx = min(count($attr), count($map)) - 1; $idx >= 0; --$idx) {
            if ($attr[$idx] === ($map[$idx] ?: 'index')) {
                $attr[$idx] = $tmp;
            } else {
                break;
            }
        }

        $url = Library::$sapp->route->buildUrl(join('/', $attr), $vars)->suffix($suffix)->domain($domain)->build();
        $ext = is_string($suffix) ? ltrim($suffix, '.') : strval($rcf['url_html_suffix'] ?? 'html');
        $pattern = $ext === ''
            ? '#/' . preg_quote($tmp, '#') . '#'
            : '#/' . preg_quote($tmp, '#') . '(\.' . preg_quote($ext, '#') . ')?#';
        $old = parse_url($url, PHP_URL_PATH) ?: '';
        $new = preg_replace($pattern, '', $old, -1, $count) ?? $old;
        if ($count > 0 && $suffix && $new !== '' && $ext !== '' && $new !== Library::$sapp->request->baseUrl()) {
            $new .= ".{$ext}";
        }

        return str_replace($old, $new ?: '/', $url);
    }
}

if (!function_exists('apiuri')) {
    /**
     * 生成标准插件 API URL。
     *
     * 统一输出 `/api/{plugin}/{controller}/{action}` 风格地址，
     * 并兼容当前插件上下文与 `controller/api/*` 的历史写法。
     *
     * @param string $url 路由地址
     * @param array $vars 路由参数
     * @param bool|string $suffix 后缀配置
     * @param bool|string $domain 域名配置
     */
    function apiuri(string $url = '', array $vars = [], bool|string $suffix = true, bool|string $domain = false): string
    {
        if (preg_match('#^(?:https?://|/|@)#', $url)) {
            return Library::$sapp->route->buildUrl($url, $vars)->suffix($suffix)->domain($domain)->build();
        }

        $attrs = $url === '' ? [] : array_values(array_filter(explode('/', trim($url, '/')), 'strlen'));
        $module = PluginService::currentCode() ?: (Library::$sapp->http->getName() ?: AppService::singleCode());
        $controller = Library::$sapp->request->controller();
        $action = Library::$sapp->request->action(true);

        if (count($attrs) >= 3) {
            $module = array_shift($attrs) ?: $module;
            $controller = array_shift($attrs) ?: $controller;
            $action = join('/', $attrs) ?: $action;
        } elseif (count($attrs) === 2) {
            [$controller, $action] = $attrs;
        } elseif (count($attrs) === 1) {
            $action = $attrs[0];
        }

        $module = Str::lower(trim(str_replace('\\', '/', $module), '/')) ?: AppService::singleCode();
        $controller = trim(str_replace(['.', '\\'], '/', $controller), '/');
        $segments = array_values(array_filter(explode('/', $controller), 'strlen'));
        if (($segments[0] ?? '') !== '' && strcasecmp($segments[0], 'api') === 0) {
            array_shift($segments);
        }
        $controller = join('/', array_map(static function (string $segment): string {
            return Str::snake($segment);
        }, $segments)) ?: 'index';
        $action = trim(str_replace('\\', '/', $action), '/') ?: 'index';

        $apiPrefix = PluginService::entryPrefix();
        $target = '/' . trim("{$apiPrefix}/{$module}/{$controller}/{$action}", '/');
        return Library::$sapp->route->buildUrl($target, $vars)->suffix($suffix)->domain($domain)->build();
    }
}

if (!function_exists('tsession')) {
    /**
     * 获取令牌会话服务实例。
     */
    function tsession(): CacheSession
    {
        return CacheSession::instance();
    }
}

if (!function_exists('encode')) {
    /**
     * 将 UTF-8 文本编码为兼容旧逻辑的短字符串。
     */
    function encode(string $content): string
    {
        $string = CodeToolkit::text2utf8($content);
        $length = strlen($string);
        if ($length === 0) {
            return '';
        }

        $chars = '';
        for ($i = 0; $i < $length; ++$i) {
            $chars .= str_pad(base_convert((string)ord($string[$i]), 10, 36), 2, '0', STR_PAD_LEFT);
        }

        return $chars;
    }
}

if (!function_exists('decode')) {
    /**
     * 将 `encode()` 结果还原为 UTF-8 文本。
     */
    function decode(string $content): string
    {
        if ($content === '') {
            return '';
        }

        $chars = '';
        foreach (str_split($content, 2) as $char) {
            if (strlen($char) < 2) {
                continue;
            }
            $chars .= chr((int)base_convert($char, 36, 10));
        }

        return CodeToolkit::text2utf8($chars);
    }
}

if (!function_exists('str2arr')) {
    /**
     * 将字符串或数组标准化为数组。
     *
     * 字符串会按分隔符拆分；数组会递归展开。
     * 返回结果会自动去空白，并按需执行 allow 白名单过滤。
     *
     * @param array|string $text 原始内容
     * @param string $separ 分隔符
     * @param ?array $allow 白名单限制
     */
    function str2arr(array|string $text, string $separ = ',', ?array $allow = null): array
    {
        $items = [];

        foreach ((array)$text as $item) {
            if (is_array($item)) {
                foreach (str2arr($item, $separ, $allow) as $value) {
                    $items[] = $value;
                }
                continue;
            }
            if (!is_scalar($item) || $item === false || $item === null) {
                continue;
            }
            if (is_string($item)) {
                foreach (explode($separ, trim($item, $separ)) as $value) {
                    $value = trim($value);
                    if ($value !== '' && (!is_array($allow) || in_array($value, $allow, true))) {
                        $items[] = $value;
                    }
                }
                continue;
            }
            if (!is_array($allow) || in_array($item, $allow, true)) {
                $items[] = $item;
            }
        }

        return $items;
    }
}

if (!function_exists('arr2str')) {
    /**
     * 将字符串或数组标准化为分隔字符串。
     *
     * 内部会复用 `str2arr()` 做统一归一化，
     * 最终输出形如 `,a,b,c,` 的历史兼容格式。
     *
     * @param array|string $data 原始内容
     * @param string $separ 分隔符
     * @param ?array $allow 白名单限制
     */
    function arr2str(array|string $data, string $separ = ',', ?array $allow = null): string
    {
        $items = str2arr($data, $separ, $allow);
        return empty($items) ? '' : $separ . join($separ, $items) . $separ;
    }
}

if (!function_exists('isDebug')) {
    /**
     * 判断当前是否处于调试模式。
     */
    function isDebug(): bool
    {
        return RuntimeService::isDebug();
    }
}

if (!function_exists('isOnline')) {
    /**
     * 判断当前是否处于生产模式。
     */
    function isOnline(): bool
    {
        return RuntimeService::isOnline();
    }
}

if (!function_exists('syspath')) {
    /**
     * 拼接项目根目录下的绝对路径。
     *
     * @param string $name 相对路径
     * @param ?string $root 根路径
     */
    function syspath(string $name = '', ?string $root = null): string
    {
        if ($root === null) {
            $root = Library::$sapp->getRootPath();
            if (defined('THINK_PLUGS_INSTALL_ROOT') && is_string(THINK_PLUGS_INSTALL_ROOT) && THINK_PLUGS_INSTALL_ROOT !== '') {
                $prefix = strtolower(strtok(str_replace('\\', '/', ltrim($name, '\\/')), '/'));
                if (in_array($prefix, ['database', 'public', 'runtime', 'safefile'], true)) {
                    $root = THINK_PLUGS_INSTALL_ROOT;
                }
            }
        }

        $attr = ['/' => DIRECTORY_SEPARATOR, '\\' => DIRECTORY_SEPARATOR];
        return rtrim($root, '\/') . DIRECTORY_SEPARATOR . ltrim(strtr($name, $attr), '\/');
    }
}

if (!function_exists('enbase64url')) {
    /**
     * Base64 URL 安全编码。
     */
    function enbase64url(string $string): string
    {
        return CodeToolkit::enSafe64($string);
    }
}

if (!function_exists('debase64url')) {
    /**
     * Base64 URL 安全解码。
     */
    function debase64url(string $string): string
    {
        return CodeToolkit::deSafe64($string);
    }
}

if (!function_exists('xss_safe')) {
    /**
     * 对文本执行基础 XSS 安全处理。
     *
     * 当前逻辑会移除 script 标签，并中和内联事件属性。
     */
    function xss_safe(string $text): string
    {
        $rules = [
            '#<script\b[^>]*>.*?</script>#is' => '',
            '#(\s+)on([a-z_][\w:-]*\s*=)#i' => '$1data-on-$2',
        ];

        return preg_replace(array_keys($rules), array_values($rules), trim($text)) ?? trim($text);
    }
}

if (!function_exists('http_get')) {
    /**
     * 发送 GET 请求。
     *
     * @param string $url 请求地址
     * @param array|string $query 查询参数
     * @param array $options 客户端配置
     */
    function http_get(string $url, array|string $query = [], array $options = []): bool|string
    {
        return HttpClient::get($url, $query, $options);
    }
}

if (!function_exists('http_post')) {
    /**
     * 发送 POST 请求。
     *
     * @param string $url 请求地址
     * @param array|string $data 提交数据
     * @param array $options 客户端配置
     */
    function http_post(string $url, array|string $data, array $options = []): bool|string
    {
        return HttpClient::post($url, $data, $options);
    }
}

if (!function_exists('data_save')) {
    /**
     * 按主键或条件执行增量保存。
     *
     * @param Model|Query|string $dbQuery 查询对象或模型
     * @param array $data 保存数据
     * @param string $key 主键字段
     * @param mixed $where 附加条件
     * @return bool|int
     * @throws Exception
     */
    function data_save($dbQuery, array $data, string $key = 'id', $where = [])
    {
        return RuntimeTools::save($dbQuery, $data, $key, $where);
    }
}

if (!function_exists('down_file')) {
    /**
     * 下载远程文件并返回本地访问地址。
     *
     * @param string $source 源文件地址
     * @param bool $force 是否强制重下
     * @param int $expire 本地缓存秒数
     */
    function down_file(string $source, bool $force = false, int $expire = 0): string
    {
        return Storage::down($source, $force, $expire)['url'] ?? $source;
    }
}

if (!function_exists('trace_file')) {
    /**
     * 将异常信息落盘到 runtime/trace 目录。
     *
     * @param Throwable $exception 异常对象
     */
    function trace_file(Throwable $exception): bool
    {
        $path = rtrim(Library::$sapp->getRuntimePath(), '\/') . DIRECTORY_SEPARATOR . 'trace';
        if (!is_dir($path) && !mkdir($path, 0777, true) && !is_dir($path)) {
            return false;
        }

        $root = strtr(rtrim(syspath(), '\/'), '\\', '/');
        $source = strtr($exception->getFile(), '\\', '/');
        $name = basename($source);
        if ($root !== '' && str_starts_with(strtolower($source), strtolower($root . '/'))) {
            $name = ltrim(substr($source, strlen($root)), '/');
        }

        $file = $path . DIRECTORY_SEPARATOR . date('Ymd_His_') . strtr($name, ['/' => '.', '\\' => '.']);
        $json = json_encode(
            $exception instanceof Exception ? $exception->getData() : [],
            JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        ) ?: '[]';
        $class = get_class($exception);

        return file_put_contents(
            $file,
            "[CODE] {$exception->getCode()}" . PHP_EOL
            . "[INFO] {$exception->getMessage()}" . PHP_EOL
            . ($exception instanceof Exception ? "[DATA] {$json}" . PHP_EOL : '')
            . "[FILE] {$class} in {$name} line {$exception->getLine()}" . PHP_EOL
            . '[TIME] ' . date('Y-m-d H:i:s') . PHP_EOL . PHP_EOL
            . '[TRACE]' . PHP_EOL . $exception->getTraceAsString()
        ) !== false;
    }
}

if (!function_exists('format_bytes')) {
    /**
     * 将字节数格式化为可读单位。
     *
     * @param float|int|string $size 原始字节值
     */
    function format_bytes($size): string
    {
        if (is_numeric($size)) {
            $size = (float)$size;
            $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
            for ($i = 0; $size >= 1024 && $i < count($units) - 1; ++$i) {
                $size /= 1024;
            }

            return round($size, 2) . ' ' . $units[$i];
        }

        return is_string($size) ? $size : strval($size);
    }
}
