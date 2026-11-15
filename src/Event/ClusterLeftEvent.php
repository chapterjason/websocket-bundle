<?php

declare(strict_types=1);

namespace Chapterjason\WebsocketBundle\Event;

use Chapterjason\WebsocketBundle\Cluster\LeaveReason;
use Chapterjason\WebsocketBundle\Cluster\MemberRole;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * A member left the cluster; reported once, to the cluster worker of one server.
 *
 * After a network split, both sides may report the same member: keep what a listener does safe to repeat.
 * A server that timed out ran no CloseEvent for its connections.
 */
final class ClusterLeftEvent extends Event
{
    public function __construct(
        private readonly string $instance,
        private readonly LeaveReason $reason,
        private readonly MemberRole $role,
    ) {
    }

    /**
     * The instance ID of the member that left.
     */
    public function getInstance(): string
    {
        return $this->instance;
    }

    public function getReason(): LeaveReason
    {
        return $this->reason;
    }

    public function getRole(): MemberRole
    {
        return $this->role;
    }
}
