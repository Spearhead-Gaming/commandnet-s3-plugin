<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Service;

use MajesticDev\CommandNetS3\Entity\Enum\ModKind;

/**
 * Drafts a changelog from two mod lists, so staff start from what actually changed. A mod is the
 * same mod when kind and Steam id match; a local mod (no id) is matched by name. A renamed mod
 * with the same id is not a change.
 */
final class ModListDiff
{
    /**
     * @param list<ParsedMod> $old
     * @param list<ParsedMod> $new
     */
    public static function changelog(array $old, array $new): string
    {
        $before = self::index($old);
        $after = self::index($new);

        $lines = [];
        foreach ([[ModKind::MOD, 'Mods'], [ModKind::DLC, 'DLC']] as [$kind, $label]) {
            $added = self::names($after, $before, $kind);
            $removed = self::names($before, $after, $kind);
            if ($added !== []) {
                $lines[] = sprintf('%s added: %s', $label, implode(', ', $added));
            }
            if ($removed !== []) {
                $lines[] = sprintf('%s removed: %s', $label, implode(', ', $removed));
            }
        }

        return $lines === [] ? 'No changes to the mod list.' : implode("\n", $lines);
    }

    /**
     * @param list<ParsedMod> $mods
     * @return array<string, ParsedMod>
     */
    private static function index(array $mods): array
    {
        $index = [];
        foreach ($mods as $mod) {
            $index[$mod->kind->value . '|' . ($mod->steamId ?? mb_strtolower($mod->name))] = $mod;
        }

        return $index;
    }

    /**
     * @param array<string, ParsedMod> $from
     * @param array<string, ParsedMod> $without
     * @return list<string> names in $from of this kind that $without doesn't have
     */
    private static function names(array $from, array $without, ModKind $kind): array
    {
        $names = [];
        foreach ($from as $key => $mod) {
            if ($mod->kind === $kind && !isset($without[$key])) {
                $names[] = $mod->name;
            }
        }

        return $names;
    }
}
