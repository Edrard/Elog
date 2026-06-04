<?php

declare(strict_types=1);

namespace Edrard\Elog;

use Edrard\Elog\Config\ElogConfig;
use Edrard\Elog\Config\JsonConfigLoader;
use Edrard\Elog\Exception\ElogException;
use Edrard\Elog\Mail\MailDigestRunner;
use Edrard\Helpers\Variable;
use Psr\Log\LoggerInterface;
use Stringable;

final class Elog
{
    private static ?LogManager $manager = null;
    private static bool $shutdownRegistered = false;

    /**
     * Boots the static facade with a typed configuration object.
     *
     * Calling boot again closes the previous manager and replaces it with a new one.
     */
    public static function boot(ElogConfig $config, ?LoggerFactory $factory = null): LogManager
    {
        $config = self::prepareRuntimeConfig($config);

        self::$manager?->close();
        $manager = new LogManager($config, $factory ?? new LoggerFactory(Variable::serverStrings()));
        self::$manager = $manager;
        self::registerMailShutdown($config);

        return $manager;
    }

    /**
     * Loads JSON configuration and boots the static facade.
     *
     * Relative paths inside the JSON config are resolved from $projectRoot.
     */
    public static function bootFromJson(
        string $path,
        string $projectRoot,
        ?LoggerFactory $factory = null,
    ): LogManager {
        return self::boot(JsonConfigLoader::load($path, $projectRoot), $factory);
    }

    /**
     * Applies a new configuration to the active manager.
     *
     * Existing logger instances are closed and recreated lazily on the next use.
     */
    public static function reconfigure(ElogConfig $config): void
    {
        $config = self::prepareRuntimeConfig($config);

        self::manager()->reconfigure($config);
        self::registerMailShutdown($config);
    }

    /**
     * Returns the active log manager.
     *
     * @throws ElogException When the facade has not been booted yet.
     */
    public static function manager(): LogManager
    {
        return self::$manager ?? throw ElogException::notBooted();
    }

    /**
     * Returns a PSR-3 logger for the given channel or for the default channel.
     *
     * @throws ElogException When the facade has not been booted yet.
     */
    public static function channel(?string $channel = null): LoggerInterface
    {
        return self::manager()->channel($channel);
    }

    /**
     * Closes the active manager and clears facade state.
     *
     * When mail delivery on shutdown is enabled, reset sends the pending
     * digest before clearing the facade.
     */
    public static function reset(): void
    {
        $manager = self::$manager;

        if ($manager === null) {
            return;
        }

        self::sendPendingMailDigest($manager);
        $manager->close();
        self::$manager = null;
    }

    /**
     * Checks whether the facade has an active manager.
     */
    public static function isBooted(): bool
    {
        return self::$manager !== null;
    }

    /**
     * Sends the configured mail digest immediately.
     *
     * Mail digest delivery is skipped when mail is disabled or when the selected
     * channel does not use the file handler.
     *
     * @param string|list<string>|null $channel
     *
     * @throws ElogException When the facade has not been booted yet.
     */
    public static function sendMailDigest(string|array|null $channel = null, ?MailDigestRunner $runner = null): int
    {
        return ($runner ?? new MailDigestRunner())->send(self::manager()->config(), $channel);
    }

    /**
     * Logs an emergency message.
     *
     * @param array<string,mixed>       $context
     * @param string|list<string>|null  $channel
     *
     * @throws ElogException When the facade has not been booted yet.
     */
    public static function emergency(string|Stringable $message, array $context = [], string|array|null $channel = null): void
    {
        self::log(__FUNCTION__, $message, $context, $channel);
    }

    /**
     * Logs an alert message.
     *
     * @param array<string,mixed>       $context
     * @param string|list<string>|null  $channel
     *
     * @throws ElogException When the facade has not been booted yet.
     */
    public static function alert(string|Stringable $message, array $context = [], string|array|null $channel = null): void
    {
        self::log(__FUNCTION__, $message, $context, $channel);
    }

    /**
     * Logs a critical message.
     *
     * @param array<string,mixed>       $context
     * @param string|list<string>|null  $channel
     *
     * @throws ElogException When the facade has not been booted yet.
     */
    public static function critical(string|Stringable $message, array $context = [], string|array|null $channel = null): void
    {
        self::log(__FUNCTION__, $message, $context, $channel);
    }

    /**
     * Logs an error message.
     *
     * @param array<string,mixed>       $context
     * @param string|list<string>|null  $channel
     *
     * @throws ElogException When the facade has not been booted yet.
     */
    public static function error(string|Stringable $message, array $context = [], string|array|null $channel = null): void
    {
        self::log(__FUNCTION__, $message, $context, $channel);
    }

    /**
     * Logs a warning message.
     *
     * @param array<string,mixed>       $context
     * @param string|list<string>|null  $channel
     *
     * @throws ElogException When the facade has not been booted yet.
     */
    public static function warning(string|Stringable $message, array $context = [], string|array|null $channel = null): void
    {
        self::log(__FUNCTION__, $message, $context, $channel);
    }

    /**
     * Logs a notice message.
     *
     * @param array<string,mixed>       $context
     * @param string|list<string>|null  $channel
     *
     * @throws ElogException When the facade has not been booted yet.
     */
    public static function notice(string|Stringable $message, array $context = [], string|array|null $channel = null): void
    {
        self::log(__FUNCTION__, $message, $context, $channel);
    }

    /**
     * Logs an informational message.
     *
     * @param array<string,mixed>       $context
     * @param string|list<string>|null  $channel
     *
     * @throws ElogException When the facade has not been booted yet.
     */
    public static function info(string|Stringable $message, array $context = [], string|array|null $channel = null): void
    {
        self::log(__FUNCTION__, $message, $context, $channel);
    }

    /**
     * Logs a debug message.
     *
     * @param array<string,mixed>       $context
     * @param string|list<string>|null  $channel
     *
     * @throws ElogException When the facade has not been booted yet.
     */
    public static function debug(string|Stringable $message, array $context = [], string|array|null $channel = null): void
    {
        self::log(__FUNCTION__, $message, $context, $channel);
    }

    /**
     * Logs a message at the given PSR-3 level.
     *
     * When $channel is null, the default channel is used. When a list is passed,
     * the same record is written to every named channel.
     *
     * @param array<string,mixed>       $context
     * @param string|list<string>|null  $channel
     *
     * @throws ElogException When the facade has not been booted yet.
     */
    public static function log(
        string $level,
        string|Stringable $message,
        array $context = [],
        string|array|null $channel = null,
    ): void {
        foreach (self::channels($channel) as $channelName) {
            self::manager()->channel($channelName)->log($level, $message, $context);
        }
    }

    /**
     * @param string|list<string>|null $channel
     *
     * @return list<string|null>
     */
    private static function channels(string|array|null $channel): array
    {
        if ($channel === null || is_string($channel)) {
            return [$channel];
        }

        return $channel;
    }

    private static function registerMailShutdown(ElogConfig $config): void
    {
        if (!$config->mail->enabled || !$config->mail->sendOnShutdown || self::$shutdownRegistered) {
            return;
        }

        self::$shutdownRegistered = true;
        register_shutdown_function([self::class, 'sendMailDigestOnShutdown']);
    }

    public static function sendMailDigestOnShutdown(): void
    {
        if (self::$manager === null) {
            return;
        }

        self::sendPendingMailDigest(self::$manager);
    }

    private static function sendPendingMailDigest(LogManager $manager): void
    {
        $config = $manager->config();

        if (!$config->mail->enabled || !$config->mail->sendOnShutdown) {
            return;
        }

        $manager->close();
        (new MailDigestRunner())->send($config);
    }

    private static function prepareRuntimeConfig(ElogConfig $config): ElogConfig
    {
        return $config->withRunSuffix(self::runSuffix());
    }

    private static function runSuffix(): string
    {
        return date('Y-m-d_H-i-s') . '_' . bin2hex(random_bytes(3));
    }
}
