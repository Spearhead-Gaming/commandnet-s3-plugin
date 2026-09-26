<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Admin\Form;

use Doctrine\ORM\EntityRepository;
use MajesticDev\CommandNet\Entity\Deployment;
use MajesticDev\CommandNetS3\Entity\DeploymentPage;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<DeploymentPage>
 */
class DeploymentPageType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DeploymentPage::class,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('deployment', EntityType::class, [
            'class' => Deployment::class,
            'choice_label' => 'name',
            // One set of details per Deployment, so only offer Deployments without one (plus the
            // one being edited).
            'query_builder' => static function (EntityRepository $repository) use ($options) {
                $qb = $repository->createQueryBuilder('d')
                    ->leftJoin(DeploymentPage::class, 'existing', 'WITH', 'existing.deployment = d')
                    ->orderBy('d.startDate', 'DESC');

                $current = $options['data'] ?? null;
                if ($current instanceof DeploymentPage && $current->getId() !== null) {
                    return $qb->where('existing.id IS NULL OR existing.id = :current')->setParameter('current', $current->getId());
                }

                return $qb->where('existing.id IS NULL');
            },
            'help' => 'Every operation page in this Deployment shows these details, unless the page fills in its own. Anything left blank here falls back to the S3-wide Page Defaults.',
        ]);

        SharedPageFields::add($builder);
    }
}
