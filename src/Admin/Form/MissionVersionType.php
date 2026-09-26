<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Admin\Form;

use MajesticDev\CommandNetS3\Service\MissionStorage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PostSubmitEvent;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * A mission version: label, notes and the file. Shared by the "Upload a version" page (all
 * required) and the create-mission form, where the first version is optional but must be given
 * whole, or not at all. Once submitted the data is ['label' => ?string, 'notes' => ?string, 'file' => ?UploadedFile].
 *
 * @extends AbstractType<array{label: ?string, notes: ?string, file: ?UploadedFile}>
 */
class MissionVersionType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['optional' => false]);
        $resolver->setAllowedTypes('optional', 'bool');
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $required = !$options['optional'];

        $builder
            ->add('label', TextType::class, [
                'required' => $required,
                'label' => 'Version',
                'help' => 'For example v1.3.',
                'constraints' => [new Length(max: 50), ...($required ? [new NotBlank()] : [])],
            ])
            ->add('notes', TextareaType::class, [
                'required' => false,
                'help' => 'What changed in this build.',
                'attr' => ['rows' => 4],
            ])
            ->add('file', FileType::class, [
                'required' => $required,
                'label' => 'Mission file',
                'help' => 'One of: ' . implode(', ', MissionStorage::EXTENSIONS) . '. Very large files are limited by the server\'s PHP upload size.',
                'constraints' => [
                    new File(maxSize: '256M', extensions: MissionStorage::EXTENSIONS, extensionsMessage: 'Upload a .pbo, .vt or .zip file.'),
                    ...($required ? [new NotBlank()] : []),
                ],
            ]);

        if ($options['optional']) {
            $builder->addEventListener(FormEvents::POST_SUBMIT, $this->requireBoth(...));
        }
    }

    private function requireBoth(PostSubmitEvent $event): void
    {
        $form = $event->getForm();
        $hasFile = $form->get('file')->getData() instanceof UploadedFile;
        $hasLabel = trim((string)$form->get('label')->getData()) !== '';

        if ($hasFile && !$hasLabel) {
            $form->get('label')->addError(new FormError('Give this version a label, for example v1.0.'));
        } elseif ($hasLabel && !$hasFile && $form->get('file')->isValid()) {
            $form->get('file')->addError(new FormError('Choose the mission file, or clear the version label.'));
        }
    }
}
