<?php
// src/Form/ProfileEditType.php

namespace App\Form;

use App\Entity\LogInUsers; 
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType; // <-- Required Import
use Symfony\Component\Form\Extension\Core\Type\RepeatedType; // <-- Required Import
use Symfony\Component\Validator\Constraints\File; 
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ProfileEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // ... (Full Name and Email fields are here) ...
            ->add('fullName', TextType::class, [
                'label' => 'Full Name',
                'attr' => ['placeholder' => 'Enter your full name'],
                'required' => true,
            ])
            
            ->add('email', EmailType::class, [
                'label' => 'Email Address',
                'attr' => ['placeholder' => 'your.email@example.com'],
                'required' => true,
            ])

            ->add('address', TextType::class, [
                'label' => 'City / Location',
                'required' => false,
                'attr' => [
                    'placeholder' => 'e.g. Manila, Cebu, Davao',
                ],
            ])
            
            // Profile Picture Field (added in the last step)
            ->add('profilePictureFile', FileType::class, [
                'label' => 'Upload new profile picture',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg', 'image/png', 'image/webp',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image file (JPEG, PNG, WebP).',
                    ])
                ],
            ])

            // NEW FIELDS: Password Change Section (plainPassword)
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false, // This is crucial: it's not mapped to an entity field
                'required' => false,
                'first_options' => [
                    'label' => 'New Password',
                    'attr' => ['placeholder' => 'Leave blank to keep current password']
                ],
                'second_options' => [
                    'label' => 'Repeat New Password',
                    'attr' => ['placeholder' => 'Confirm new password']
                ],
                // Add constraints for validation if a password is entered
                'constraints' => [
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Your password should be at least {{ limit }} characters',
                        'max' => 4096,
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LogInUsers::class, 
            'csrf_protection' => true,
        ]);
    }
}