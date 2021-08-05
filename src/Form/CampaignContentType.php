<?php

namespace App\Form;

use App\Entity\CampaignContent;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CampaignContentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('fromName')
            ->add('subjectLine')
            ->add('template', FileType::class, [
                'mapped' => false,
                'attr' => [
                    'accept' => '.html'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => CampaignContent::class,
        ]);
    }
}
