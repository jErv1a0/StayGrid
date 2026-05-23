<?php

namespace App\Controller\Admin;

use Doctrine\ORM\EntityNotFoundException;
use App\Repository\ActivityLogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class ActivityLogController extends AbstractController
{
    #[Route('/admin/activity-logs', name: 'app_admin_activity_logs_index', methods: ['GET'])]
    public function index(ActivityLogRepository $repo, EntityManagerInterface $em): Response
    {
        $logs = $repo->findRecent(100);

        // If ActivityLog is empty but user_activity has data, import it for display.
        if (empty($logs)) {
            $userActivities = $em->getRepository(\App\Entity\UserActivity::class)->findBy([], ['createdAt' => 'DESC'], 100);

            if (!empty($userActivities)) {
                $conn = $em->getConnection();

                foreach ($userActivities as $ua) {
                    $actorId = null;
                    $actorEmail = null;
                    try {
                        if ($user = $ua->getUser()) {
                            $actorId = $user->getId();
                            $actorEmail = $user->getEmail();
                        }
                    } catch (EntityNotFoundException $e) {
                        // User associated with the activity was likely deleted.
                        // We can log this or just proceed with null actor details.
                    }

                    $conn->insert('activity_log', [
                        'action' => strtolower($ua->getAction()),
                        'actor_type' => \App\Entity\LogInUsers::class,
                        'actor_id' => $actorId,
                        'actor_email' => $actorEmail,
                        'target_type' => null,
                        'target_id' => null,
                        'details' => json_encode([
                            'source' => 'user_activity',
                            'ip' => $ua->getIp(),
                            'user_agent' => $ua->getUserAgent(),
                        ]),
                        'created_at' => $ua->getCreatedAt()->format('Y-m-d H:i:s'),
                    ]);
                }

                $logs = $repo->findRecent(100);
            }
        }

        return $this->render('admin/activity_log/index.html.twig', [
            'logs' => $logs,
            'page_title' => 'Activity Log',
        ]);
    }
}
