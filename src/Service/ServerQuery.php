<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Service;

use MajesticDev\CommandNetS3\Entity\GameServer;

/**
 * Asks a game server about itself over the Steam query protocol (A2S_INFO), which Arma
 * servers answer on their query port. That gives name, map, mission, player count and
 * latency. It does not give uptime or the mod list, and it is not RCON.
 *
 * ponytail: servers are queried one after another with a short timeout, so a page of offline
 * servers is slow. Query in parallel or cache results if that starts to matter.
 */
class ServerQuery
{
    private const string REQUEST = "\xFF\xFF\xFF\xFFTSource Engine Query\0";
    private const float TIMEOUT_SECONDS = 1.5;

    /**
     * @return array{name: string, map: string, game: string, players: int, maxPlayers: int, bots: int, pingMs: int}|null
     *         null when the server did not answer or answered with something unexpected
     */
    public function query(GameServer $server): ?array
    {
        $socket = @stream_socket_client(
            sprintf('udp://%s:%d', $server->getHost(), $server->getQueryPort()),
            $errorCode,
            $errorMessage,
            self::TIMEOUT_SECONDS,
        );
        if ($socket === false) {
            return null;
        }

        stream_set_timeout($socket, 1, 500000);

        try {
            $started = microtime(true);
            $response = $this->exchange($socket, self::REQUEST);
            // Current servers reply with a challenge ("A" + 4 bytes) that has to be sent back.
            if ($response !== null && ($response[4] ?? '') === 'A') {
                $response = $this->exchange($socket, self::REQUEST . substr($response, 5, 4));
            }
            $pingMs = (int)round((microtime(true) - $started) * 1000);

            $info = $response !== null ? self::parseInfo($response) : null;

            return $info === null ? null : $info + ['pingMs' => $pingMs];
        } finally {
            fclose($socket);
        }
    }

    /**
     * @param resource $socket
     */
    private function exchange($socket, string $request): ?string
    {
        if (@fwrite($socket, $request) === false) {
            return null;
        }

        $response = @fread($socket, 1400);

        return $response === false || $response === '' ? null : $response;
    }

    /**
     * @return array{name: string, map: string, game: string, players: int, maxPlayers: int, bots: int}|null
     */
    public static function parseInfo(string $packet): ?array
    {
        if (strlen($packet) < 6 || !str_starts_with($packet, "\xFF\xFF\xFF\xFF") || $packet[4] !== 'I') {
            return null;
        }

        // After the 4 byte header, the type byte and the protocol byte come four
        // null-terminated strings: name, map, game folder, game (the mission).
        $position = 6;
        $strings = [];
        for ($i = 0; $i < 4; $i++) {
            $end = strpos($packet, "\0", $position);
            if ($end === false) {
                return null;
            }
            $strings[] = mb_scrub(substr($packet, $position, $end - $position));
            $position = $end + 1;
        }

        // Then a 2 byte app id, followed by players, max players and bots.
        if (strlen($packet) < $position + 5) {
            return null;
        }

        return [
            'name' => $strings[0],
            'map' => $strings[1],
            'game' => $strings[3],
            'players' => ord($packet[$position + 2]),
            'maxPlayers' => ord($packet[$position + 3]),
            'bots' => ord($packet[$position + 4]),
        ];
    }
}
