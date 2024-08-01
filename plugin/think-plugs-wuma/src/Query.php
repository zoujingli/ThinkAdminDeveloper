<?php

// +----------------------------------------------------------------------
// | Wuma Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2022~2024 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 收费插件 ( https://thinkadmin.top/fee-introduce.html )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-wuma
// | github 代码仓库：https://github.com/zoujingli/think-plugs-wuma
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace plugin\wuma;

use plugin\wuma\service\CodeService;
use think\admin\Controller;

/**
 * 物码访问验证入口
 * @class Query
 * @package plugin\wuma
 */
class Query extends Controller
{
    /**
     * 当前标签编码
     * @var string
     */
    protected $code;

    /**
     * 标签查询验证
     * @param string $mode 匹配模式
     * @param string $code 标签内容
     * @param string $verify 安全验证
     * @return void
     * @throws \think\admin\Exception
     */
    public function index(string $mode, string $code, string $verify)
    {
        $this->code = $code;
        if (strtolower($mode) === 'n') {
            $min = CodeService::num2min($code);
        }
        if (strtolower($mode) === 'c') {
            $min = CodeService::enc2min($code);
        }
        if (isset($min)) {
            if ($verify === CodeService::url2ver($min)) {
                echo '验证成功！后面再显示对应页面';
                dump($mode, $min, $code, $verify, CodeService::min2ver($min));
                dump(CodeService::find($code, is_numeric($code) ? 'number' : 'encode'));
            } else {
                echo '验证失败！';
            }
        } else {
            echo '不支持的模式';
        }
    }
}