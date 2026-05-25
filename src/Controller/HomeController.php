<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController

{
   
    #[Route('/', name: 'app_home')]
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

        return $this->render('landingpage/landing.html.twig', []);
    }
    
}