<?php

namespace App\Form;

use App\Entity\LogInUsers;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;


class AdminProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            
            ->add('fullName', TextType::class, [
                'label' => 'Full Name',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Enter your full name',
                ],
            ])
            
            ->add('profilePictureFile', FileType::class, [
                'label' => false, 
                'mapped' => false, 
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5120k', 
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                            'image/heic',
                            'image/heif',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image (JPG, PNG, WEBP, HEIC, or HEIF). Max size: 5MB',
                    ])
                ],
            ])


            ->add('email', EmailType::class, [
                'label' => 'Login Email',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Enter your email',
                ],
            ])
            
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false, 
                'required' => false, 
                'first_options' => [
                    'label' => 'New Password',
                    'attr' => [
                        'placeholder' => 'Enter new password (leave blank to keep current)',
                    ],
                ],
                'second_options' => [
                    'label' => 'Confirm New Password',
                    'attr' => [
                        'placeholder' => 'Confirm new password',
                    ],
                ],
                'invalid_message' => 'The password fields must match.',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LogInUsers::class,
            // Add 'allow_extra_fields' to ensure unmapped fields are accepted
            'allow_extra_fields' => true, 
        ]);
    }
}