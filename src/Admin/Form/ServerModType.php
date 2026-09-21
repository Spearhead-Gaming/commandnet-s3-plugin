<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Admin\Form;

use MajesticDev\CommandNetS3\Entity\GameServer;
use MajesticDev\CommandNetS3\Entity\ServerMod;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * @extends AbstractType<ServerMod>
 */
class ServerModType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ServerMod::class,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('server', EntityType::class, [
                'class' => GameServer::class,
                'choice_label' => 'name',
            ])
            ->add('name', TextType::class, [
                'constraints' => [new Length(max: 150)],
            ])
            ->add('workshopId', TextType::class, [
                'required' => false,
                'label' => 'Workshop ID',
                'help' => 'Optional. The number at the end of the Steam Workshop URL.',
                'constraints' => [new Regex('/^\d{1,30}$/', message: 'Digits only.')],
            ])
            ->add('installedVersion', TextType::class, [
                'label' => 'Installed on server',
                'help' => 'Changing this is recorded in the audit log, which doubles as the update history.',
                'constraints' => [new Length(max: 50)],
            ])
            ->add('modpackVersion', TextType::class, [
                'required' => false,
                'label' => 'In client modpack',
                'help' => 'Leave blank if unknown. The mod is flagged Out of date when the server is behind this.',
                'constraints' => [new Length(max: 50)],
            ])
        ;
    }
}
