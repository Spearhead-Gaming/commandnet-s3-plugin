<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Service;

use Doctrine\ORM\EntityManagerInterface;
use MajesticDev\CommandNetS3\Entity\Mission;
use MajesticDev\CommandNetS3\Entity\MissionMod;
use MajesticDev\CommandNetS3\Repository\MissionModRepository;

/**
 * A mission's mod list: read it, and replace it as a whole. Replacing (rather than editing one
 * row at a time) is what makes "upload a preset" and "edit the typed list" the same operation.
 */
class MissionModList
{
    public function __construct(
        private readonly MissionModRepository $repository,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * @return list<ParsedMod>
     */
    public function forMission(Mission $mission): array
    {
        return array_map(
            static fn (MissionMod $mod) => $mod->asParsed(),
            $this->repository->findBy(['mission' => $mission], ['position' => 'ASC']),
        );
    }

    public function toText(Mission $mission): string
    {
        return ModListParser::toText($this->forMission($mission));
    }

    /**
     * @param list<ParsedMod> $mods
     */
    public function replace(Mission $mission, array $mods): void
    {
        $this->em->wrapInTransaction(function () use ($mission, $mods): void {
            $this->repository->removeAll($this->repository->findBy(['mission' => $mission]), false);

            $rows = [];
            foreach ($mods as $position => $mod) {
                $rows[] = new MissionMod($mission, $mod, $position);
            }
            $this->repository->saveAll($rows, false);
            $this->repository->flush();
        });
    }
}
