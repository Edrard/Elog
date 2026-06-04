# Elog Examples

These examples are small executable scripts that show the current Elog API.

Run them from the package root:

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

The file-based examples write runtime logs to `examples/logs/`.

If your environment does not allow writing into the repository directory, set
`ELOG_EXAMPLE_PROJECT_ROOT` to a writable directory before running the examples.
Relative paths from JSON configs will be resolved from that directory.

## What They Cover

- `Elog::bootFromJson()`
- `JsonConfigLoader`
- default channels
- multiple channels
- CLI `--stdout` override
- typed config objects without JSON
- exact level allow-lists
- line and JSON formatters
- optional processors
- ready-made handler helpers
- named timers
- mail digest with Symfony Mailer

The mail digest example sends manually through Symfony Mailer's `null://null`
transport, so it exercises the flow without real email delivery.
