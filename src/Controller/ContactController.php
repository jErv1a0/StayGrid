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
            $rating = $request->request->get('rating');

            // Verify reCAPTCHA (if configured)
            $recaptchaResponse = $request->request->get('g-recaptcha-response');
            $recaptchaSecret = $_ENV['RECAPTCHA_SECRET'] ?? $_SERVER['RECAPTCHA_SECRET'] ?? null;
            $recaptchaVerified = true;

            if ($recaptchaSecret) {
                $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';
                $postData = http_build_query([
                    'secret' => $recaptchaSecret,
                    'response' => $recaptchaResponse,
                    'remoteip' => $request->getClientIp(),
                ]);

                $opts = ['http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                    'content' => $postData,
                    'timeout' => 5,
                ]];

                $context = stream_context_create($opts);
                $result = @file_get_contents($verifyUrl, false, $context);
                $decoded = $result ? json_decode($result, true) : null;
                $recaptchaVerified = $decoded['success'] ?? false;
            }

            if (!$recaptchaVerified && $recaptchaSecret) {
                $this->addFlash('error', 'Captcha verification failed. Please try again.');
                return $this->redirectToRoute('app_contact');
            }

            if ($feedbackContent && $name && $email) {
                $feedback = new Feedback();
                $feedback->setContent($feedbackContent);
                $feedback->setName($name);
                $feedback->setEmail($email);
                $feedback->setCreatedAt(new \DateTimeImmutable());
                // Keep new submissions unapproved until an admin reviews them
                $feedback->setApproved(false);

                // Store rating if present and valid (1-5)
                $ratingInt = null;
                if (is_numeric($rating)) {
                    $ratingInt = (int)$rating;
                    if ($ratingInt < 1 || $ratingInt > 5) {
                        $ratingInt = null;
                    }
                }
                $feedback->setRating($ratingInt);

                $entityManager->persist($feedback);
                $entityManager->flush();

                $this->addFlash('success', 'Thank you — your feedback was received and is pending admin approval.');
            }
        }

        // Fallback: still render contact view if it ever posts internally
        // Pass reCAPTCHA site key (if set) to the template
        $recaptchaSiteKey = $_ENV['RECAPTCHA_SITE_KEY'] ?? $_SERVER['RECAPTCHA_SITE_KEY'] ?? null;

        return $this->render('contact/index.html.twig', [
            'controller_name' => 'ContactController',
            'recaptcha_site_key' => $recaptchaSiteKey,
        ]);
    }
}
