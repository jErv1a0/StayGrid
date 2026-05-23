<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class BookController extends AbstractController
{
    #[Route('/book', name: 'app_booking')]
    public function book(): Response
    {
        return $this->render('booking/book.html.twig', [
            'controller_name' => 'BookController',
        ]);
    }
}



