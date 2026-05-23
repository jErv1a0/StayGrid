<?php

namespace App\Controller\Admin;

use App\Entity\LogInUsers;
use App\Form\AdminProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

// NEW IMPORTS FOR FILE HANDLING
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
// END NEW IMPORTS

#[Route('/admin/profile')]
class AdminProfileController extends AbstractController
{
    #[Route('', name: 'app_admin_profile', methods: ['GET'])]
    public function index(): Response
    {
        /** @var LogInUsers|null $adminUser */
        $adminUser = $this->getUser();

        if (!$adminUser || !$this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_admin_login');
        }

        return $this->redirectToRoute('app_admin_profile_edit');
    }

    #[Route('/edit', name: 'app_admin_profile_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        SluggerInterface $slugger,             // NEW: For generating safe filenames
        ParameterBagInterface $parameterBag     // NEW: For getting the upload directory path
    ): Response {
        /** @var LogInUsers|null $adminUser */
        $adminUser = $this->getUser();

        // Only allow access if logged in and ROLE_ADMIN
        if (!$adminUser || !$this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_admin_login');
        }

        // Create the form
        $form = $this->createForm(AdminProfileType::class, $adminUser);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            // 1. Handle Password Change
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $hashedPassword = $passwordHasher->hashPassword($adminUser, $plainPassword);
                $adminUser->setPassword($hashedPassword);
            }

            // 2. Handle Profile Picture Upload (NEW LOGIC)
            $profilePictureFile = $form->get('profilePictureFile')->getData();

            if ($profilePictureFile) {
                // Get the upload directory path from parameters (must be configured in services.yaml)
                $uploadDirectory = $parameterBag->get('profile_pictures_directory');

                // Generate a safe filename
                $originalFilename = pathinfo($profilePictureFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$profilePictureFile->guessExtension();

                try {
                    // Move the file to the target directory
                    $profilePictureFile->move(
                        $uploadDirectory,
                        $newFilename
                    );
                    
                    // Update the Entity with the new filename
                    $adminUser->setProfilePicture($newFilename);

                } catch (FileException $e) {
                    // Handle exception if something goes wrong during file upload
                    $this->addFlash('error', 'Could not upload the profile picture. Check folder permissions: ' . $e->getMessage());
                    // Optionally, return early to prevent other changes from being flushed
                    return $this->redirectToRoute('app_admin_profile_edit');
                }
            }
            
            // 3. Persist Changes
            // Persist defensively in case the security user instance is detached.
            $entityManager->persist($adminUser);
            $entityManager->flush();

            $this->addFlash('success', 'Profile updated successfully.');

            return $this->redirectToRoute('app_admin_profile_edit');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Profile update failed. Please check the form fields and file size/type.');
        }

        // Render the form
        return $this->render('admin/profile/edit.html.twig', [
            'profileForm' => $form->createView(),
        ]);
    }
}