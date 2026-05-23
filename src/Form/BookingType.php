<?php

namespace App\Form;

use App\Entity\Booking;
use App\Entity\LogInUsers;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class BookingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('bookingType', ChoiceType::class, [
                'choices' => [
                    'Daily Booking' => 'daily',
                    'Hourly Booking (minimum 4 hours)' => 'hourly',
                ],
                'label' => 'Booking Type',
                'attr' => ['class' => 'booking-type-selector'],
            ])
            ->add('startDate', DateType::class, [
                'widget' => 'single_text',
                'html5' => true,
                'label' => 'Check-In Date',
                'attr' => ['class' => 'daily-booking-field'],
            ])
            ->add('endDate', DateType::class, [
                'widget' => 'single_text',
                'html5' => true,
                'label' => 'Check-Out Date',
                'attr' => ['class' => 'daily-booking-field'],
            ])
            ->add('numberOfHours', IntegerType::class, [
                'label' => 'Number of Hours (minimum 4)',
                'required' => false,
                'attr' => [
                    'class' => 'hourly-booking-field',
                    'min' => 4,
                    'step' => 1,
                    'style' => 'display: none;'
                ],
            ]);

        if ($options['is_staff']) {
            $builder->add('user', EntityType::class, [
                'class' => LogInUsers::class,
                'choice_label' => function(LogInUsers $user) {
                    return $user->getFullName() ?: $user->getEmail();
                },
                'label' => 'Book for User',
                'placeholder' => 'Select a user (leave empty for yourself)',
                'required' => false,
            ]);
        }

        $builder->add('status', ChoiceType::class, [
            'choices' => [
                'Pending' => 'pending',
                'Confirmed' => 'confirmed',
                'Cancelled' => 'cancelled',
                'Completed' => 'completed',
            ],
            'label' => 'Status',
            'attr' => ['class' => 'form-control'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Booking::class,
            'is_staff' => false,
        ]);
    }
}
