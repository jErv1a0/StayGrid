<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LandingController extends AbstractController
{
    #[Route('/landing', name: 'app_landing')]
    public function index(): Response
    {
        if ($this->getUser()) {
            if ($this->isGranted('ROLE_ADMIN')) {
                return $this->redirectToRoute('app_admin_home');
            }

            if ($this->isGranted('ROLE_STAFF')) {
                return $this->redirectToRoute('app_staff_dashboard');
            }

            return $this->redirectToRoute('app_user_dashboard');
        }

        return $this->render('landingpage/landing.html.twig');
    }
}
