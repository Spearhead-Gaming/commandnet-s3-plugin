<?php

declare(strict_types=1);

namespace MajesticDev\CommandNetS3\Discord;

use MajesticDev\Discord\Exception\DiscordBotException;
use MajesticDev\Discord\Exception\NoBotRegisteredException;
use MajesticDev\Discord\Service\BotService;
use Psr\Log\LoggerInterface;

/**
 * The one place S3 talks to the Discord plugin. An unreachable or unregistered bot must never
 * stop a Mission Dev from saving their work, so failures are logged and swallowed here.
 */
class DiscordAnnouncer
{
    public function __construct(
        private readonly BotService $botService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function announce(string $message): void
    {
        try {
            $this->botService->postAnnouncement($message);
        } catch (DiscordBotException | NoBotRegisteredException $exception) {
            $this->logger->warning('Could not post the S3 announcement to Discord.', ['exception' => $exception]);
        }
    }

    /**
     * For staff-entered text going into a message: a briefing called "@everyone" must not ping
     * the whole server, so break up every mention with a zero-width space.
     */
    public static function plain(string $text): string
    {
        return str_replace('@', "@\u{200B}", $text);
    }
}
