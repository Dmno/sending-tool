<?php

namespace App\Form;

use App\Entity\Batch;
use App\Entity\ContactList;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BatchType extends AbstractType
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
            ->add('contactList', EntityType::class, [
                'class' => ContactList::class,
                'choices' => $options['contactList_choices'],
                'choice_label' => function (ContactList $contactList) {
                    return $contactList->getName()." (".$contactList->getSize().")";
                }
            ])
            ->add('mode', ChoiceType::class, [
                'choices' => $options['mode_choices']
            ])
            ->add('list', TextareaType::class, [
                'label' => 'Paste in server list',
                'mapped' => false,
                'attr' => [
                    'class' => 'csv-data-input'
                ]
            ])
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
            'contactList_choices' => [],
            'mode_choices' => []
        ]);

        $resolver->setRequired([
            'contactList_choices',
            'mode_choices'
        ]);
    }
}
