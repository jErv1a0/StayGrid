<?php

namespace App\Controller;

use App\Repository\FeedbackRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

final class AboutController extends AbstractController
{
    #[Route('/about', name: 'app_about')]
    public function index(FeedbackRepository $feedbackRepository): Response
    {
        $feedbacks = [];

        try {
            $feedbacks = $feedbackRepository->findBy([], ['createdAt' => 'DESC']);
        } catch (Throwable) {
            // Keep the about page available even if feedback storage is not ready yet.
        }

        return $this->render('about/index.html.twig', [
            'feedbacks' => $feedbacks,
        ]);
    }
}