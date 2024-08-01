<?php

namespace think\admin\tests;

use PHPUnit\Framework\TestCase;
use plugin\account\service\Account;
use plugin\payment\service\Payment;

class PaymentTest extends TestCase
{
    public function testGetTypes()
    {
        $all = Payment::types();
        $this->assertNotEmpty($all);
    }

    public function testGetTypesByChannel()
    {
        $all = Payment::typesByAccess(Account::WXAPP);
        $this->assertNotEmpty($all);
    }
}