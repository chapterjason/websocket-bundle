<?php

declare(strict_types=1);

namespace Chapterjason\WebsocketBundle\Tests\Unit\Handshake;

use Chapterjason\WebsocketBundle\Handshake\Handshake;
use Chapterjason\WebsocketBundle\Handshake\HandshakeValueResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class HandshakeValueResolverTest extends TestCase
{
    public function testResolvesTheHandshakeOfTheWorker(): void
    {
        $handshake = new Handshake('a.1');
        $request = new Request();
        $request->attributes->set(Handshake::REQUEST_ATTRIBUTE, $handshake);

        self::assertSame([$handshake], (new HandshakeValueResolver())->resolve($request, self::argument(false)));
    }

    public function testIgnoresTheWebSocketConnectionHeaderOfClients(): void
    {
        $request = new Request(server: ['HTTP_WEBSOCKET_CONNECTION' => 'a.1']);

        $this->expectException(HttpException::class);

        (new HandshakeValueResolver())->resolve($request, self::argument(false));
    }

    public function testRefusesRegularRequestsWithUpgradeRequired(): void
    {
        try {
            (new HandshakeValueResolver())->resolve(new Request(), self::argument(false));
            self::fail('No exception was thrown.');
        } catch (HttpException $exception) {
            self::assertSame(426, $exception->getStatusCode());
            self::assertSame('websocket', $exception->getHeaders()['Upgrade']);
        }
    }

    public function testResolvesNullForNullableArguments(): void
    {
        self::assertSame([null], (new HandshakeValueResolver())->resolve(new Request(), self::argument(true)));
    }

    public function testIgnoresOtherArguments(): void
    {
        self::assertSame([], (new HandshakeValueResolver())->resolve(new Request(), new ArgumentMetadata('request', Request::class, false, false, null)));
    }

    private static function argument(bool $nullable): ArgumentMetadata
    {
        return new ArgumentMetadata('handshake', Handshake::class, false, false, null, $nullable);
    }
}
