<?php

declare(strict_types=1);

namespace Chapterjason\WebsocketBundle\Tests\E2E\App\Command;

use Chapterjason\WebsocketBundle\Cluster\ClusterInterface;
use Chapterjason\WebsocketBundle\Cluster\MemberInfo;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('app:members', 'Prints the members as a CLI participant that does not receive, and stays in the cluster for a while')]
final class MembersCommand
{
    public function __construct(
        private readonly ClusterInterface $cluster,
    ) {
    }

    public function __invoke(OutputInterface $output, #[Option] int $stay = 0): int
    {
        $output->writeln(json_encode(array_map(
            static fn (MemberInfo $info): array => ['role' => $info->role->value, 'receives' => $info->receives],
            $this->cluster->getMemberInfo(),
        ), \JSON_THROW_ON_ERROR));

        sleep($stay);

        return Command::SUCCESS;
    }
}
