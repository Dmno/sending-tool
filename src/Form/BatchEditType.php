<?php

namespace App\Form;

use App\Entity\Batch;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BatchEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('description', TextareaType::class, [
                'label' => 'Description (not required)',
                'required' => false
            ])
            ->add('cost', MoneyType::class)
            ->add('revenue', MoneyType::class)
            ->add('category', ChoiceType::class, [
                'choices' => [
                    'DO' => 'DO',
                    'OVH' => 'OVH',
                    'SS' => 'SS',
                    'WU' => 'WU'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Batch::class,
        ]);
    }
}
