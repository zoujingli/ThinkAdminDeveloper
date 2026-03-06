<?php

declare(strict_types=1);

namespace plugin\helper\support;

final class PluginRegistry
{
    /**
     * @return array<string, array{
     *     title:string,
     *     target:string,
     *     file:string,
     *     class:string,
     *     name:string,
     *     tables?:string[],
     *     prefixes?:string[]
     * }>
     */
    public static function all(): array
    {
        return [
            'admin' => [
                'title' => '系统模块',
                'target' => 'plugin/think-plugs-admin/stc/database',
                'file' => '20241010000001_install_admin20241010.php',
                'class' => 'InstallAdmin20241010',
                'name' => 'AdminPlugin',
                'tables' => [
                    'system_auth',
                    'system_auth_node',
                    'system_base',
                    'system_config',
                    'system_data',
                    'system_file',
                    'system_menu',
                    'system_oplog',
                    'system_queue',
                    'system_user',
                ],
            ],
            'wechat' => [
                'title' => '微信模块',
                'target' => 'plugin/think-plugs-wechat/stc/database',
                'file' => '20241010000003_install_wechat20241010.php',
                'class' => 'InstallWechat20241010',
                'name' => 'WechatPlugin',
                'prefixes' => ['wechat_'],
            ],
            'center' => [
                'title' => '会员中心模块',
                'target' => 'plugin/think-plugs-center/stc/database',
                'file' => '20241010000004_install_center20241010.php',
                'class' => 'InstallCenter20241010',
                'name' => 'CenterPlugin',
                'tables' => [],
            ],
            'account' => [
                'title' => '账号模块',
                'target' => 'plugin/think-plugs-account/stc/database',
                'file' => '20241010000005_install_account20241010.php',
                'class' => 'InstallAccount20241010',
                'name' => 'AccountPlugin',
                'prefixes' => ['plugin_account_'],
            ],
            'payment' => [
                'title' => '支付模块',
                'target' => 'plugin/think-plugs-payment/stc/database',
                'file' => '20241010000006_install_payment20241010.php',
                'class' => 'InstallPayment20241010',
                'name' => 'PaymentPlugin',
                'prefixes' => ['plugin_payment_'],
            ],
            'wemall' => [
                'title' => '商城模块',
                'target' => 'plugin/think-plugs-wemall/stc/database',
                'file' => '20241010000007_install_wemall20241010.php',
                'class' => 'InstallWemall20241010',
                'name' => 'WemallPlugin',
                'prefixes' => ['plugin_wemall_'],
            ],
            'wechat-service' => [
                'title' => '开放平台模块',
                'target' => 'plugin/think-plugs-wechat-service/stc/database',
                'file' => '20241010000009_install_wechat_service20241010.php',
                'class' => 'InstallWechatService20241010',
                'name' => 'WechatServicePlugin',
                'tables' => ['wechat_auth'],
            ],
            'wuma' => [
                'title' => '物码模块',
                'target' => 'plugin/think-plugs-wuma/stc/database',
                'file' => '20241010000010_install_wuma20241010.php',
                'class' => 'InstallWuma20241010',
                'name' => 'WumaPlugin',
                'prefixes' => ['plugin_wuma_'],
            ],
        ];
    }

    /**
     * @return array<string, array{
     *     title:string,
     *     target:string,
     *     file:string,
     *     class:string,
     *     name:string,
     *     tables?:string[],
     *     prefixes?:string[]
     * }>
     */
    public static function selected(array $plugins = []): array
    {
        $items = static::all();
        if (empty($plugins)) {
            return $items;
        }

        $selected = [];
        foreach ($plugins as $plugin) {
            if (isset($items[$plugin])) {
                $selected[$plugin] = $items[$plugin];
            }
        }

        return $selected;
    }

    public static function matchPlugin(string $table): ?string
    {
        foreach (static::all() as $plugin => $config) {
            if (in_array($table, $config['tables'] ?? [], true)) {
                return $plugin;
            }

            foreach ($config['prefixes'] ?? [] as $prefix) {
                if (str_starts_with($table, $prefix)) {
                    return $plugin;
                }
            }
        }

        return null;
    }
}
