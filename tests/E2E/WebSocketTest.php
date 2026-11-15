<?php

declare(strict_types=1);

namespace Chapterjason\WebsocketBundle\Tests\E2E;

use WebSocket\Exception\HandshakeException;
use WebSocket\Message\Binary;
use WebSocket\Message\Close;
use WebSocket\Message\Ping;
use WebSocket\Message\Pong;
use WebSocket\Middleware\CompressionExtension;
use WebSocket\Middleware\CompressionExtension\DeflateCompressor;

final class WebSocketTest extends E2ETestCase
{
    public function testRequestThatIsNoHandshakeGetsUpgradeRequired(): void
    {
        $response = self::http('a')->request('GET', '/chat', ['headers' => ['Cookie' => self::login('a', 'alice')]]);

        self::assertSame(426, $response->getStatusCode());
        self::assertSame(['websocket'], $response->getHeaders(false)['upgrade']);
    }

    public function testFirewallRefusesHandshakesWithoutLogin(): void
    {
        try {
            self::connect('a');
            self::fail('The handshake was accepted.');
        } catch (HandshakeException $exception) {
            self::assertSame(401, $exception->getResponse()?->getStatusCode());
        }
    }

    public function testFirewallRefusesWrongCredentials(): void
    {
        self::assertSame(401, self::http('a')->request('POST', '/login', ['json' => ['username' => 'alice', 'password' => 'wrong']])->getStatusCode());
    }

    public function testWelcomeCarriesTheStateOfTheConnection(): void
    {
        $client = self::connect('a', ['Cookie' => self::login('a', 'alice'), 'Sec-WebSocket-Protocol' => 'other, chat.v1']);
        $welcome = self::receive($client);

        self::assertSame('chat.v1', $client->getHandshakeResponse()?->getHeaderLine('Sec-WebSocket-Protocol'));
        self::assertSame('welcome', $welcome['type']);
        self::assertIsString($welcome['connection']);
        self::assertStringStartsWith(self::instanceId('a').'.', $welcome['connection']);
        self::assertSame('alice', $welcome['name']);
        self::assertSame(['chat'], $welcome['tags']);
        self::assertSame('/chat', $welcome['uri']);
        self::assertSame(self::instanceId('a'), $welcome['member']);

        $client->close();
    }

    public function testMessagesReachEveryoneInTheChat(): void
    {
        [$alice] = self::join('a', 'alice');
        [$bob] = self::join('a', 'bob');

        $alice->text(json_encode(['text' => 'hello'], \JSON_THROW_ON_ERROR));

        self::assertSame(['type' => 'said', 'name' => 'alice', 'text' => 'hello'], self::receive($alice));
        self::assertSame(['type' => 'said', 'name' => 'alice', 'text' => 'hello'], self::receive($bob));

        $alice->close();
        $bob->close();
    }

    public function testBinaryMessagesAreAnswered(): void
    {
        [$alice] = self::join('a', 'alice');

        $alice->binary("\x00\x01\x02");

        $received = self::receive($alice);

        self::assertSame('received', $received['type']);
        self::assertSame(3, $received['bytes']);

        $alice->close();
    }

    public function testMessagesAreCompressedWhenTheHandshakeAcceptsIt(): void
    {
        $client = self::connect('a', ['Cookie' => self::login('a', 'alice')]);
        $client->addMiddleware(new CompressionExtension(new DeflateCompressor()));
        $client->addMiddleware($recorder = new CompressionRecorder());
        $client->connect();

        self::assertStringStartsWith('permessage-deflate', $client->getHandshakeResponse()?->getHeaderLine('Sec-WebSocket-Extensions') ?? '');

        $client->receive();

        $client->text(json_encode(['text' => str_repeat('compressed ', 100)], \JSON_THROW_ON_ERROR));
        self::assertSame(['type' => 'said', 'name' => 'alice', 'text' => str_repeat('compressed ', 100)], self::receive($client));
        self::assertTrue(end($recorder->compressed));

        $client->close();
    }

    public function testWorkerRestartsAfterWorkerLoopMaxWithoutDroppingConnections(): void
    {
        [$alice] = self::join('a', 'alice');
        $workers = [];

        for ($i = 0; $i < 12; ++$i) {
            $alice->binary('ping');
            $received = self::receive($alice);
            self::assertSame(4, $received['bytes']);
            self::assertIsString($received['worker']);
            $workers[] = $received['worker'];
        }

        self::assertGreaterThan(1, \count(array_unique($workers)));

        $alice->close();
    }

    public function testFailingListenerClosesTheConnectionWithInternalError(): void
    {
        [$alice] = self::join('a', 'alice');

        $alice->text('not json');
        $message = $alice->receive();

        self::assertInstanceOf(Close::class, $message);
        self::assertSame(1011, $message->getCloseStatus());
    }

    public function testLeavingIsAnnounced(): void
    {
        [$alice] = self::join('a', 'alice');
        [$bob] = self::join('a', 'bob');

        $bob->close(4000, 'bye');

        self::assertSame(['type' => 'left', 'name' => 'bob', 'code' => 4000, 'reason' => 'bye'], self::receive($alice));

        $alice->close();
    }

    public function testPingsAndPongsReachTheListeners(): void
    {
        [$alice, $connection] = self::join('a', 'alice');

        $alice->ping('from client');
        self::assertInstanceOf(Pong::class, $alice->receive());
        self::assertSame(['type' => 'pinged', 'data' => 'from client'], self::receive($alice));

        self::http('a')->request('POST', '/connections/'.$connection.'/pings', ['body' => 'from server'])->getContent();
        self::assertInstanceOf(Ping::class, $alice->receive());
        self::assertSame(['type' => 'ponged', 'data' => 'from server'], self::receive($alice));

        $alice->close();
    }

    public function testRequestsPushToConnections(): void
    {
        [$alice, $connection] = self::join('a', 'alice');
        [$bob] = self::join('a', 'bob');
        $http = self::http('a');

        $http->request('POST', '/connections/'.$connection.'/messages', ['body' => 'only alice'])->getContent();
        self::assertSame('only alice', $alice->receive()->getContent());

        $http->request('POST', '/connections/'.$connection.'/messages', ['body' => "\x00", 'headers' => ['Content-Type' => 'application/octet-stream']])->getContent();
        self::assertInstanceOf(Binary::class, $alice->receive());

        self::assertGreaterThanOrEqual(2, $http->request('POST', '/announcements', ['body' => 'chat only'])->toArray()['delivered']);
        self::assertSame('chat only', $alice->receive()->getContent());
        self::assertSame('chat only', $bob->receive()->getContent());

        self::assertGreaterThanOrEqual(2, $http->request('POST', '/broadcasts', ['body' => 'everyone'])->toArray()['delivered']);
        self::assertSame('everyone', $alice->receive()->getContent());
        self::assertSame('everyone', $bob->receive()->getContent());

        $http->request('DELETE', '/connections/'.$connection)->getContent();
        $message = $alice->receive();
        self::assertInstanceOf(Close::class, $message);
        self::assertSame(4001, $message->getCloseStatus());
        self::assertSame(['type' => 'left', 'name' => 'alice', 'code' => 4001, 'reason' => 'kicked'], self::receive($bob));

        $bob->close();
    }

    public function testRequestsManageConnections(): void
    {
        [$alice, $connection] = self::join('a', 'alice');
        $http = self::http('a');
        $path = '/connections/'.$connection;

        self::assertContains($connection, $http->request('GET', '/connections')->toArray());
        self::assertContains($connection, $http->request('GET', '/tags/chat/connections')->toArray());
        $a = self::instanceId('a');

        self::assertSame(['instance' => $a, 'attributes' => ['name' => 'alice'], 'tags' => ['chat']], $http->request('GET', $path)->toArray());

        $http->request('PUT', $path.'/attributes/room', ['body' => 'lobby'])->getContent();
        $http->request('DELETE', $path.'/attributes/name')->getContent();
        $http->request('DELETE', $path.'/tags/chat')->getContent();

        self::assertSame(['instance' => $a, 'attributes' => ['room' => 'lobby'], 'tags' => []], $http->request('GET', $path)->toArray());
        self::assertNotContains($connection, $http->request('GET', '/tags/chat/connections')->toArray());

        $alice->close();
    }

    public function testUnknownConnectionIsNotFound(): void
    {
        [$alice, $connection] = self::join('a', 'alice');
        $unknown = '/connections/'.self::instanceId('a').'.unknown';

        self::assertSame(404, self::http('a')->request('GET', $unknown)->getStatusCode());
        self::assertSame(404, self::http('a')->request('POST', $unknown.'/messages', ['body' => 'nobody'])->getStatusCode());

        $alice->close();
    }

    public function testMembersReachConnectionsOnOtherMembers(): void
    {
        [$alice, $connection] = self::join('a', 'alice');
        $http = self::http('b');

        $http->request('POST', '/connections/'.$connection.'/messages', ['body' => 'from b'])->getContent();
        self::assertSame('from b', $alice->receive()->getContent());

        self::assertSame(['instance' => self::instanceId('a'), 'attributes' => ['name' => 'alice'], 'tags' => ['chat']], $http->request('GET', '/connections/'.$connection)->toArray());
        self::assertContains($connection, $http->request('GET', '/tags/chat/connections')->toArray());

        $alice->close();
    }
}
