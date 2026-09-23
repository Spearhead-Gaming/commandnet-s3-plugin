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
     * Why the last query() call returned null, for display next to "No answer" - e.g.
     * distinguishing a DNS/connect failure from a timeout from a malformed reply, which all
     * looked identical before this. Only meaningful immediately after a query() call that
     * returned null; each query() call resets it first.
     */
    private ?string $lastError = null;

    /**
     * @return array{name: string, map: string, game: string, players: int, maxPlayers: int, bots: int, pingMs: int}|null
     *         null when the server did not answer or answered with something unexpected -
     *         see getLastError() for why
     */
    public function query(GameServer $server): ?array
    {
        $this->lastError = null;
        $host = $server->getHost();
        $port = $server->getQueryPort();

        $socket = @stream_socket_client(
            sprintf('udp://%s:%d', $host, $port),
            $errorCode,
            $errorMessage,
            self::TIMEOUT_SECONDS,
        );
        if ($socket === false) {
            $this->lastError = sprintf(
                'Could not open a connection to %s:%d (%s).',
                $host,
                $port,
                $errorMessage !== '' ? $errorMessage : 'unknown error',
            );
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

            if ($response === null) {
                $this->lastError = sprintf(
                    'No response from %s:%d within %.1fs. Check this is really the Steam query'
                    . ' port (often the game port plus 1, but some hosts set it separately),'
                    . ' that the server is running, and that Steam/server-browser querying is'
                    . ' enabled - your host may firewall this port or disable it separately'
                    . ' from the game itself.',
                    $host,
                    $port,
                    self::TIMEOUT_SECONDS,
                );
                return null;
            }

            $info = self::parseInfo($response);
            if ($info === null) {
                $this->lastError = sprintf(
                    'Got a %d byte reply from %s:%d, but it was not a valid Source Engine Query'
                    . ' response - check this is actually the query port.',
                    strlen($response),
                    $host,
                    $port,
                );
                return null;
            }

            return $info + ['pingMs' => $pingMs];
        } finally {
            fclose($socket);
        }
    }

    /**
     * Why the last query() call returned null. Null if the last call succeeded (or none was
     * made yet).
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
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
