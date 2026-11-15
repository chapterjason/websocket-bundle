<?php

declare(strict_types=1);

namespace Chapterjason\WebsocketBundle\Tests\E2E\App\Command;

use Chapterjason\WebsocketBundle\Cluster\ClusterReceiverInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;

#[AsCommand('app:receive', 'Receives messages as a CLI participant until the limit, or forever')]
final class ReceiveCommand
{
    public function __construct(
        private readonly ClusterReceiverInterface $receiver,
    ) {
    }

    public function __invoke(#[Option] ?int $limit = null): int
    {
        for ($received = 0; null === $limit || $received < $limit;) {
            if ($this->receiver->receive(1000)) {
                ++$received;
            }
        }

        return Command::SUCCESS;
    }
}
