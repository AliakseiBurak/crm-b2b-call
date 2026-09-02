<?php

namespace App\Tests\Functional\Command;

use App\Tests\DatabaseWebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Фоновая команда app:campaign:send: wiring LockFactory и пустой прогон.
 */
final class CampaignSendCommandTest extends DatabaseWebTestCase
{
    public function testIdleRunSucceeds(): void
    {
        $application = new Application($this->client->getKernel());
        $command = $application->find('app:campaign:send');
        $tester = new CommandTester($command);

        self::assertSame(Command::SUCCESS, $tester->execute([]));
    }
}
