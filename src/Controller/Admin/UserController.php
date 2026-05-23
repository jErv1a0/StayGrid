<?php

namespace App\Controller\Admin;

use App\Entity\LogInUsers;
use App\Repository\LogInUsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/users')]
class UserController extends AbstractController
{
    #[Route('/', name: 'app_admin_users_index')]
    public function index(LogInUsersRepository $userRepository): Response
    {
        // Option A (simple): show all users
        $users = $userRepository->findAll();

        // Option B (if you have isVerified field)
        // $users = $userRepository->findBy(['isVerified' => true]);

        return $this->render('admin/users/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/{id}/verify', name: 'app_admin_users_verify', methods: ['POST'])]
    public function verify(
        LogInUsers $user,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid('verify_user_' . $user->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $user->setIsVerified(true);
        $entityManager->flush();

        $this->addFlash('success', sprintf('User %s has been verified.', $user->getEmail()));

        return $this->redirectToRoute('app_admin_users_index');
    }

    #[Route('/{id}/unverify', name: 'app_admin_users_unverify', methods: ['POST'])]
    public function unverify(
        LogInUsers $user,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid('unverify_user_' . $user->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $user->setIsVerified(false);
        $entityManager->flush();

        $this->addFlash('success', sprintf('User %s has been marked unverified.', $user->getEmail()));

        return $this->redirectToRoute('app_admin_users_index');
    }
}
