<?php

namespace App\Controller;

use App\Entity\LogInUsers;
use App\Form\RegistrationFormType;
use App\Security\EmailVerifier;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request, 
        UserPasswordHasherInterface $passwordHasher, 
        Security $security, 
        EntityManagerInterface $entityManager
    ): Response 
    {
        // FIX: Use your actual entity
        $user = new LogInUsers();

        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // Hash the password correctly
            $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);

            // Default role for new users
            $user->setRoles(['ROLE_CLIENT']);

            $entityManager->persist($user);
            $entityManager->flush();

            // Auto-login user after registration
            return $security->login($user, 'form_login', 'main');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(
        Request $request, 
        EmailVerifier $emailVerifier,
        TranslatorInterface $translator
    ): Response 
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        try {
            /** @var LogInUsers $user */
            $user = $this->getUser();
            $emailVerifier->handleEmailConfirmation($request, $user);

        } catch (VerifyEmailExceptionInterface $exception) {

            $this->addFlash(
                'verify_email_error', 
                $translator->trans($exception->getReason(), [], 'VerifyEmailBundle')
            );

            return $this->redirectToRoute('app_login');
        }

        $this->addFlash('success', 'Your email has been verified!');

        return $this->redirectToRoute('app_landing');
    }

    #[Route('/connect/google', name: 'connect_google_start')]
    #[Route('/api/auth/google', name: 'api_auth_google', methods: ['GET'])]
    public function connectGoogle(ClientRegistry $clientRegistry): RedirectResponse
    {
        $client = $clientRegistry->getClient('google');
        $client->setAsStateless();

        // Force account chooser so users can pick a Google account
        return $client->redirect(['email', 'profile'], ['prompt' => 'select_account']);
    }

    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectGoogleCheck(): void
    {
        // handled by Symfony
    }
}