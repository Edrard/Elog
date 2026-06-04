# Elog

A lightweight PHP library for structured logging and email alerts, powered by Monolog and Symfony Mailer.

Elog is a modern replacement for the older `edrard/mylog` and `edrard/mylogmail` packages. It keeps the convenient static API, but builds it on top of typed configuration objects, a small log manager, Monolog 3, and Symfony Mailer.

## Requirements

- PHP `^8.2`
- Composer
- Monolog `^3.10`
- Symfony Mailer `^7.4`
- `edrard/helpers` `^1.1`

## Installation

This package is currently installed from Git.

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/Edrard/Elog.git"
    }
  ],
  "require": {
    "edrard/elog": "^1.0"
  }
}
```

During local development, install dependencies normally:

```bash
composer install
```

The full reference configuration with all supported options is available in [config/elog.example.json](config/elog.example.json).

## Quick Start

Create a JSON config:

```json
{
  "default_channel": "app",
  "channels": {
    "app": {
      "handler": "file",
      "path": "logs",
      "max_files": 14,
      "levels": ["debug", "info", "warning", "error", "critical"],
      "format": "line",
      "per_run": false,
      "file_names": {
        "debug": "debug.log",
        "info": "info.log",
        "warning": "error.log",
        "error": "error.log",
        "critical": "error.log"
      }
    }
  },
  "mail": {
    "enabled": false
  },
  "processors": {
    "enabled": false
  }
}
```

Boot Elog and write logs:

```php
<?php

use Edrard\Elog\Elog;

require __DIR__ . '/vendor/autoload.php';

Elog::bootFromJson(
    path: __DIR__ . '/config/elog.json',
    projectRoot: dirname(__DIR__),
);

Elog::info('Application started');
Elog::warning('Something should be checked', ['code' => 1001]);
Elog::error('Something failed', ['code' => 500]);
```

Relative paths in JSON are resolved from `projectRoot`, not from the JSON file location.

## Facade API

The `Elog` facade is intentionally thin. It delegates all runtime work to `LogManager`.

```php
Elog::boot($config);
Elog::bootFromJson($path, $projectRoot);
Elog::reconfigure($newConfig);
Elog::channel('app')->info('Direct PSR-3 logger access');
```

`Elog::reconfigure($newConfig)` closes active loggers and applies a fresh runtime configuration. For `per_run` channels this starts a new run file set.

Supported shortcut methods:

```php
Elog::debug('Debug message');
Elog::info('Info message');
Elog::notice('Notice message');
Elog::warning('Warning message');
Elog::error('Error message');
Elog::critical('Critical message');
Elog::alert('Alert message');
Elog::emergency('Emergency message');
```

You can write to one channel:

```php
Elog::error('Import failed', ['id' => 42], 'import');
```

Or to several channels:

```php
Elog::warning('Shared warning', [], ['app', 'import']);
```

Calling a log method before `Elog::boot()` throws an `ElogException`.

## Resetting The Facade

You do not need to call `Elog::reset()` in normal short-lived PHP requests, CLI commands, or cron scripts. The PHP process ends, so the facade state and opened handlers disappear with it.

Use `Elog::reset()` when the same PHP process keeps running and you need a clean logging state:

- PHPUnit tests that boot Elog several times.
- Examples or playground scripts that run multiple isolated scenarios in one process.
- Long-running workers that intentionally rebuild the logger from scratch.
- Manual experiments where you want to clear the static facade before another `boot()`.

`Elog::reset()` closes the current log manager and clears the static facade state. If mail is enabled with `send_on_shutdown: true`, it sends the pending mail digest before clearing the facade. It is not a configuration reload. For normal runtime changes, use `Elog::reconfigure($newConfig)`.

## Channels

Elog uses a simple channel model:

- one channel has one primary handler type;
- levels are an exact allow-list;
- the default channel is configured by `default_channel`;
- channels are created lazily by `LogManager`.

Example:

```json
{
  "default_channel": "app",
  "channels": {
    "app": {
      "handler": "file",
      "path": "logs/app",
      "levels": ["info", "warning", "error", "critical"]
    },
    "import": {
      "handler": "file",
      "path": "logs/import",
      "levels": ["info", "error"]
    }
  }
}
```

If a channel allows only `info` and `critical`, all other levels are ignored:

```json
{
  "levels": ["info", "critical"]
}
```

This is an allow-list, not a minimum severity threshold.

## Handlers

Supported config handler types:

| Handler | Description |
| --- | --- |
| `file` | Writes date-based files and cleans old files through Elog's own retention logic. |
| `stdout` | Writes to `php://stdout`. Useful for CLI and Docker-like workflows. |
| `stderr` | Writes to `php://stderr`. |
| `null` | Discards all matching records. Useful for disabling a channel. |

Switch a channel to stdout for one CLI run:

```php
use Edrard\Elog\Config\HandlerType;
use Edrard\Elog\Config\JsonConfigLoader;
use Edrard\Elog\Elog;

$config = JsonConfigLoader::load(
    path: __DIR__ . '/config/elog.json',
    projectRoot: dirname(__DIR__),
);

if (in_array('--stdout', $argv, true)) {
    $config = $config->withDefaultChannelHandler(HandlerType::Stdout);
}

Elog::boot($config);
```

## File Logging

File channels support:

- `path`
- `max_files`
- `levels`
- `format`
- `per_run`
- `file_names`

Default file names:

```json
{
  "file_names": {
    "debug": "debug.log",
    "info": "info.log",
    "notice": "info.log",
    "warning": "error.log",
    "error": "error.log",
    "critical": "error.log",
    "alert": "error.log",
    "emergency": "error.log"
  }
}
```

When `per_run` is disabled, Elog writes date-based files, for example `info-2026-06-01.log`. Long-running processes automatically start writing to a new file when the date changes.

`max_files` controls retention for each physical file group. `max_files: 10` keeps up to 10 `info-*` files, up to 10 `error-*` files, and so on. Use `max_files: false` to disable cleanup and keep all files.

`file_names` maps log levels to physical files. Levels that point to the same file are grouped together. With the default mapping, `warning`, `error`, and `critical` are written to the same `error-*` file group.

When `per_run` is enabled, Elog writes directly to files for the current boot/run instead of using daily rotated files:

```json
{
  "per_run": true
}
```

Per-run file names use the base log name, the exact run timestamp, and a short run id:

```text
info-2026-06-04_14-21-39_ab12cd.log
error-2026-06-04_14-21-39_ab12cd.log
```

The timestamp format is `Y-m-d_H-i-s`, so files stay easy to sort and read in the log directory. Mail digest collection respects this mode: when a channel uses `per_run: true`, Elog sends only the files from the current boot/run, not the whole daily log history.

## Defaults

If a value is not specified, Elog uses these defaults:

| Option | Default |
| --- | --- |
| `default_channel` | `log` |
| `channels.<name>.handler` | `file` |
| `channels.<name>.path` | `logs` |
| `channels.<name>.max_files` | `14` |
| `channels.<name>.levels` | `["info", "warning", "error", "critical"]` |
| `channels.<name>.format` | `line` |
| `channels.<name>.per_run` | `false` |
| `channels.<name>.file_names.debug` | `debug.log` |
| `channels.<name>.file_names.info` | `info.log` |
| `channels.<name>.file_names.notice` | `info.log` |
| `channels.<name>.file_names.warning` | `error.log` |
| `channels.<name>.file_names.error` | `error.log` |
| `channels.<name>.file_names.critical` | `error.log` |
| `channels.<name>.file_names.alert` | `error.log` |
| `channels.<name>.file_names.emergency` | `error.log` |
| `mail.enabled` | `false` |
| `mail.dsn` | `null` |
| `mail.from` | `null` |
| `mail.to` | `[]` |
| `mail.subject` | `Elog report` |
| `mail.send_on_shutdown` | `true` |
| `mail.separate` | `false` |
| `mail.only_important` | `true` |
| `mail.max_body_size` | `1048576` |
| `mail.attach_logs` | `false` |
| `mail.important_levels` | `["warning", "error", "critical", "alert", "emergency"]` |
| `processors.enabled` | `false` |
| `processors.memory_usage` | `false` |
| `processors.process_id` | `false` |
| `processors.uid` | `false` |
| `processors.request_id` | `false` |

## Formatters

Elog supports two formats:

```json
{
  "format": "line"
}
```

Human-readable line format.

```json
{
  "format": "json"
}
```

Structured JSON records, useful for log collectors and automated parsing.

## Processors

Processors are optional and disabled by default:

```json
{
  "processors": {
    "enabled": false
  }
}
```

Enable selected processors:

```json
{
  "processors": {
    "enabled": true,
    "memory_usage": true,
    "process_id": true,
    "uid": true,
    "request_id": true
  }
}
```

Available processors:

| Processor | Extra field |
| --- | --- |
| `memory_usage` | `memory_usage` |
| `process_id` | `process_id` |
| `uid` | `uid` |
| `request_id` | `request_id` |

`request_id` reads `HTTP_X_REQUEST_ID`, `HTTP_X_CORRELATION_ID`, or `UNIQUE_ID` from `$_SERVER`. The facade uses `Edrard\Helpers\Variable::serverStrings()` to pass only string server values to the logger factory.

## Typed Configuration

JSON is convenient for application configuration, but you can also build the config directly:

```php
use Edrard\Elog\Config\ChannelConfig;
use Edrard\Elog\Config\ElogConfig;
use Edrard\Elog\Config\HandlerType;
use Edrard\Elog\Elog;
use Monolog\Level;

$config = new ElogConfig(
    defaultChannel: 'cli',
    channels: [
        'cli' => new ChannelConfig(
            name: 'cli',
            handler: HandlerType::Stdout,
            levels: [Level::Debug, Level::Info, Level::Error],
        ),
    ],
);

Elog::boot($config);
```

## Handler Helpers

`Edrard\Elog\Handler\Handlers` provides ready-made Monolog handlers:

```php
use Edrard\Elog\Handler\Handlers;
use Monolog\Logger;

$logger = new Logger('manual', Handlers::stdout());

$logger->info('Manual Monolog usage with Elog helpers.');
```

Available helpers:

- `Handlers::stdout()`
- `Handlers::stderr()`
- `Handlers::null()`

## Timer

`Edrard\Elog\Timer` provides small named timers for measuring execution time.

```php
use Edrard\Elog\Timer;

Timer::start();
Timer::start('import');

// Read elapsed time without stopping the timer.
$current = Timer::elapsed('import', precision: 4);

// Stop the timer and keep its elapsed value stable.
$total = Timer::stop('import', precision: 4);

Timer::reset('import');
Timer::reset(); // reset all timers
```

Available methods:

- `Timer::start($name = 'global')`
- `Timer::restart($name = 'global')`
- `Timer::elapsed($name = 'global', $precision = 2)`
- `Timer::stop($name = 'global', $precision = 2)`
- `Timer::has($name = 'global')`
- `Timer::isRunning($name = 'global')`
- `Timer::reset($name = null)`

Legacy-compatible method names are also available:

- `Timer::startTime()`
- `Timer::endTime()`
- `Timer::getTime()`

## Mail Digest

Elog can collect the current date-based or per-run log files for a file channel and send them through Symfony Mailer.

Mail is optional and disabled by default:

```json
{
  "mail": {
    "enabled": false
  }
}
```

Enable mail digest:

```json
{
  "mail": {
    "enabled": true,
    "dsn": "smtp://user:pass@smtp.example.com:587",
    "from": "server@example.com",
    "to": ["admin@example.com"],
    "subject": "Server report",
    "send_on_shutdown": true,
    "separate": false,
    "only_important": true,
    "max_body_size": 1048576,
    "attach_logs": false,
    "important_levels": ["warning", "error", "critical", "alert", "emergency"]
  }
}
```

Send manually:

```php
$sent = Elog::sendMailDigest();
```

Send for a specific channel:

```php
$sent = Elog::sendMailDigest('app');
```

When `send_on_shutdown` is enabled, Elog registers a shutdown callback during `Elog::boot()`.

Manual and shutdown delivery are independent. Calling `Elog::sendMailDigest()` sends a digest immediately. If `send_on_shutdown` is also enabled, Elog will send another digest when the process exits or when `Elog::reset()` is called. This is intentional: a long-running worker can send a scheduled daily report manually, then still send a final lifecycle report if the process stops with new errors.

Important behavior:

- mail digest works only for channels with `handler: "file"`;
- if a channel is switched to `stdout`, `stderr`, or `null`, no digest is sent for that channel;
- calling `Elog::reset()` also sends the pending digest when `send_on_shutdown` is enabled;
- `only_important: true` keeps only files containing one of `important_levels`;
- `separate: true` sends one email per collected log file;
- `attach_logs: true` attaches log files instead of embedding their full content in the email body;
- `max_body_size` limits the embedded body size. Elog reads embedded log content only up to this byte limit, so large log files are not loaded fully into memory during shutdown delivery.

For local tests or examples, use Symfony Mailer's null transport:

```json
{
  "dsn": "null://null"
}
```

## Examples

See [examples](examples/README.md).

```bash
php examples/01-basic-json.php
php examples/02-cli-stdout-override.php
php examples/02-cli-stdout-override.php --stdout
php examples/03-multiple-channels.php
php examples/04-typed-config.php
php examples/05-processors-and-json.php
php examples/06-handlers-helper.php
php examples/07-timer.php
php examples/08-mail-digest.php
```

If your environment does not allow writing into the repository directory, set `ELOG_EXAMPLE_PROJECT_ROOT` to a writable directory before running file-based examples.

## Quality

Run tests:

```bash
composer test
```

Run static analysis:

```bash
composer analyse
```

Check coding style:

```bash
composer cs-check
```

Run all checks:

```bash
composer quality
```

On systems without a global Composer command, run the installed tools directly through PHP:

```bash
php vendor/bin/phpunit
php vendor/bin/phpstan analyse src tests --level=max --memory-limit=1G
php vendor/bin/php-cs-fixer fix --dry-run --diff --ansi
```

## Design Notes

- `ElogConfig`, `ChannelConfig`, `MailConfig`, and `ProcessorConfig` are readonly value objects.
- `JsonConfigLoader` reads JSON and returns typed config.
- `PathResolver` resolves relative paths from the project root.
- `LoggerFactory` creates Monolog loggers.
- `LogManager` stores runtime channel instances.
- `Elog` is only a facade over `LogManager`.

## License

MIT.
