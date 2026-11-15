<?php

declare(strict_types=1);

namespace Chapterjason\WebsocketBundle\Tests\Integration;

use Chapterjason\WebsocketBundle\ChapterjasonWebsocketBundle;
use Chapterjason\WebsocketBundle\Cluster\Cluster;
use Chapterjason\WebsocketBundle\Cluster\ClusterReceiver;
use Chapterjason\WebsocketBundle\Runtime\ClusterWorkerRunner;
use Chapterjason\WebsocketBundle\Runtime\WebSocketWorkerRunner;
use Chapterjason\WebsocketBundle\Tests\Integration\Fixtures\ServiceConsumer;
use Chapterjason\WebsocketBundle\WebSocket\WebSocket;
use Chapterjason\WebsocketBundle\Worker\ClusterEventHandler;
use Chapterjason\WebsocketBundle\Worker\WebSocketEventHandler;
use Nyholm\BundleTest\TestKernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\KernelInterface;

final class BundleInitializationTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    /**
     * @param array<string, mixed> $options
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        /** @var TestKernel $kernel */
        $kernel = parent::createKernel($options);
        $kernel->addTestBundle(ChapterjasonWebsocketBundle::class);
        $kernel->handleOptions($options);

        return $kernel;
    }

    public function testAutowiresTheServicesByTheirInterfaces(): void
    {
        $kernel = self::bootKernel(['config' => static function (TestKernel $kernel): void {
            $kernel->addTestConfig(static function (ContainerBuilder $container): void {
                $container->autowire(ServiceConsumer::class)->setPublic(true);
            });
        }]);

        $consumer = $kernel->getContainer()->get(ServiceConsumer::class);

        self::assertInstanceOf(ServiceConsumer::class, $consumer);
        self::assertInstanceOf(WebSocket::class, $consumer->webSocket);
        self::assertInstanceOf(Cluster::class, $consumer->cluster);
        self::assertInstanceOf(ClusterReceiver::class, $consumer->clusterReceiver);
    }

    public function testExposesTheWorkerHandlersToTheRunners(): void
    {
        $kernel = self::bootKernel();

        self::assertInstanceOf(WebSocketEventHandler::class, $kernel->getContainer()->get(WebSocketWorkerRunner::HANDLER_SERVICE));
        self::assertInstanceOf(ClusterEventHandler::class, $kernel->getContainer()->get(ClusterWorkerRunner::HANDLER_SERVICE));
    }

    public function testKeepsTheServicesForApplicationsPrivate(): void
    {
        $kernel = self::bootKernel();

        self::assertFalse($kernel->getContainer()->has('chapterjason_websocket.websocket'));
        self::assertFalse($kernel->getContainer()->has('chapterjason_websocket.cluster'));
        self::assertFalse($kernel->getContainer()->has('chapterjason_websocket.cluster_receiver'));
    }
}
