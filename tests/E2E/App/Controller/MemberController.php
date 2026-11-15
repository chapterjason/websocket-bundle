<?php

declare(strict_types=1);

namespace Chapterjason\WebsocketBundle\Tests\E2E\App\Controller;

use Chapterjason\WebsocketBundle\Cluster\ClusterInterface;
use Chapterjason\WebsocketBundle\Cluster\MemberInfo;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MemberController
{
    public function __construct(
        private readonly ClusterInterface $cluster,
    ) {
    }

    #[Route('/health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        $members = $this->cluster->getMembers();

        return new JsonResponse(
            ['instance' => $this->cluster->getInstanceId(), 'members' => $members],
            [] === $members ? Response::HTTP_SERVICE_UNAVAILABLE : Response::HTTP_OK,
        );
    }

    #[Route('/members', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return new JsonResponse(array_map(
            static fn (MemberInfo $info): array => ['role' => $info->role->value, 'receives' => $info->receives],
            $this->cluster->getMemberInfo(),
        ));
    }

    #[Route('/members/{instance}/requests', methods: ['POST'])]
    public function request(Request $request, string $instance): Response
    {
        return new Response($this->cluster->request($instance, $request->getContent()));
    }

    #[Route('/members/{instance}/messages', methods: ['POST'])]
    public function send(Request $request, string $instance): Response
    {
        $this->cluster->send($instance, $request->getContent());

        return new Response(null, Response::HTTP_ACCEPTED);
    }

    #[Route('/members/messages', methods: ['POST'])]
    public function publish(Request $request): JsonResponse
    {
        return new JsonResponse(['delivered' => $this->cluster->publish($request->getContent())]);
    }
}
