<?php

namespace App\Controller\User;

use App\Entity\Feedback;
use App\Entity\LogInUsers;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user/feedback')]
#[IsGranted('ROLE_CLIENT')]
class UserFeedbackController extends AbstractController
{
    #[Route('', name: 'app_user_feedback', methods: ['GET', 'POST'])]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var LogInUsers|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            $feedbackContent = trim((string) $request->request->get('feedback'));
            $rating = $request->request->get('rating');

            if ($feedbackContent !== '') {
                $feedback = new Feedback();
                $feedback->setContent($feedbackContent);
                $feedback->setName((string) ($user->getFullName() ?: $user->getEmail()));
                $feedback->setEmail((string) $user->getEmail());
                $feedback->setCreatedAt(new \DateTimeImmutable());
                $feedback->setApproved(false);

                $ratingInt = null;
                if (is_numeric($rating)) {
                    $ratingInt = (int) $rating;
                    if ($ratingInt < 1 || $ratingInt > 5) {
                        $ratingInt = null;
                    }
                }
                $feedback->setRating($ratingInt);

                $entityManager->persist($feedback);
                $entityManager->flush();

                $this->addFlash('success', 'Your feedback was submitted and is pending admin approval.');
                return $this->redirectToRoute('app_user_feedback');
            }

            $this->addFlash('error', 'Please write some feedback before submitting.');
        }

        return $this->render('user/feedback/index.html.twig', [
            'current_user' => $user,
            'is_verified' => $user->isVerified(),
            'page_title' => 'User Feedback',
        ]);
    }
}
