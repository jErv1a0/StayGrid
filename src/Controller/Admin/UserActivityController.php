<?php

namespace App\Controller\Admin;

use App\Repository\UserActivityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Entity\UserActivity;

#[IsGranted('ROLE_ADMIN')] // Ensure only admins can see this
#[Route('/admin/user-activity')]
class UserActivityController extends AbstractController
{
    #[Route('/', name: 'app_admin_user_activity_index', methods: ['GET'])]
    public function index(UserActivityRepository $repo): Response
    {
        // Fetch logs as scalar rows to avoid exceptions from stale user references.
        $activities = $repo->findRecentForAdminList(200);

        return $this->render('admin/user_activity/index.html.twig', [
            'activities' => $activities,
        ]);
    }

    // Retaining the debug route to test logging in isolation
    #[Route('/debug/create', name: 'app_admin_user_activity_debug', methods: ['GET'])]
    public function debugCreate(Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $user = $this->getUser();
        // If the user is not a LogInUsers entity (e.g., anonymous or from another provider), skip.
        if (!$user || !$user instanceof \App\Entity\LogInUsers) {
            $this->addFlash('warning', 'No current LogInUsers user to attach debug activity to.');
            return $this->redirectToRoute('app_admin_user_activity_index');
        }

        $activity = new UserActivity();
        $activity->setUser($user);
        $activity->setAction('debug_check');
        $activity->setIp($request->getClientIp());
        $activity->setUserAgent($request->headers->get('User-Agent'));

        $em->persist($activity);
        $em->flush();

        $this->addFlash('success', 'DEBUG: Log entry created successfully.');
        return $this->redirectToRoute('app_admin_user_activity_index');
    }
}