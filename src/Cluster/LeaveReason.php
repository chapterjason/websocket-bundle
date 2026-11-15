<?php

declare(strict_types=1);

namespace Chapterjason\WebsocketBundle\Cluster;

enum LeaveReason: string
{
    /** The member said goodbye: a server that stopped or reloaded, a CLI participant that ended. */
    case Leave = 'leave';

    /** The member fell silent for three presence intervals: crashed, killed or cut off. */
    case Timeout = 'timeout';
}
