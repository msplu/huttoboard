<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\BoardColumn;
use App\Entity\Project;
use App\Entity\Ticket;
use App\Entity\User;
use App\Enum\Priority;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TicketType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Project $project */
        $project = $options['project'];

        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'attr' => ['placeholder' => 'Résumé court de la tâche'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['rows' => 5, 'placeholder' => 'Détails, critères d’acceptation…'],
            ])
            ->add('priority', EnumType::class, [
                'label' => 'Priorité',
                'class' => Priority::class,
                'choice_label' => fn (Priority $p) => $p->label(),
            ])
            ->add('column', EntityType::class, [
                'label' => 'Colonne',
                'class' => BoardColumn::class,
                'choice_label' => 'name',
                'query_builder' => fn (EntityRepository $repo) => $repo->createQueryBuilder('c')
                    ->andWhere('c.project = :project')
                    ->setParameter('project', $project)
                    ->orderBy('c.position', 'ASC'),
            ])
            ->add('assignee', EntityType::class, [
                'label' => 'Assigné à',
                'class' => User::class,
                'required' => false,
                'placeholder' => '— Non assigné —',
                'choice_label' => 'fullName',
                'query_builder' => fn (EntityRepository $repo) => $repo->createQueryBuilder('u')
                    ->orderBy('u.fullName', 'ASC'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ticket::class,
        ]);
        $resolver->setRequired('project');
        $resolver->setAllowedTypes('project', Project::class);
    }
}
