<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Admin\Form;

use MajesticDev\CommandNetS3\Entity\Enum\AssetCategory;
use MajesticDev\CommandNetS3\Entity\ZeusAsset;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Url;

/**
 * @extends AbstractType<ZeusAsset>
 */
class ZeusAssetType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ZeusAsset::class,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
            ->add('category', EnumType::class, [
                'class' => AssetCategory::class,
                'choice_label' => fn (AssetCategory $c) => $c->label(),
            ])
            ->add('modUrl', UrlType::class, [
                'label' => 'Mod link',
                'required' => false,
                'default_protocol' => 'https',
                'help' => 'Workshop or download page for the mod.',
                'constraints' => [new Url(protocols: ['http', 'https']), new Length(max: 500)],
            ])
            ->add('notes', TextareaType::class, [
                'required' => false,
                'help' => 'Anything a Zeus should know before using it: required mods, quirks, good for which mission types.',
            ])
        ;
    }
}
