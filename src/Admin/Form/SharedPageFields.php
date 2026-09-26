<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Admin\Form;

use MajesticDev\CommandNetS3\Entity\GameServer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Url;

/**
 * The operation-page details that can be set once for a whole Deployment, or for everything, and
 * inherited by the pages below. Shared by the Deployment Details form and the Page Defaults
 * settings form so both describe the boxes the same way. The field names match OperationPage's.
 */
final class SharedPageFields
{
    public static function add(FormBuilderInterface $builder): void
    {
        $builder
            ->add('server', EntityType::class, [
                'class' => GameServer::class,
                'required' => false,
                'placeholder' => 'No server',
                'choice_label' => 'name',
                'help' => 'Its status is shown live on the page.',
            ])
            ->add('steamCollectionUrl', UrlType::class, [
                'required' => false,
                'label' => 'Steam collection link',
                'default_protocol' => 'https',
                'constraints' => [new Url(protocols: ['http', 'https']), new Length(max: 500)],
            ])
            ->add('comms', TextareaType::class, self::lines(
                'Comms plan',
                'One per line: net | frequency',
                "Command | 40.100\nAlpha | 40.200",
            ))
            ->add('roe', TextareaType::class, self::lines(
                'Rules of engagement',
                'One rule per line',
                'Weapons TIGHT until H-Hour.',
            ))
            ->add('checklist', TextareaType::class, self::lines(
                'Pre-op checklist',
                'One item per line',
                "Mod-pack synced\nTFAR working",
            ))
            ->add('quickLinks', TextareaType::class, self::lines(
                'Quick links',
                'One per line: label | link. A link must start with http(s):// or / (a page on this site), anything else is ignored.',
                "Unit SOP | /sops\nDiscord | https://discord.gg/example",
            ));
    }

    /**
     * @return array<string, mixed>
     */
    private static function lines(string $label, string $help, string $example): array
    {
        return [
            'required' => false,
            'label' => $label,
            'help' => $help,
            'attr' => ['rows' => 6, 'placeholder' => $example],
            'constraints' => [new Length(max: 5000)],
        ];
    }
}
