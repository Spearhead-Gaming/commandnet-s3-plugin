<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Admin\Form;

use Forumify\Core\Form\RichTextEditorType;
use MajesticDev\CommandNet\Entity\Operation;
use MajesticDev\CommandNetS3\Entity\Briefing;
use MajesticDev\CommandNetS3\Entity\Enum\BriefingStatus;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<Briefing>
 */
class BriefingType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Briefing::class,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('operation', EntityType::class, [
                'class' => Operation::class,
                'choice_label' => 'title',
            ])
            ->add('missionName', TextType::class)
            ->add('taskPurpose', RichTextEditorType::class, [
                'label' => 'Task / purpose',
            ])
            ->add('objectives', TextareaType::class, [
                'required' => false,
                'help' => 'One objective per line, in priority order.',
            ])
            ->add('status', EnumType::class, [
                'class' => BriefingStatus::class,
                'choice_label' => fn (BriefingStatus $s) => $s->label(),
                'help' => 'Slotted players only see the briefing once it is Ready.',
            ])
        ;
    }
}
