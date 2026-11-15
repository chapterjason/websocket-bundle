<?php

declare(strict_types=1);

namespace Chapterjason\WebsocketBundle\Tests\E2E;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use WebSocket\Client;
use WebSocket\Middleware\CloseHandler;
use WebSocket\Middleware\PingResponder;

/**
 * Talks to the members "a" and "b" of compose.yaml as clients do.
 */
abstract class E2ETestCase extends TestCase
{
    protected static function http(string $member): HttpClientInterface
    {
        return HttpClient::createForBaseUri(\sprintf('http://%s:8080', $member));
    }

    /**
     * Logs the user in with the firewall and returns the session cookie.
     */
    protected static function login(string $member, string $user, ?string $password = null): string
    {
        $response = self::http($member)->request('POST', '/login', ['json' => ['username' => $user, 'password' => $password ?? $user.'-password']]);
        $cookie = Cookie::fromString($response->getHeaders()['set-cookie'][0]);

        return $cookie->getName().'='.$cookie->getValue();
    }

    /**
     * @param array<string, string> $headers
     * @param int                   $timeout in seconds, for every operation of the connection
     */
    protected static function connect(string $member, array $headers = [], int $timeout = 5): Client
    {
        $client = new Client(\sprintf('ws://%s:8080/chat', $member));
        $client->setTimeout($timeout);
        $client->addMiddleware(new CloseHandler());
        $client->addMiddleware(new PingResponder());

        foreach ($headers as $header => $value) {
            $client->addHeader($header, $value);
        }

        $client->connect();

        return $client;
    }

    /**
     * Logs in, connects and returns the client with the ID of its connection.
     *
     * @param int $timeout in seconds, for every operation of the connection
     *
     * @return array{Client, string}
     */
    protected static function join(string $member, string $user, int $timeout = 5): array
    {
        $client = self::connect($member, ['Cookie' => self::login($member, $user)], $timeout);
        $welcome = self::receive($client);

        self::assertSame('welcome', $welcome['type'] ?? null);
        self::assertIsString($welcome['connection'] ?? null);

        return [$client, $welcome['connection']];
    }

    /**
     * @return array<mixed>
     */
    protected static function receive(Client $client): array
    {
        $message = json_decode($client->receive()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($message);

        return $message;
    }

    /**
     * Receives until a message of the type arrives, skipping the chat traffic of other tests.
     *
     * @return array<mixed>
     */
    protected static function receiveType(Client $client, string $type): array
    {
        do {
            $message = self::receive($client);
        } while ($type !== ($message['type'] ?? null));

        return $message;
    }

    protected static function instanceId(string $member): string
    {
        $instance = self::http($member)->request('GET', '/health')->toArray()['instance'] ?? null;
        self::assertIsString($instance);

        return $instance;
    }
}
