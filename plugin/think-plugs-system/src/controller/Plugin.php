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

namespace plugin\system\controller;

use plugin\system\builder\PluginBuilder;
use plugin\system\service\PluginService;
use think\admin\Controller;
use think\admin\Exception;
use think\exception\HttpResponseException;

/**
 * 系统插件中心控制器.
 * @class Plugin
 */
class Plugin extends Controller
{
    private const PLUGIN_LAYOUT_VIEW = __DIR__ . '/../view/plugin/layout.html';

    /**
     * 插件中心首页.
     *
     * @auth true
     * @menu true
     * @login true
     * @throws Exception
     */
    public function index(): void
    {
        if (!PluginService::isEnabled()) {
            $this->respondWithPageBuilder(PluginBuilder::buildDisabledPage());
            return;
        }

        $context = PluginService::buildIndexContext();
        $this->respondWithPageBuilder(PluginBuilder::buildIndexPage($context), $context);
    }

    /**
     * 插件工作台布局
     *
     * @login true
     * @throws Exception
     */
    public function layout(): void
    {
        if (!PluginService::isEnabled()) {
            $this->fetchError(strval(lang('插件中心已禁用，请在系统参数中重新启用。')));
            return;
        }

        try {
            foreach (PluginService::buildLayoutContext(strval($this->request->get('encode', ''))) as $name => $value) {
                $this->{$name} = $value;
            }
            $this->fetch(self::PLUGIN_LAYOUT_VIEW);
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            $plugin = is_array($this->plugin ?? null) ? $this->plugin : [];
            $this->fetchError($exception->getMessage(), $plugin);
        }
    }

    /**
     * 渲染插件工作台错误页.
     */
    private function fetchError(string $content, array $plugin = []): void
    {
        $context = PluginService::buildLayoutErrorContext($content, $plugin);
        foreach ($context as $name => $value) {
            $this->{$name} = $value;
        }
        $builder = PluginBuilder::buildErrorPage([
            'content' => $content,
            'returnUrl' => strval($context['returnUrl'] ?? ''),
        ]);
        $this->contentHtml = $builder->renderHtml([
            'showErrorMessage' => '',
        ]);
        $this->fetch(self::PLUGIN_LAYOUT_VIEW);
    }
}
