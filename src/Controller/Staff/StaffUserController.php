<?php

namespace App\Controller\Staff;

use App\Repository\LogInUsersRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/staff/users')]
#[IsGranted('ROLE_STAFF')]
class StaffUserController extends AbstractController
{
    #[Route('/', name: 'app_staff_users_index', methods: ['GET'])]
    public function index(LogInUsersRepository $userRepository): Response
    {
        $allUsers = $userRepository->findBy([], ['id' => 'DESC']);

        $clientUsers = array_values(array_filter($allUsers, static function ($user): bool {
            $roles = $user->getRoles();

            // Exclude admin and staff accounts; remaining accounts are treated as clients.
            if (in_array('ROLE_ADMIN', $roles, true) || in_array('ROLE_STAFF', $roles, true)) {
                return false;
            }

            return true;
        }));

        return $this->render('staff/users/index.html.twig', [
            'users' => $clientUsers,
        ]);
    }
}
