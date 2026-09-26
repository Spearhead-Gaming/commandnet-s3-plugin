<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Admin\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * A new version of a modpack: label, mod list (an uploaded launcher preset or a typed list, see
 * MissionModsType) and changelog. Two buttons: "Preview changes" drafts the changelog from the
 * difference to the previous version without saving, "Publish" saves it. Submitted data is
 * ['label' => string, 'changelog' => ?string, 'mods' => ['mods' => list<ParsedMod>]].
 *
 * @extends AbstractType<array<string, mixed>>
 */
class ModPackVersionType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['mod_text' => '']);
        $resolver->setAllowedTypes('mod_text', 'string');
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('label', TextType::class, [
                'label' => 'Version',
                'help' => 'For example 2026.09-r2.',
                'constraints' => [new NotBlank(), new Length(max: 50)],
            ])
            ->add('mods', MissionModsType::class, [
                'mod_text' => $options['mod_text'],
                'label' => 'Mod list',
            ])
            ->add('changelog', TextareaType::class, [
                'required' => false,
                'attr' => ['rows' => 6],
                'help' => 'What changed. "Preview changes" fills this in from the difference to the previous version, and you can edit it before publishing. Left empty, publishing uses that draft.',
                'constraints' => [new Length(max: 5000)],
            ])
            ->add('preview', SubmitType::class, ['label' => 'Preview changes', 'attr' => ['class' => 'btn-outlined']])
            ->add('publish', SubmitType::class, ['label' => 'Publish version', 'attr' => ['class' => 'btn-primary']])
        ;
    }
}
