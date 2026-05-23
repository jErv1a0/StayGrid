<?php

namespace App\Security;

use App\Entity\LogInUsers;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class EmailVerifier
{
    public function __construct(
        private VerifyEmailHelperInterface $verifyEmailHelper,
        private MailerInterface $mailer,
        private EntityManagerInterface $entityManager
    ) {}

    public function sendEmailConfirmation(string $verifyEmailRouteName, LogInUsers $user): void
    {
        $signatureComponents = $this->verifyEmailHelper->generateSignature(
            $verifyEmailRouteName,
            (string) $user->getId(),
            (string) $user->getEmail(),
            ['id' => $user->getId()]
        );

        $signedUrl = $signatureComponents->getSignedUrl();

        $email = (new Email())
            ->from('alvrcoqviermv05@gmail.com')
            ->to($user->getEmail())
            ->subject('Verify your Email')
            ->text(
                "Hello!\n\n" .
                "Click the link below to verify your email:\n\n" .
                $signedUrl . "\n\n" .
                "This link will expire soon.\n\n" .
                "Thank you!"
            );

        $this->mailer->send($email);
    }

    public function handleEmailConfirmation(Request $request, LogInUsers $user): void
    {
        $this->verifyEmailHelper->validateEmailConfirmationFromRequest(
            $request,
            (string) $user->getId(),
            (string) $user->getEmail()
        );

        $user->setIsVerified(true);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}