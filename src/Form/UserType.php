<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = (bool) $options['is_edit'];

        $builder
            ->add('email', EmailType::class, [
                'label' => 'Adresse e-mail',
            ])
            ->add('fullName', TextType::class, [
                'label' => 'Nom complet',
            ])
            ->add('role', ChoiceType::class, [
                'label' => 'Rôle',
                'mapped' => false,
                'choices' => [
                    'Utilisateur classique' => User::ROLE_USER,
                    'Administrateur' => User::ROLE_ADMIN,
                ],
                'data' => $options['current_role'],
                'expanded' => false,
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => $isEdit ? 'Nouveau mot de passe' : 'Mot de passe',
                'mapped' => false,
                'required' => !$isEdit,
                'help' => $isEdit ? 'Laisser vide pour conserver le mot de passe actuel.' : null,
                'constraints' => array_filter([
                    $isEdit ? null : new Assert\NotBlank(message: 'Veuillez saisir un mot de passe.'),
                    new Assert\Length(min: 6, max: 4096, minMessage: 'Au moins {{ limit }} caractères.'),
                ]),
                'attr' => ['autocomplete' => 'new-password'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_edit' => false,
            'current_role' => User::ROLE_USER,
        ]);
        $resolver->setAllowedTypes('is_edit', 'bool');
        $resolver->setAllowedTypes('current_role', 'string');
    }
}
