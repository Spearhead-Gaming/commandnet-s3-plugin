<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Admin\Form;

use Forumify\Core\Form\RichTextEditorType;
use MajesticDev\CommandNetS3\Entity\Sop;
use MajesticDev\CommandNetS3\Entity\SopVersion;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<SopVersion>
 */
class SopVersionType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SopVersion::class,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('sop', EntityType::class, [
                'class' => Sop::class,
                'choice_label' => 'title',
                'label' => 'Document',
            ])
            ->add('label', TextType::class, [
                'label' => 'Version',
                'help' => 'For example 1.2. The newest version of a document is its current one, and everyone has to acknowledge it again.',
            ])
            ->add('changelog', TextareaType::class, [
                'required' => false,
                'help' => 'What changed since the previous version.',
            ])
            ->add('content', RichTextEditorType::class)
        ;
    }
}
