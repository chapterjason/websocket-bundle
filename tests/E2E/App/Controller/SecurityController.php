<?php

declare(strict_types=1);

namespace Chapterjason\WebsocketBundle\Tests\E2E\App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class SecurityController
{
    #[Route('/login', methods: ['POST'])]
    public function login(#[CurrentUser] UserInterface $user): Response
    {
        return new Response($user->getUserIdentifier());
    }
}
