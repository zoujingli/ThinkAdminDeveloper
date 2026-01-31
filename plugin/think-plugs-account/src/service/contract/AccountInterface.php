<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | Payment Plugin for ThinkAdmin
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

namespace plugin\account\service\contract;

use plugin\account\model\PluginAccountUser;
use think\admin\Exception;
use think\db\exception\DbException;

/**
 * 用户账号接口类.
 * @class AccountInterface
 */
interface AccountInterface
{
    /**
     * 读取子账号资料.
     */
    public function get(bool $rejwt = false, bool $refresh = false): array;

    /**
     * 设置子账号资料.
     * @param array $data 用户资料
     * @param bool $rejwt 返回令牌
     */
    public function set(array $data = [], bool $rejwt = false): array;

    /**
     * 初始化通道.
     * @param array|string $token
     */
    public function init($token = '', bool $isjwt = true): AccountInterface;

    /**
     * 获取用户模型.
     */
    public function user(): PluginAccountUser;

    /**
     * 获取用户编号.
     */
    public function getCode(): string;

    /**
     * 获取终端类型.
     */
    public function getType(): string;

    /**
     * 获取用户编号.
     */
    public function getUnid(): int;

    /**
     * 获取终端编号.
     */
    public function getUsid(): int;

    /**
     * 绑定主账号.
     * @param array $map 主账号条件
     * @param array $data 主账号资料
     */
    public function bind(array $map, array $data = []): array;

    /**
     * 解绑主账号.
     */
    public function unBind(): array;

    /**
     * 判断绑定主账号.
     */
    public function isBind(): bool;

    /**
     * 判断是否空账号.
     */
    public function isNull(): bool;

    /**
     * 获取关联终端.
     */
    public function allBind(): array;

    /**
     * 解除终端关联.
     * @param int $usid 终端编号
     */
    public function delBind(int $usid): array;

    /**
     * 验证终端密码
     */
    public function pwdVerify(string $pass): bool;

    /**
     * 修改终端密码
     * @param string $pass 待修改密码
     * @param bool $event 触发事件
     */
    public function pwdModify(string $pass, bool $event = true): bool;

    /**
     * 刷新账号序号.
     */
    public function recode(): array;

    /**
     * 检查是否有效.
     * @throws Exception
     */
    public function check(): array;

    /**
     * 生成授权令牌.
     * @throws DbException
     */
    public function token(): AccountInterface;

    /**
     * 延期令牌时间.
     */
    public function expire(): AccountInterface;
}
