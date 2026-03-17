<?php

declare(strict_types=1);

use plugin\helper\service\PhinxExtend;
use think\migration\Migrator;

@set_time_limit(0);
@ini_set('memory_limit', '-1');

class InstallCenter20241010 extends Migrator
{
    public function getName(): string
    {
        return 'CenterPlugin';
    }

    public function change(): void
    {
        // no tables
    }
}