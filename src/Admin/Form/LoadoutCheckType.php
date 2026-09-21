<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Admin\Form;

use MajesticDev\CommandNet\Entity\Operation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Not bound to an entity: nothing is saved, the form only feeds the check.
 *
 * @extends AbstractType<array{operation: Operation, classnames: string}>
 */
class LoadoutCheckType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('operation', EntityType::class, [
                'class' => Operation::class,
                'choice_label' => 'title',
                'help' => 'Mission-specific approvals for this operation count as approved.',
            ])
            ->add('classnames', TextareaType::class, [
                'label' => 'Kit list',
                'help' => 'Arma class names, one per line (commas work too).',
                'attr' => ['rows' => 12],
                'constraints' => [new NotBlank(), new Length(max: 20000)],
            ])
        ;
    }
}
