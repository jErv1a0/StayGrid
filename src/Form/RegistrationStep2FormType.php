<?php

namespace App\Form;

use App\Entity\LogInUsers;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationStep2FormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fullName', TextType::class, [
                'label' => 'Full Name',
                'attr' => [
                    'placeholder' => 'John Doe',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter your full name',
                    ]),
                ],
            ])

            ->add('birthday', DateType::class, [
                'label' => 'Birthday',
                'widget' => 'single_text',
                'attr' => [
                    'type' => 'date',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter your birthday',
                    ]),
                ],
            ])

            ->add('address', TextType::class, [
                'label' => 'Address',
                'attr' => [
                    'placeholder' => '123 Main Street, City, Country',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter your address',
                    ]),
                ],
            ])

            ->add('phoneNumber', TelType::class, [
                'label' => 'Phone Number',
                'attr' => [
                    'placeholder' => '+63 9XX XXX XXXX',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter your phone number',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LogInUsers::class,
        ]);
    }
}
