<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Admin\Form;

use MajesticDev\CommandNetS3\Entity\GameServer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * @extends AbstractType<GameServer>
 */
class GameServerType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => GameServer::class,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'constraints' => [new Length(max: 100)],
            ])
            ->add('host', TextType::class, [
                'help' => 'Host name or IP address of the server.',
                'constraints' => [
                    new Length(max: 255),
                    new Regex('/^[A-Za-z0-9.\-]+$/', message: 'Use a host name or IP address only, without a protocol or port.'),
                ],
            ])
            ->add('queryPort', IntegerType::class, [
                'help' => 'The Steam query port (for Arma this is usually the game port plus 1), not the game port itself.',
                'constraints' => [new Range(min: 1, max: 65535)],
            ])
        ;
    }
}
