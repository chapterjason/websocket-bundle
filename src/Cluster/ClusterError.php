<?php

declare(strict_types=1);

namespace Chapterjason\WebsocketBundle\Cluster;

enum ClusterError
{
    case NotJoined;
    case InvalidArgument;
    case BusFailed;
    case Timeout;
    case NoReceiver;

    public static function fromCode(int $code): self
    {
        return match ($code) {
            \CLUSTER_ERROR_NOT_JOINED => self::NotJoined,
            \CLUSTER_ERROR_INVALID_ARGUMENT => self::InvalidArgument,
            \CLUSTER_ERROR_BUS_FAILED => self::BusFailed,
            \CLUSTER_ERROR_TIMEOUT => self::Timeout,
            \CLUSTER_ERROR_NO_RECEIVER => self::NoReceiver,
            default => throw new \UnexpectedValueException(\sprintf('Unknown cluster error code %d.', $code)),
        };
    }
}
