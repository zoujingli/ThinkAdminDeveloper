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

namespace think\admin\tests;

use plugin\payment\service\Recount;
use plugin\worker\service\ProcessService;
use think\admin\contract\QueueRuntimeInterface;
use think\admin\tests\Support\SqliteIntegrationTestCase;
use think\Model;

/**
 * @internal
 * @coversNothing
 */
class PaymentRecountServiceTest extends SqliteIntegrationTestCase
{
    public function testExecuteUsesEnglishQueueMessagesWhenLangSetIsEnUs(): void
    {
        $user = $this->createAccountUser([
            'username' => 'queue-user',
            'nickname' => '队列用户',
        ]);
        $this->switchPaymentLang('en-us');

        $queue = new class implements QueueRuntimeInterface {
            public array $messages = [];

            public string $successMessage = '';

            public string $errorMessage = '';

            public function getCode(): string
            {
                return 'payment-recount-test';
            }

            public function getTitle(): string
            {
                return 'payment recount';
            }

            public function getData(): array
            {
                return [];
            }

            public function getRecord(): Model
            {
                return new class extends Model {};
            }

            public function progress(?int $status = null, ?string $message = null, ?string $progress = null, int $backline = 0): array
            {
                return ['status' => $status, 'message' => $message, 'progress' => $progress, 'backline' => $backline];
            }

            public function message(int $total, int $count, string $message = '', int $backline = 0): void
            {
                $this->messages[] = compact('total', 'count', 'message', 'backline');
            }

            public function success(string $message): void
            {
                $this->successMessage = $message;
            }

            public function error(string $message): void
            {
                $this->errorMessage = $message;
            }
        };

        $service = new Recount($this->app, $this->app->make(ProcessService::class));
        $service->initialize($queue)->execute();

        $this->assertSame('', $queue->errorMessage);
        $this->assertSame('Balance and integral refresh completed', $queue->successMessage);
        $this->assertCount(2, $queue->messages);
        $this->assertSame("Start refreshing user [{$user->getAttr('id')} queue-user] balance and integral", $queue->messages[0]['message']);
        $this->assertSame("Refreshed user [{$user->getAttr('id')} queue-user] balance and integral", $queue->messages[1]['message']);
    }

    protected function defineSchema(): void
    {
        $this->createAccountTables();
        $this->createPaymentBalanceTable();
        $this->createPaymentIntegralTable();
    }

    private function switchPaymentLang(string $langSet): void
    {
        $this->app->lang->switchLangSet($langSet);
        $file = TEST_PROJECT_ROOT . "/plugin/think-plugs-payment/src/lang/{$langSet}.php";
        if (is_file($file)) {
            $this->app->lang->load($file, $langSet);
        }
    }
}
