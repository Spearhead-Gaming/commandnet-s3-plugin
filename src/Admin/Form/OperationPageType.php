<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Admin\Form;

use Doctrine\ORM\EntityRepository;
use MajesticDev\CommandNet\Entity\Operation;
use MajesticDev\CommandNetS3\Entity\GameServer;
use MajesticDev\CommandNetS3\Entity\OperationPage;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Url;

/**
 * @extends AbstractType<OperationPage>
 */
class OperationPageType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OperationPage::class,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('operation', EntityType::class, [
                'class' => Operation::class,
                'choice_label' => 'title',
                // Operations get a page automatically when they are created, so this list is for
                // the ones that don't have one (an older operation, or another type). A page's own
                // operation stays selectable while editing it.
                'query_builder' => static function (EntityRepository $repository) use ($options) {
                    $qb = $repository->createQueryBuilder('o')
                        ->leftJoin(OperationPage::class, 'existing', 'WITH', 'existing.operation = o')
                        ->orderBy('o.startDateTime', 'DESC');

                    $current = $options['data'] ?? null;
                    if ($current instanceof OperationPage && $current->getId() !== null) {
                        return $qb->where('existing.id IS NULL OR existing.id = :current')->setParameter('current', $current->getId());
                    }

                    return $qb->where('existing.id IS NULL');
                },
                'help' => 'Pages are created automatically for new operations, so this only lists operations without one. The page shows the operation\'s title, dates, status, OPORD, RSVPs and briefing live. Only what is below is stored here. The server, Steam link, comms plan, ROE, checklist and quick links can be left blank: the page then uses its Deployment\'s details (Zeus / GM → Deployment Details), or the S3-wide Page Defaults.',
            ])
            ->add('published', CheckboxType::class, [
                'required' => false,
                'help' => 'Until this is on, only staff see these extra sections.',
            ])
            ->add('orderNumber', TextType::class, [
                'required' => false,
                'label' => 'Order number',
                'help' => 'Filled in automatically when the page is created: 26-10-03 is the third operation of the Deployment starting in October 2026 (26-04 style, counted by year, when there is no Deployment). Change it if you need to.',
                'constraints' => [new Length(max: 30)],
            ])
            ->add('summary', TextType::class, [
                'required' => false,
                'help' => 'One line under the title. Leave blank to use the start of the briefing\'s task and purpose.',
                'constraints' => [new Length(max: 500)],
            ])
            ->add('server', EntityType::class, [
                'class' => GameServer::class,
                'required' => false,
                'placeholder' => 'No server',
                'choice_label' => 'name',
                'help' => 'Its status is shown live, and its tracked mods become the mod list.',
            ])
            ->add('presetUrl', UrlType::class, [
                'required' => false,
                'label' => 'Preset download link',
                'default_protocol' => 'https',
                'constraints' => [new Url(protocols: ['http', 'https']), new Length(max: 500)],
            ])
            ->add('steamCollectionUrl', UrlType::class, [
                'required' => false,
                'label' => 'Steam collection link',
                'default_protocol' => 'https',
                'constraints' => [new Url(protocols: ['http', 'https']), new Length(max: 500)],
            ])
            ->add('timeline', TextareaType::class, $this->lines(
                'Timeline',
                'One per line: time | title | description',
                "1830 | Server & Comms Open | Join Discord and sync mods\n1900 | Platoon Briefing | Full OPORD brief",
            ))
            ->add('taskOrg', TextareaType::class, $this->lines(
                'Task organization',
                'One block per element, blocks separated by a blank line. First line: name | subtitle. Then role | count.',
                "Alpha Squad | Support\nSquad Leader | 1\nRiflemen | 4\n\nBravo Squad | Main effort\nSquad Leader | 1",
            ))
            ->add('missionData', TextareaType::class, $this->lines(
                'Mission data',
                'One per line: label | value',
                "Zeus | Alpha, Delta\nOIC | Maj. Reyes",
            ))
            ->add('comms', TextareaType::class, $this->lines(
                'Comms plan',
                'One per line: net | frequency',
                "Command | 40.100\nAlpha | 40.200",
            ))
            ->add('roe', TextareaType::class, $this->lines(
                'Rules of engagement',
                'One rule per line',
                'Weapons TIGHT until H-Hour.',
            ))
            ->add('checklist', TextareaType::class, $this->lines(
                'Pre-op checklist',
                'One item per line',
                "Mod-pack synced\nTFAR working",
            ))
            ->add('quickLinks', TextareaType::class, $this->lines(
                'Quick links',
                'One per line: label | link. A link must start with http(s):// or / (a page on this site), anything else is ignored.',
                "Unit SOP | /sops\nDiscord | https://discord.gg/example",
            ))
        ;
    }

    /**
     * Options shared by the one-entry-per-line boxes.
     *
     * @return array<string, mixed>
     */
    private function lines(string $label, string $help, string $example): array
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
