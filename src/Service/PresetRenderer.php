<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Service;

use MajesticDev\CommandNetS3\Entity\Enum\ModKind;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Twig\Environment;

/**
 * Builds the preset file players import into the Arma 3 Launcher, in the launcher's own export
 * format, from a mod list. It is generated from scratch every time, so nothing an admin uploaded
 * is ever served back to players.
 */
class PresetRenderer
{
    public function __construct(private readonly Environment $twig)
    {
    }

    /**
     * The preset as a file download. It is always an attachment, never shown in the browser.
     *
     * @param list<ParsedMod> $mods
     */
    public function download(string $presetName, array $mods): Response
    {
        $slug = (new AsciiSlugger())->slug($presetName)->lower()->toString();
        $response = new Response($this->render($presetName, $mods));
        $response->headers->set('Content-Type', 'text/html; charset=UTF-8');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Content-Disposition', HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            ($slug !== '' ? $slug : 'modpack') . '-preset.html',
        ));

        return $response;
    }

    /**
     * @param list<ParsedMod> $mods
     */
    public function render(string $presetName, array $mods): string
    {
        $rows = static fn (ModKind $kind): array => array_values(array_map(
            static fn (ParsedMod $mod) => ['name' => $mod->name, 'url' => ModListParser::url($mod->kind, $mod->steamId)],
            array_filter($mods, static fn (ParsedMod $mod) => $mod->kind === $kind),
        ));

        return $this->twig->render('@CommandNetS3Plugin/frontend/modpack/preset.html.twig', [
            'name' => $presetName,
            'mods' => $rows(ModKind::MOD),
            'dlc' => $rows(ModKind::DLC),
        ]);
    }
}
