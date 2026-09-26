<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Service;

/**
 * Turns the plain text boxes on an Operation Page (one entry per line, columns split with a
 * pipe) into the lists the page template renders. Staff type the text; this is where anything
 * unexpected is dropped, so the template only ever sees clean values.
 */
final class OperationPageParser
{
    /**
     * Lines split on "|" into exactly $columns trimmed cells (missing ones become ''). Blank
     * lines and lines with nothing before the first pipe are skipped.
     *
     * @return list<list<string>>
     */
    public static function rows(?string $text, int $columns): array
    {
        $rows = [];
        foreach (self::lines($text) as $line) {
            $cells = array_map('trim', explode('|', $line, $columns));
            if ($cells[0] === '') {
                continue;
            }
            $rows[] = array_pad($cells, $columns, '');
        }

        return $rows;
    }

    /**
     * The comms plan: "net | frequency" per line. Text saved when the format was "net | channel |
     * frequency" still reads correctly: a line with a third part shows its first and third, so
     * the channel drops out instead of turning up as the frequency.
     *
     * @return list<array{0: string, 1: string}>
     */
    public static function comms(?string $text): array
    {
        return array_map(
            static fn (array $cells): array => [$cells[0], $cells[2] !== '' ? $cells[2] : $cells[1]],
            self::rows($text, 3),
        );
    }

    /**
     * Non-blank trimmed lines.
     *
     * @return list<string>
     */
    public static function lines(?string $text): array
    {
        $lines = preg_split('/\R/', $text ?? '') ?: [];

        return array_values(array_filter(array_map('trim', $lines), static fn (string $line): bool => $line !== ''));
    }

    /**
     * Task organization: blocks separated by a blank line. The first line of a block is
     * "Element | subtitle", every other line is "Role | count".
     *
     * @return list<array{name: string, subtitle: string, rows: list<array{0: string, 1: string}>}>
     */
    public static function taskOrg(?string $text): array
    {
        $blocks = [];
        foreach (preg_split('/\R\s*\R/', trim($text ?? '')) ?: [] as $block) {
            $rows = self::rows($block, 2);
            if ($rows === []) {
                continue;
            }

            $head = array_shift($rows);
            $blocks[] = ['name' => $head[0], 'subtitle' => $head[1], 'rows' => $rows];
        }

        return $blocks;
    }

    /**
     * "Label | URL" lines. Only http(s) links and paths on this site are kept, so a typed
     * javascript: or data: link can never become a clickable link.
     *
     * @return list<array{label: string, url: string}>
     */
    public static function links(?string $text): array
    {
        $links = [];
        foreach (self::rows($text, 2) as [$label, $url]) {
            $safe = self::safeUrl($url);
            if ($safe !== null) {
                $links[] = ['label' => $label, 'url' => $safe];
            }
        }

        return $links;
    }

    public static function safeUrl(?string $url): ?string
    {
        $url = trim($url ?? '');

        // "/path" is fine, "//host" (protocol-relative) is not.
        if (preg_match('#^(https?://\S+|/(?!/)\S*)$#i', $url) === 1) {
            return $url;
        }

        return null;
    }
}
