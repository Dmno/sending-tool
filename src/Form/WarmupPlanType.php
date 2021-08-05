<?php

namespace App\Form;

use App\Entity\WarmupPlan;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WarmupPlanType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('plan', TextareaType::class, [
                'label' => 'Plan (Hourly Quota)',
                'attr' => [
                    'class' => 'csv-data-input',
                    'placeholder' => '20,28,40,68,100...'
                ]
            ])
            ->add('shared')
        ;

        $builder->get('plan')->addModelTransformer(new CallbackTransformer(
            function ($tagsAsArray) {
                // transform the array to a string
                return implode(',', $tagsAsArray);
            },
            function ($tagsAsString) {
                // transform the string back to an array
                return explode(',', $tagsAsString);
            }
        ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => WarmupPlan::class,
        ]);
    }
}
