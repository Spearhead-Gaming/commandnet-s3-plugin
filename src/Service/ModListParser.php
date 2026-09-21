<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Service;

use InvalidArgumentException;
use MajesticDev\CommandNetS3\Entity\Enum\ModKind;

/**
 * Reads a mod list from either of the two ways staff can supply one: a typed list, or the HTML
 * preset the Arma 3 Launcher exports (Mods > Preset > Export). Both end up as ParsedMods.
 *
 * The uploaded file is only ever read for names and Steam ids. It is never stored or served back,
 * and the download players get is generated fresh from those ids.
 */
final class ModListParser
{
    public const int MAX_MODS = 500;
    private const int MAX_NAME = 150;
    private const int MAX_BYTES = 2_000_000;

    /**
     * Typed list: "Name | Workshop id or link" per line, "DLC: Name | app id or link" for DLC. A
     * line with just a name is a local mod. Blank lines are skipped.
     *
     * @return list<ParsedMod>
     *
     * @throws InvalidArgumentException with a message that names the offending line
     */
    public static function parseText(string $text): array
    {
        $mods = [];
        foreach (preg_split('/\R/', $text) ?: [] as $number => $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $kind = ModKind::MOD;
            if (preg_match('/^DLC\s*:\s*(.*)$/i', $line, $match) === 1) {
                $kind = ModKind::DLC;
                $line = trim($match[1]);
            }

            [$name, $reference] = array_pad(array_map('trim', explode('|', $line, 2)), 2, '');
            $name = self::cleanName($name);
            if ($name === '') {
                throw new InvalidArgumentException(sprintf('Line %d has no mod name.', $number + 1));
            }

            $steamId = null;
            if ($reference !== '') {
                $steamId = self::extractId($reference, $kind);
                if ($steamId === null) {
                    throw new InvalidArgumentException(sprintf(
                        'Line %d: "%s" is not a %s.',
                        $number + 1,
                        mb_strimwidth($reference, 0, 60, '…'),
                        $kind === ModKind::DLC ? 'Steam store link or app id' : 'Steam Workshop link or id',
                    ));
                }
            }

            $mods[] = new ParsedMod($name, $kind, $steamId);
        }

        return self::finish($mods);
    }

    /**
     * The HTML the Arma 3 Launcher exports: one <tr data-type="ModContainer"> per mod (name in a
     * DisplayName cell, Workshop link in a Link anchor) and one DlcContainer row per DLC.
     *
     * @return list<ParsedMod>
     *
     * @throws InvalidArgumentException when the file isn't a launcher preset
     */
    public static function parsePreset(string $html): array
    {
        if (strlen($html) > self::MAX_BYTES) {
            throw new InvalidArgumentException('That file is too large to be a launcher preset.');
        }

        $dom = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            // NONET: never fetch anything the file points at. The prefix forces UTF-8.
            $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        $xpath = new \DOMXPath($dom);
        $mods = [];

        foreach ([[ModKind::MOD, 'ModContainer'], [ModKind::DLC, 'DlcContainer']] as [$kind, $rowType]) {
            foreach ($xpath->query(sprintf("//tr[@data-type='%s']", $rowType)) ?: [] as $row) {
                $name = self::cleanName((string)$xpath->evaluate("string(.//td[@data-type='DisplayName'])", $row));
                if ($name === '') {
                    continue;
                }

                $steamId = null;
                foreach ($xpath->query('.//a[@href]', $row) ?: [] as $anchor) {
                    $steamId = self::extractId($anchor->getAttribute('href'), $kind);
                    if ($steamId !== null) {
                        break;
                    }
                }

                $mods[] = new ParsedMod($name, $kind, $steamId);
            }
        }

        if ($mods === []) {
            throw new InvalidArgumentException('No mods were found in that file. Export it from the Arma 3 Launcher: Mods, Preset, Export.');
        }

        return self::finish($mods);
    }

    /**
     * The typed-list form of a list, so an edit form can start from what is stored.
     *
     * @param iterable<ParsedMod> $mods
     */
    public static function toText(iterable $mods): string
    {
        $lines = [];
        foreach ($mods as $mod) {
            $lines[] = ($mod->kind === ModKind::DLC ? 'DLC: ' : '') . $mod->name . ($mod->steamId !== null ? ' | ' . $mod->steamId : '');
        }

        return implode("\n", $lines);
    }

    public static function url(ModKind $kind, ?string $steamId): ?string
    {
        if ($steamId === null || preg_match('/^\d{1,20}$/', $steamId) !== 1) {
            return null;
        }

        return $kind === ModKind::DLC
            ? 'https://store.steampowered.com/app/' . $steamId
            : 'https://steamcommunity.com/sharedfiles/filedetails/?id=' . $steamId;
    }

    /**
     * An id typed on its own, or the id inside a Workshop (?id=123) or store (/app/123) link.
     */
    private static function extractId(string $reference, ModKind $kind): ?string
    {
        $reference = trim($reference);
        if (preg_match('/^\d{1,20}$/', $reference) === 1) {
            return $reference;
        }

        $pattern = $kind === ModKind::DLC ? '#store\.steampowered\.com/app/(\d{1,20})#i' : '#steamcommunity\.com/.*[?&]id=(\d{1,20})#i';

        return preg_match($pattern, $reference, $match) === 1 ? $match[1] : null;
    }

    private static function cleanName(string $name): string
    {
        return mb_strimwidth(trim(preg_replace('/\s+/u', ' ', $name) ?? ''), 0, self::MAX_NAME);
    }

    /**
     * Drops repeats (the same mod listed twice) and enforces the size limit.
     *
     * @param list<ParsedMod> $mods
     * @return list<ParsedMod>
     */
    private static function finish(array $mods): array
    {
        $unique = [];
        foreach ($mods as $mod) {
            $unique[$mod->kind->value . '|' . ($mod->steamId ?? mb_strtolower($mod->name))] ??= $mod;
        }

        if (count($unique) > self::MAX_MODS) {
            throw new InvalidArgumentException(sprintf('That list has more than %d mods.', self::MAX_MODS));
        }

        return array_values($unique);
    }
}
