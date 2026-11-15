<?php

declare(strict_types=1);

namespace Chapterjason\WebsocketBundle\EventListener;

use Chapterjason\WebsocketBundle\Cluster\ClusterError;
use Chapterjason\WebsocketBundle\Exception\ClusterException;
use Chapterjason\WebsocketBundle\Exception\WebSocketException;
use Chapterjason\WebsocketBundle\WebSocket\WebSocketError;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Maps the errors of the extension that escape a controller to HTTP errors.
 */
final class ExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $statusCode = match (true) {
            $exception instanceof WebSocketException => match ($exception->getError()) {
                WebSocketError::NotFound => Response::HTTP_NOT_FOUND,
                WebSocketError::NotOpen => Response::HTTP_CONFLICT,
                WebSocketError::InvalidArgument => Response::HTTP_BAD_REQUEST,
                WebSocketError::WriteFailed => Response::HTTP_BAD_GATEWAY,
                WebSocketError::BusFailed => Response::HTTP_SERVICE_UNAVAILABLE,
                WebSocketError::Timeout => Response::HTTP_GATEWAY_TIMEOUT,
            },
            $exception instanceof ClusterException => match ($exception->getError()) {
                ClusterError::NotJoined, ClusterError::BusFailed, ClusterError::NoReceiver => Response::HTTP_SERVICE_UNAVAILABLE,
                ClusterError::InvalidArgument => Response::HTTP_BAD_REQUEST,
                ClusterError::Timeout => Response::HTTP_GATEWAY_TIMEOUT,
            },
            default => null,
        };

        if (null !== $statusCode) {
            $event->setThrowable(new HttpException($statusCode, $exception->getMessage(), $exception));
        }
    }
}
