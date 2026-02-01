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
// | Payment Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2025 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// +----------------------------------------------------------------------

$extra = [];

return array_merge($extra, [
    // 通用
    '回 收 站' => 'Recycle Bin',
    '排序权重' => 'Sort Weight',
    '头像' => 'Avatar',
    '操作面板' => 'Actions',
    '已激活' => 'Activated',
    '已禁用' => 'Disabled',
    '已启用' => 'Enabled',
    '已取消' => 'Cancelled',
    '已生效' => 'Effective',
    '锁定中' => 'Locked',
    '锁定' => 'Lock',
    '解 锁' => 'Unlock',
    '锁 定' => 'Lock',
    '删 除' => 'Delete',
    '编 辑' => 'Edit',
    '保存数据' => 'Save Data',
    '取消编辑' => 'Cancel Edit',
    '确定要永久删除吗？' => 'Are you sure you want to permanently delete?',
    '确定要删除选中的记录吗？' => 'Are you sure you want to delete selected records?',
    '确定要删除文章吗?' => 'Are you sure you want to delete the article?',
    '全部' => 'All',
    '搜 索' => 'Search',
    '导 出' => 'Export',
    '创建时间' => 'Create Time',
    '操作时间' => 'Operation Time',
    '更新时间' => 'Update Time',

    // 余额管理
    '余额管理' => 'Balance Management',
    '余额统计' => 'Balance Statistics',
    '累计充值' => 'Total Recharge',
    '已消费' => 'Consumed',
    '剩余可用余额' => 'Available Balance',
    '用户账号' => 'User Account',
    '用户昵称' => 'User Nickname',
    '绑定账号' => 'Bound Account',
    '交易金额' => 'Transaction Amount',
    '交易单号' => 'Transaction Code',
    '交易状态' => 'Transaction Status',
    '操作描述' => 'Operation Description',
    '操作名称' => 'Operation Name',
    '状态操作' => 'Status Operation',
    '取消时间' => 'Cancel Time',
    '生效时间' => 'Effective Time',
    '锁定时间' => 'Lock Time',
    '元' => 'Yuan',

    // 积分管理
    '积分管理' => 'Integral Management',
    '积分统计' => 'Integral Statistics',
    '累计发放' => 'Total Issued',
    '剩余可用' => 'Available',
    '积分' => 'Integral',

    // 支付管理
    '支付管理' => 'Payment Management',
    '支付配置' => 'Payment Configuration',
    '添加支付' => 'Add Payment',
    '批量删除' => 'Batch Delete',
    '图标' => 'Icon',
    '支付编号' => 'Payment Code',
    '支付类型' => 'Payment Type',
    '支付名称' => 'Payment Name',
    '终端授权' => 'Terminal Authorization',
    '支付状态' => 'Payment Status',

    // 支付记录
    '订单内容' => 'Order Content',
    '订单标题' => 'Order Title',
    '订单编号' => 'Order Number',
    '订单名称' => 'Order Name',
    '订单金额' => 'Order Amount',
    '需支付' => 'Need to Pay',
    '已付' => 'Paid',
    '待审' => 'Pending Review',
    '支付描述' => 'Payment Description',
    '支付状态' => 'Payment Status',
    '已支付' => 'Paid',
    '未支付' => 'Unpaid',
    '待支付' => 'Pending Payment',
    '待审核' => 'Pending Review',
    '已拒绝' => 'Rejected',
    '已完成' => 'Completed',
    '支付类型' => 'Payment Type',
    '支付时间' => 'Payment Time',
    '用户编号' => 'User Code',
    '支付行为数据' => 'Payment Behavior Data',

    // 退款管理
    '退款内容' => 'Refund Content',
    '用户姓名' => 'User Name',
    '请输入用户姓名' => 'Please enter user name',
    '请输入订单内容' => 'Please enter order content',
    '请选择创建时间' => 'Please select create time',

    // 微信支付配置
    '公众号APPID' => 'WeChat Official Account APPID',
    '请输入18位绑定公众号（必填）' => 'Please enter 18-digit bound official account (required)',
    '微信商户号' => 'WeChat Merchant Number',
    '请输入微信商户号（必填）' => 'Please enter WeChat merchant number (required)',
    '微信支付 V2 接口' => 'WeChat Payment V2 API',
    '微信支付 V3 接口' => 'WeChat Payment V3 API',
    '商户密钥' => 'Merchant Key',
    '请输入32位微信商户密钥（必填）' => 'Please enter 32-digit WeChat merchant key (required)',
    '支付公钥ID' => 'Payment Public Key ID',
    '请输入商户证书公钥序号' => 'Please enter merchant certificate public key serial number',
    '证书内容' => 'Certificate Content',
    '请输入商户证书公钥内容' => 'Please enter merchant certificate public key content',
    '必填，' => 'Required, ',
    '从商户平台上下载支付证书，解压并取得其中的 apiclient_cert.pem 用记事本打开并复制文件内容填至此处。' => 'Download the payment certificate from the merchant platform, extract it and get apiclient_cert.pem, open it with Notepad and copy the file content to fill here.',
    '密钥内容' => 'Key Content',
    '请输入微信商户密钥文件内容' => 'Please enter WeChat merchant key file content',
    '从商户平台上下载支付证书，解压并取得其中的 apiclient_key.pem 用记事本打开并复制文件内容填至此处。' => 'Download the payment certificate from the merchant platform, extract it and get apiclient_key.pem, open it with Notepad and copy the file content to fill here.',
    '微信支付公钥' => 'WeChat Payment Public Key',
    '微信支付公钥 ID' => 'WeChat Payment Public Key ID',
    '请输入微信支付公钥ID' => 'Please enter WeChat payment public key ID',
    '微信支付公钥内容' => 'WeChat Payment Public Key Content',
    '（ 需要填写文件的全部内容 ）' => '(Need to fill in the full content of the file)',
    '请输入微信支付公钥内容' => 'Please enter WeChat payment public key content',
    '可选，' => 'Optional, ',
    '从商户平台上下载支付证书，解压并取得其中的 pub_key.pem 用记事本打开并复制文件内容填至此处。' => 'Download the payment certificate from the merchant platform, extract it and get pub_key.pem, open it with Notepad and copy the file content to fill here.',
]);
