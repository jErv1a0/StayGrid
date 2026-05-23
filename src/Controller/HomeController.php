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
        // Home should show public landing page for guests.
        // Authenticated users remain on landing (or you can change this to dashboards). 
        return $this->render('landingpage/landing.html.twig', []);
    }
    
}