<?php

namespace App\Controller\User;

use App\Entity\LogInUsers;
use App\Form\ProfileEditType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/user/profile')]
class UserProfileController extends AbstractController
{
    #[Route('', name: 'app_user_profile', methods: ['GET'])]
    public function index(): Response
    {
        return $this->redirectToRoute('app_user_profile_edit');
    }

    #[Route('/edit', name: 'app_user_profile_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_CLIENT')]
    public function edit(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        SluggerInterface $slugger,
        string $profilePictureDirectory,
    ): Response {
        /** @var LogInUsers|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(ProfileEditType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            }

            $profilePictureFile = $form->get('profilePictureFile')->getData();
            if ($profilePictureFile) {
                $originalFilename = pathinfo($profilePictureFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $profilePictureFile->guessExtension();

                try {
                    $profilePictureFile->move($profilePictureDirectory, $newFilename);
                    $user->setProfilePicture($newFilename);
                } catch (FileException $exception) {
                    $this->addFlash('error', 'Could not upload the profile picture.');

                    return $this->redirectToRoute('app_user_profile_edit');
                }
            }

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Profile updated successfully.');

            return $this->redirectToRoute('app_user_profile_edit');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Profile update failed. Please review the form fields.');
        }

        return $this->render('user/profile/edit.html.twig', [
            'profileForm' => $form->createView(),
        ]);
    }
}