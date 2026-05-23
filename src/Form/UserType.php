<?php

namespace App\Form;

use App\Entity\LogInUsers;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType; 
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity; 

// The UniqueEntity constraint must be applied at the class level for proper edit exclusion
#[UniqueEntity(
    fields: ['email'], 
    errorPath: 'email',
    message: 'This email address is already in use.',
)]
class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $roles = [
            'Client' => 'ROLE_CLIENT',
            'Admin' => 'ROLE_ADMIN',
            'Staff' => LogInUsers::ROLE_STAFF,
        ];

        $isNewUser = $options['is_edit'] === false;
        
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email Address',
                'required' => true,
            ])

            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                
                // CRITICAL: Unmapped for manual hashing in the controller
                'mapped' => false, 

                // Required only for new users
                'required' => $isNewUser, 
                
                'first_options'  => [
                    'label' => $isNewUser ? 'Password' : 'New Password (Leave blank to keep current)',
                    'attr'  => ['placeholder' => $isNewUser ? 'Enter password' : 'Optional: Enter new password'],
                ],
                'second_options' => [
                    'label' => $isNewUser ? 'Repeat Password' : 'Repeat New Password',
                    'attr'  => ['placeholder' => $isNewUser ? 'Confirm password' : 'Confirm new password'],
                ],
                
                'constraints' => [
                    new Length([
                        'min' => 6, 
                        'minMessage' => 'Your password should be at least {{ limit }} characters',
                    ]),
                    new NotBlank([
                        'message' => 'Please enter a password',
                        // Allows null/blank value if it's not a new user
                        'allowNull' => !$isNewUser,
                    ]),
                ],
            ])
            
            ->add('roles', ChoiceType::class, [
                'label' => 'User Roles',
                'choices' => $roles,
                'multiple' => true, 
                'expanded' => true, 
                'required' => false,
            ])
            
            ->add('isVerified', CheckboxType::class, [
                'label' => 'Email Verified',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LogInUsers::class,
            'is_edit' => false,
        ]);
        
        $resolver->setRequired('is_edit');
        $resolver->setAllowedTypes('is_edit', 'bool');
    }
}