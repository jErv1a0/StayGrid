<?php

namespace App\Controller\Admin;

use App\Entity\Feedback;
use App\Repository\FeedbackRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/feedback', name: 'app_admin_feedback_')]
class FeedbackController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(FeedbackRepository $repo): Response
    {
        $pending = $repo->findPending(200);

        return $this->render('admin/feedback/index.html.twig', [
            'pending' => $pending,
            'page_title' => 'Pending Feedback',
        ]);
    }

    #[Route('/{id}/approve', name: 'approve', methods: ['POST'])]
    public function approve(Feedback $feedback, EntityManagerInterface $em, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('approve-feedback-'.$feedback->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_admin_feedback_index');
        }

        $feedback->setApproved(true);
        $feedback->setApprovedAt(new \DateTimeImmutable());
        $feedback->setApprovedBy($this->getUser());

        $em->persist($feedback);
        $em->flush();

        $this->addFlash('success', 'Feedback approved and will appear on the About page.');
        return $this->redirectToRoute('app_admin_feedback_index');
    }
}
