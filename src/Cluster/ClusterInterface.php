<?php

declare(strict_types=1);

namespace Chapterjason\WebsocketBundle\Cluster;

use Chapterjason\WebsocketBundle\Exception\ClusterException;

/**
 * Talks to the other members of the cluster: the cluster workers of servers, and CLI participants.
 *
 * Instance IDs change when a member reloads its configuration.
 *
 * @throws ClusterException from every method when the extension reports an error
 */
interface ClusterInterface
{
    /**
     * The instance ID of this member.
     */
    public function getInstanceId(): string;

    /**
     * @return list<string> the instance IDs of the other members known to be alive, CLI participants included
     */
    public function getMembers(): array;

    /**
     * @return array<string, MemberInfo> the other members known to be alive, by instance ID
     */
    public function getMemberInfo(): array;

    /**
     * Fire and forget: succeeds once the data is queued for the member, or when the member is gone.
     *
     * Fails with ClusterError::NoReceiver for a member known not to receive.
     */
    public function send(string $instance, string $data): void;

    /**
     * Sends the data to all other members that receive.
     *
     * @return int the number of members the data was queued for
     */
    public function publish(string $data): int;

    /**
     * Sends the data to the member and waits for its answer; fails at once with ClusterError::NoReceiver for a member that does not receive.
     *
     * @param int $timeout in milliseconds; 0 waits the query_timeout of the server
     */
    public function request(string $instance, string $data, int $timeout = 0): string;
}
