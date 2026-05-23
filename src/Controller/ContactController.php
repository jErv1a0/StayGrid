<?php

namespace App\Controller;

use App\Entity\Feedback;
use App\Repository\FeedbackRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact', methods: ['GET', 'POST'])]
    public function index(Request $request, EntityManagerInterface $entityManager, FeedbackRepository $feedbackRepository): Response
    {
        // Handle posted feedback as before
        if ($request->isMethod('POST')) {
            $feedbackContent = $request->request->get('feedback');
            $name = $request->request->get('feedback_name');
            $email = $request->request->get('feedback_email');

            if ($feedbackContent && $name && $email) {
                $feedback = new Feedback();
                $feedback->setContent($feedbackContent);
                $feedback->setName($name);
                $feedback->setEmail($email);
                $feedback->setCreatedAt(new \DateTimeImmutable());

                $entityManager->persist($feedback);
                $entityManager->flush();

                $this->addFlash('success', 'Thank you for your feedback! It will be displayed on our About page.');
            }
        }

        // Fallback: still render contact view if it ever posts internally
        return $this->render('contact/index.html.twig', [
            'controller_name' => 'ContactController',
        ]);
    }
}
