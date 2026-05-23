<?php

namespace App\Controller\Debug;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class WhoAmIController extends AbstractController
{
    #[Route('/whoami', name: 'app_whoami', methods: ['GET'])]
    public function whoami(Request $request, TokenStorageInterface $tokenStorage): JsonResponse
    {
        $token = $tokenStorage->getToken();
        $user = $token ? $token->getUser() : null;

        $userRoles = [];
        if (is_object($user) && method_exists($user, 'getRoles')) {
            $userRoles = $user->getRoles();
        }

        $tokenRoles = [];
        if ($token && method_exists($token, 'getRoleNames')) {
            $tokenRoles = $token->getRoleNames();
        }

        $session = $request->getSession();
        $targetMain = $session->get('_security_main.target_path');
        $targetAdmin = $session->get('_security_admin.target_path');

        return $this->json([
            'user_id' => is_object($user) && method_exists($user, 'getId') ? $user->getId() : null,
            'user_email' => is_object($user) && method_exists($user, 'getUserIdentifier') ? $user->getUserIdentifier() : null,
            'user_roles' => $userRoles,
            'token_roles' => $tokenRoles,
            'target_path_main' => $targetMain,
            'target_path_admin' => $targetAdmin,
        ]);
    }
}
