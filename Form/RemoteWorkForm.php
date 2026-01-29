<?php

/*
 * This file is part of the "Remote Work" plugin for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\RemoteWorkBundle\Form;

use App\Form\Type\DatePickerType;
use App\Form\Type\UserType;
use App\Form\Type\YesNoType;
use KimaiPlugin\RemoteWorkBundle\Entity\RemoteWork;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

/**
 * @extends AbstractType<RemoteWork>
 */
final class RemoteWorkForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'] === true;

        $year = $options['year'];
        if (\is_int($year) && $year > 0) {
            $year = (string) $year;
        }

        if (!\is_string($year)) {
            $year = date('Y');
        }

        if (!$isEdit) {
            // Date picker for start date
            $builder->add('date', DatePickerType::class, [
                'constraints' => [new NotNull()],
                'label' => 'date',
                'min_day' => $year . '-01-01',
                'max_day' => $year . '-12-31',
            ]);

            // Optional end date for date range (not mapped to entity)
            $builder->add('end', DatePickerType::class, [
                'required' => false,
                'label' => 'end',
                'min_day' => $year . '-01-01',
                'max_day' => $year . '-12-31',
            ]);

            // Half day option
            $builder->add('halfDay', YesNoType::class, [
                'label' => 'day_half',
            ]);

            if ($options['include_user'] === true) {
                $builder->add('user', UserType::class);
            }
        }

        $builder->add('comment', TextType::class, [
            'required' => false,
            'label' => 'comment',
            'attr' => [
                'maxlength' => 250,
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RemoteWork::class,
            'csrf_protection' => false,
            'include_user' => false,
            'is_edit' => false,
            'year' => date('Y'),
            'method' => 'POST',
            'attr' => [
                'data-form-event' => 'kimai.remoteWork',
                'data-msg-success' => 'action.update.success',
                'data-msg-error' => 'action.update.error',
            ],
        ]);

        $resolver->setAllowedTypes('include_user', 'bool');
        $resolver->setAllowedTypes('is_edit', 'bool');
        $resolver->setAllowedTypes('year', ['int', 'string']);
    }
}
