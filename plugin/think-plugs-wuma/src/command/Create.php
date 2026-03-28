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

namespace plugin\wuma\command;

use plugin\system\service\ConfigService as SystemConfigService;
use plugin\worker\service\ProcessService as Process;
use think\admin\Command;
use think\admin\extend\CodeToolkit;
use think\admin\Library;
use think\Exception;

/**
 * 同步物码规则.
 * @class Create
 */
class Create extends Command
{
    /**
     * 物码批次号.
     */
    protected string $batch;

    /**
     * 任务执行.
     */
    public function handle()
    {
        $this->batch = $this->input->getArgument('batch');
        if (empty($this->batch)) {
            $this->setQueueError('批次号不能为空！');
        }
        // 物码服务生成文件
        [$status, $message] = $this->_create($this->batch);
        empty($status) ? $this->setQueueError($message) : $this->setQueueSuccess($message);
    }

    /**
     * 指令配置.
     */
    protected function configure()
    {
        $this->setName('xdata:wuma:create');
        $this->addArgument('batch', null, '待处理的物码批次号');
        $this->setDescription('创建物码压缩文件');
    }

    /**
     * 创建物码规则.
     * @param string $batch 物码批次号
     * @return array [state, info, result]
     */
    private function _create(string $batch): array
    {
        try {
            $data = $this->_exec("create {$batch}");
            if (isset($data['code'], $data['data']['file'])) {
                return [true, $data['info'], $data['data']];
            }
            return [false, $data['info'], ''];
        } catch (\Exception $exception) {
            return [false, $exception->getMessage()];
        }
    }

    /**
     * 执行操作指令.
     * @throws Exception
     * @throws \think\admin\Exception
     */
    private function _exec(string $params): array
    {
        $auth = CodeToolkit::random(20);
        $this->app->cache->set("create_auth_{$this->batch}", $auth, 360);
        $token = base64_encode(json_encode([
            'auth' => $auth,
            'host' => SystemConfigService::getSiteHost(),
            'target' => runpath('safefile/code/'),
        ]));
        $binary = dirname(__DIR__, 2) . '/stc/bin/' . (Process::isWin() ? 'coder.exe' : 'coder');
        // 赋予文件可执行权限
        if (!Process::isWin() && file_exists($binary) && !is_executable($binary)) {
            Process::exec("/usr/bin/chmod +x {$binary}");
        }
        // 检查是否具有可执行权限
        if (!is_executable($binary)) {
            $filename = substr($binary, strlen(Library::$sapp->getRootPath()));
            throw new Exception("生码工具[./{$filename}]没有可执行权限！");
        }
        // 通过二进制执行物码操作
        $result = Process::exec("{$binary} {$token} {$params}");
        if ($this->app->isDebug()) {
            $this->app->log->notice("Execute: {$binary} {$token} {$params}");
            $this->app->log->notice("Results: {$result}");
        }
        return json_decode(trim($result) ?: '[]', true);
    }
}
