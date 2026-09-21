<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Service;

use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Where uploaded mission files live: a private folder under the install (var/, outside the web
 * root), so a file is only ever reachable through a controller that checks permissions. Files
 * get a generated name, so nothing the user typed ever becomes a path.
 *
 * ponytail: local disk only. If mission files should live on S3 or a shared volume, swap this
 * class for one backed by a Flysystem storage; nothing else touches the disk directly.
 */
class MissionStorage
{
    /** What a Mission Dev can upload: Arma mission PBOs, VT exports and zipped bundles. */
    public const array EXTENSIONS = ['pbo', 'vt', 'zip'];

    private readonly string $directory;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $projectDir)
    {
        $this->directory = $projectDir . '/var/s3-missions';
    }

    /**
     * @return string the stored name to keep on the MissionVersion
     */
    public function store(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, self::EXTENSIONS, true)) {
            throw new InvalidArgumentException('Unsupported mission file type.');
        }

        $storedName = bin2hex(random_bytes(16)) . '.' . $extension;
        $file->move($this->directory, $storedName);

        return $storedName;
    }

    public function path(string $storedName): string
    {
        // Only names this class generated are valid, which also rules out any ../ trickery.
        if (!preg_match('/^[a-f0-9]{32}\.[a-z0-9]{1,4}$/', $storedName)) {
            throw new InvalidArgumentException('Invalid stored file name.');
        }

        return $this->directory . '/' . $storedName;
    }

    public function remove(string $storedName): void
    {
        $path = $this->path($storedName);
        if (is_file($path)) {
            unlink($path);
        }
    }
}
