<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Service;

use Doctrine\ORM\EntityManagerInterface;
use Forumify\Core\Entity\User;
use MajesticDev\CommandNet\Entity\Deployment;
use MajesticDev\CommandNetS3\Entity\ModPack;
use MajesticDev\CommandNetS3\Entity\ModPackMod;
use MajesticDev\CommandNetS3\Entity\ModPackVersion;
use MajesticDev\CommandNetS3\Repository\ModPackModRepository;
use MajesticDev\CommandNetS3\Repository\ModPackRepository;
use MajesticDev\CommandNetS3\Repository\ModPackVersionRepository;

/**
 * Reads a deployment's modpack and publishes new versions of it. Publishing is the mod list's
 * only write: a version's rows are created once (like MissionModList::replace, but never
 * replaced afterwards), so old versions stay exactly as they were for rollback.
 */
class ModPackService
{
    public function __construct(
        private readonly ModPackRepository $packRepository,
        private readonly ModPackVersionRepository $versionRepository,
        private readonly ModPackModRepository $modRepository,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function currentFor(?Deployment $deployment): ?ModPackVersion
    {
        $pack = $deployment !== null ? $this->packRepository->findOneBy(['deployment' => $deployment]) : null;

        return $pack !== null ? $this->latest($pack) : null;
    }

    public function latest(ModPack $pack): ?ModPackVersion
    {
        return $this->versionRepository->findOneBy(['modPack' => $pack], ['id' => 'DESC']);
    }

    /**
     * @return list<ParsedMod>
     */
    public function mods(?ModPackVersion $version): array
    {
        if ($version === null) {
            return [];
        }

        return array_map(
            static fn (ModPackMod $mod) => $mod->asParsed(),
            $this->modRepository->findBy(['version' => $version], ['position' => 'ASC']),
        );
    }

    /**
     * The changelog to offer staff for publishing $mods as the pack's next version.
     *
     * @param list<ParsedMod> $mods
     */
    public function draftChangelog(ModPack $pack, array $mods): string
    {
        $previous = $this->latest($pack);
        if ($previous === null) {
            return sprintf('First version: %d mods and DLC.', count($mods));
        }

        return ModListDiff::changelog($this->mods($previous), $mods);
    }

    /**
     * @param list<ParsedMod> $mods
     */
    public function publish(ModPack $pack, string $label, ?string $changelog, array $mods, ?User $by): ModPackVersion
    {
        $version = new ModPackVersion();
        $version->setModPack($pack);
        $version->setLabel($label);
        $version->setChangelog($changelog);
        $version->setCreatedBy($by);

        $this->em->wrapInTransaction(function () use ($version, $mods): void {
            $this->versionRepository->save($version, false);
            $rows = [];
            foreach ($mods as $position => $mod) {
                $rows[] = new ModPackMod($version, $mod, $position);
            }
            $this->modRepository->saveAll($rows, false);
            $this->modRepository->flush();
        });

        return $version;
    }
}
