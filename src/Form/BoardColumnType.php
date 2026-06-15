<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\BoardColumn;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BoardColumnType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de la colonne',
                'attr' => ['placeholder' => 'Ex. En cours'],
            ])
            ->add('color', TextType::class, [
                'label' => 'Couleur',
                'attr' => ['type' => 'color'],
            ])
            ->add('wipLimit', IntegerType::class, [
                'label' => 'Limite WIP',
                'required' => false,
                'help' => 'Nombre maximum de tickets recommandé (optionnel).',
                'attr' => ['min' => 1, 'placeholder' => 'Illimité'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BoardColumn::class,
        ]);
    }
}
