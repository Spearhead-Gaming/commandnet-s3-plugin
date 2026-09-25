<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Admin\Form;

use MajesticDev\CommandNet\Entity\Operation;
use MajesticDev\CommandNetS3\Entity\Mission;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

/**
 * @extends AbstractType<Mission>
 */
class MissionType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Mission::class,
            'mod_text' => '',
            'with_version' => false,
        ]);
        $resolver->setAllowedTypes('mod_text', 'string');
        $resolver->setAllowedTypes('with_version', 'bool');
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'constraints' => [new Length(max: 255)],
            ])
            ->add('operation', EntityType::class, [
                'class' => Operation::class,
                'required' => false,
                'placeholder' => 'Not tied to an operation',
                'choice_label' => 'title',
                'help' => 'The operation this mission is built for, so the exact build used is easy to find later.',
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'attr' => ['rows' => 5],
            ])
            ->add('mods', MissionModsType::class, [
                'mapped' => false,
                'mod_text' => $options['mod_text'],
            ])
        ;

        // Only when creating: later versions go through "Upload a version" on the mission page.
        if ($options['with_version']) {
            $builder->add('version', MissionVersionType::class, [
                'mapped' => false,
                'optional' => true,
                'label' => 'First version (optional)',
            ]);
        }
    }
}
