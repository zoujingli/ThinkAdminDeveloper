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

namespace think\admin;

use think\admin\helper\ValidateHelper;
use think\admin\runtime\SystemContext;
use think\admin\service\JwtToken;
use think\admin\service\NodeService;
use think\admin\service\QueueService;
use think\App;
use think\exception\HttpResponseException;
use think\Request;

/**
 * 标准控制器基类.
 * @class Controller
 */
class Controller extends \stdClass
{
    /**
     * 应用容器.
     */
    public App $app;

    /**
     * 请求GET参数.
     */
    public array $get = [];

    /**
     * 当前功能节点.
     */
    public string $node = '';

    /**
     * 请求参数对象
     */
    public Request $request;

    /**
     * Constructor.
     */
    public function __construct(App $app)
    {
        if (in_array($app->request->action(), get_class_methods(__CLASS__))) {
            $this->error('禁止访问内置方法！');
        }
        $this->get = $app->request->get();
        $this->app = $app->bind('think\admin\Controller', $this);
        $this->node = NodeService::getCurrent();
        $this->request = $this->app->request;
        $this->initialize();
    }

    /**
     * 返回失败的内容.
     * @param mixed $info 消息内容
     * @param mixed $data 返回数据
     * @param mixed $code 返回代码
     */
    public function error(mixed $info, mixed $data = '{-null-}', mixed $code = 0): never
    {
        $this->success($info, $data, $code);
    }

    /**
     * 返回成功的内容.
     * @param mixed $info 消息内容
     * @param mixed $data 返回数据
     * @param mixed $code 返回代码
     */
    public function success(mixed $info, mixed $data = '{-null-}', mixed $code = 1): never
    {
        if ($data === '{-null-}') {
            $data = new \stdClass();
        }
        $result = ['code' => $code, 'info' => is_string($info) ? lang($info) : $info, 'data' => $data];
        if (JwtToken::isRejwt()) {
            $result['token'] = JwtToken::token();
        } elseif ($token = SystemContext::instance()->buildToken()) {
            $result['token'] = $token;
            SystemContext::instance()->syncTokenCookie($token);
        }
        throw new HttpResponseException(json($result));
    }

    /**
     * URL重定向.
     * @param string $url 跳转链接
     * @param int $code 跳转代码
     */
    public function redirect(string $url, int $code = 302): void
    {
        throw new HttpResponseException(redirect($url, $code));
    }

    /**
     * 返回视图内容.
     * @param string $tpl 模板名称
     * @param array $vars 模板变量
     * @param null|string $node 授权节点
     */
    public function fetch(string $tpl = '', array $vars = [], ?string $node = null): void
    {
        foreach (get_object_vars($this) as $name => $value) {
            $vars[$name] = $value;
        }
        throw new HttpResponseException(view($tpl, $vars));
    }

    /**
     * 模板变量赋值
     * @param mixed $name 要显示的模板变量
     * @param mixed $value 变量的值
     * @return $this
     */
    public function assign(mixed $name, mixed $value = ''): static
    {
        if (is_string($name)) {
            $this->{$name} = $value;
        } elseif (is_array($name)) {
            foreach ($name as $k => $v) {
                if (is_string($k)) {
                    $this->{$k} = $v;
                }
            }
        }
        return $this;
    }

    /**
     * 数据回调处理机制.
     * @param string $name 回调方法名称
     * @param mixed $one 回调引用参数1
     * @param mixed $two 回调引用参数2
     * @param mixed $thr 回调引用参数3
     */
    public function callback(string $name, mixed &$one = [], mixed &$two = [], mixed &$thr = []): bool
    {
        if (is_callable($name)) {
            return call_user_func($name, $this, $one, $two, $thr);
        }
        foreach (["_{$this->app->request->action()}{$name}", $name] as $method) {
            if (method_exists($this, $method) && $this->{$method}($one, $two, $thr) === false) {
                return false;
            }
        }
        return true;
    }

    /**
     * 控制器初始化.
     */
    protected function initialize() {}

    /**
     * 快捷输入并验证（ 支持 规则 # 别名 ）.
     * @param array $rules 验证规则（ 验证信息数组 ）
     * @param array|string $type 输入方式 ( post. 或 get. )
     * @param null|callable $callable 异常处理操作
     */
    protected function _vali(array $rules, array|string $type = '', ?callable $callable = null): array
    {
        return ValidateHelper::instance()->init($rules, $type, $callable);
    }

    /**
     * 创建异步任务并返回任务编号.
     * @param string $title 任务名称
     * @param string $command 执行内容
     * @param int $later 延时执行时间
     * @param array $data 任务附加数据
     * @param int $loops 循环等待时间
     * @param ?int $legacyLoops 兼容旧调用的循环等待时间参数
     */
    protected function _queue(string $title, string $command, int $later = 0, array $data = [], int $loops = 0, ?int $legacyLoops = null): void
    {
        try {
            $queue = QueueService::register($title, $command, $later, $data, $loops, $legacyLoops);
            $this->success('创建任务成功！', $queue->getCode());
        } catch (Exception $exception) {
            $code = $exception->getData();
            if (is_string($code) && stripos($code, 'Q') === 0) {
                $this->success('任务已经存在，无需再次创建！', $code);
            } else {
                $this->error($exception->getMessage());
            }
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            trace_file($exception);
            $this->error(lang('创建任务失败，%s', [$exception->getMessage()]));
        }
    }
}
