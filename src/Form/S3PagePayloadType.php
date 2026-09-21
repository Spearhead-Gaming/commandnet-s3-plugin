<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * The options in Forumify's Menu Builder for an "S3" menu item: which of this plugin's member
 * pages it links to.
 *
 * @extends AbstractType<array<string, mixed>>
 */
class S3PagePayloadType extends AbstractType
{
    /** Page label => route. */
    public const array PAGES = [
        'Dashboard' => 'command_net_s3_dashboard',
        'SOP Library' => 'command_net_s3_sop_list',
    ];

    /** Route => permission a viewer needs for the link to be worth showing. */
    public const array PERMISSIONS = [
        'command_net_s3_dashboard' => 'command-net-s3.dashboard.view',
        'command_net_s3_sop_list' => 'command-net-s3.sop.view',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('pages', ChoiceType::class, [
            'choices' => self::PAGES,
            'multiple' => true,
            'expanded' => true,
            'label' => 'Pages',
            'help' => 'Check every page this menu item should link to. One page shows as a plain link, several as a dropdown. Use the item\'s permissions in the Menu Builder to limit it to S3 staff roles.',
        ]);
    }
}
