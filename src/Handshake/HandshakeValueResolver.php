<?php

declare(strict_types=1);

namespace Chapterjason\WebsocketBundle\Handshake;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Resolves a Handshake argument; a request that is no handshake gets a 426, or null for a nullable argument.
 */
final class HandshakeValueResolver implements ValueResolverInterface
{
    /**
     * @return iterable<Handshake|null>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (Handshake::class !== $argument->getType()) {
            return [];
        }

        $handshake = $request->attributes->get(Handshake::REQUEST_ATTRIBUTE);

        if ($handshake instanceof Handshake) {
            return [$handshake];
        }

        if ($argument->isNullable()) {
            return [null];
        }

        throw new HttpException(Response::HTTP_UPGRADE_REQUIRED, 'This endpoint only accepts WebSocket handshakes.', headers: ['Upgrade' => 'websocket', 'Connection' => 'Upgrade']);
    }
}
