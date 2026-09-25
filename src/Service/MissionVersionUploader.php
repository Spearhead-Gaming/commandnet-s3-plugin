<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Service;

use Forumify\Core\Entity\User;
use MajesticDev\CommandNetS3\Entity\Mission;
use MajesticDev\CommandNetS3\Entity\MissionVersion;
use MajesticDev\CommandNetS3\Repository\MissionVersionRepository;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Stores an uploaded mission file and records it as a new version of the mission. Shared by the
 * "Upload a version" page and the optional first version on the create-mission form.
 */
class MissionVersionUploader
{
    public function __construct(
        private readonly MissionStorage $storage,
        private readonly MissionVersionRepository $versionRepository,
    ) {
    }

    public function upload(Mission $mission, string $label, ?string $notes, UploadedFile $file, ?User $user): MissionVersion
    {
        $originalName = $file->getClientOriginalName();
        $size = (int)$file->getSize();

        $storedName = $this->storage->store($file);

        $version = new MissionVersion();
        $version->setMission($mission);
        $version->setLabel($label);
        $version->setNotes($notes);
        $version->setOriginalName($originalName);
        $version->setStoredName($storedName);
        $version->setSize($size);
        $version->setUploadedBy($user);

        try {
            $this->versionRepository->save($version);
        } catch (\Throwable $exception) {
            $this->storage->remove($storedName); // don't leave a file nothing points to
            throw $exception;
        }

        return $version;
    }
}
