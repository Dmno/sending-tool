<?php

namespace App\Form;

use App\Entity\ContactList;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichFileType;

class ContactListType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('file', VichFileType::class, [
                'required' => true,
                'attr' => [
                    'placeholder' => 'Choose file',
                    'accept' => '.csv'
                ]
            ])
            ->add('shared')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ContactList::class,
        ]);
    }
}
