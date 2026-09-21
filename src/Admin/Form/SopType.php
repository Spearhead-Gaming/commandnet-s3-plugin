<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Admin\Form;

use MajesticDev\CommandNetS3\Entity\Sop;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<Sop>
 */
class SopType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Sop::class,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class)
            ->add('description', TextareaType::class, [
                'required' => false,
                'help' => 'A short summary shown in the library list. Add the actual text as a Version.',
            ])
        ;
    }
}
