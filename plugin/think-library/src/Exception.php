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

/**
 * 自定义数据异常.
 * @class Exception
 */
class Exception extends \Exception
{
    /**
     * 异常数据对象
     * @var mixed
     */
    protected mixed $data = [];

    /**
     * Exception constructor.
     * @param string $message
     * @param int $code
     * @param mixed $data
     */
    public function __construct(string $message = '', int $code = 0, $data = [])
    {
        parent::__construct($message);
        $this->code = $code;
        $this->data = $data;
        $this->message = $message;
    }

    /**
     * 获取异常停止数据.
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * 设置异常停止数据.
     * @param mixed $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }
}
