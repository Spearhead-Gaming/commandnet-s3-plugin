<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Admin\Form;

use InvalidArgumentException;
use MajesticDev\CommandNetS3\Service\ModListParser;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\SubmitEvent;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;

/**
 * The two ways to give a mission its mod list, shared by the mission form and the mod list page:
 * upload the launcher's exported preset, or type the list. Once submitted, the form's data is
 * ['mods' => list<ParsedMod>]; a bad file or line becomes a form error instead.
 *
 * @extends AbstractType<array{mods: list<\MajesticDev\CommandNetS3\Service\ParsedMod>}>
 */
class MissionModsType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'mod_text' => '',
            'label' => 'Mod list',
        ]);
        $resolver->setAllowedTypes('mod_text', 'string');
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('preset', FileType::class, [
                'required' => false,
                'label' => 'Arma 3 Launcher preset',
                'help' => 'In the Arma 3 Launcher: Mods, Preset, Export. Uploading a preset replaces the list below. The file is only read for its mods and never stored or shared; players get a fresh preset generated from the list.',
                // The launcher's export starts with an XML declaration line, so PHP reports it as text/xml, not text/html,
                // and one saved with a byte-order mark is sniffed as text/x-affix.
                // The file is never stored or served, and the parser rejects anything without mod rows anyway.
                'constraints' => [new File(
                    maxSize: '2M',
                    extensions: [
                        'html' => ['text/html', 'application/xhtml+xml', 'text/xml', 'application/xml', 'text/plain', 'text/x-affix'],
                        'htm' => ['text/html', 'application/xhtml+xml', 'text/xml', 'application/xml', 'text/plain', 'text/x-affix'],
                    ],
                    extensionsMessage: 'Upload the .html file the Arma 3 Launcher exports.',
                )],
            ])
            ->add('text', TextareaType::class, [
                'required' => false,
                'label' => 'Or type the list',
                'data' => $options['mod_text'],
                'attr' => ['rows' => 10, 'placeholder' => "CBA_A3 | 450814997\nACE3 | https://steamcommunity.com/sharedfiles/filedetails/?id=463939057\nDLC: Arma 3 Creator DLC: Contact | 1021790\nMy local mod"],
                'help' => 'One per line: name | Workshop link or ID. Start a DLC line with "DLC:". A line with only a name is a local mod, which players can\'t download. Saving makes the mission\'s list exactly this.',
                'constraints' => [new Length(max: 60000)],
            ])
            ->addEventListener(FormEvents::SUBMIT, $this->parse(...));
    }

    private function parse(SubmitEvent $event): void
    {
        /** @var array{preset?: ?UploadedFile, text?: ?string}|null $data */
        $data = $event->getData();
        $file = $data['preset'] ?? null;

        try {
            $mods = $file instanceof UploadedFile
                ? ModListParser::parsePreset((string)file_get_contents($file->getPathname()))
                : ModListParser::parseText((string)($data['text'] ?? ''));
        } catch (InvalidArgumentException $exception) {
            $event->getForm()->addError(new FormError($exception->getMessage()));
            $mods = [];
        }

        $event->setData(['mods' => $mods]);
    }
}
