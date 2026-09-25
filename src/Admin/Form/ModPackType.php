<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Admin\Form;

use MajesticDev\CommandNet\Entity\Deployment;
use MajesticDev\CommandNetS3\Entity\ModPack;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

/**
 * @extends AbstractType<ModPack>
 */
class ModPackType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => ModPack::class]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'constraints' => [new Length(max: 150)],
                'help' => 'Shown on the operation page, for example "Spearhead Modpack".',
            ])
            ->add('deployment', EntityType::class, [
                'class' => Deployment::class,
                'help' => 'Operations in this deployment use its modpack unless their mission has its own mod list. Each deployment has one pack.',
            ])
        ;
    }
}
