<div align="center">
  <img src="./devkit-logo.png" alt="Devkit Profiles" width="120" />
  <h1>devkit-env-profiles</h1>
  <p>Named <code>.env</code> profiles, safe switching with backups, and drift reports — all from one Composer binary.</p>

  [![PHP](https://img.shields.io/badge/PHP-8.3%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
  [![Packagist](https://img.shields.io/packagist/v/devkit/env-profiles?label=packagist&color=f28d1a&logo=packagist&logoColor=white)](https://packagist.org/packages/devkit/env-profiles)
  [![License](https://img.shields.io/badge/license-MIT-22c55e)](LICENSE)
  [![Tests](https://github.com/stuarttodd-dev/devkit-env-profiles/actions/workflows/tests.yml/badge.svg)](https://github.com/stuarttodd-dev/devkit-env-profiles/actions)
</div>

---

## Table of Contents

- [Why this exists](#why-this-exists)
- [Use case — switching environments in seconds](#use-case--switching-environments-in-seconds)
- [Prerequisites](#prerequisites)
- [Install](#install)
- [Running the CLI](#running-the-cli)
- [Commands at a glance](#commands-at-a-glance)
- [Configuration](#configuration)
- [Files and folders](#files-and-folders)
- [Commands](#commands)
  - [`save`](#save--snapshot-a-file-into-a-named-profile)
  - [`use`](#use--apply-a-profile-to-your-working-env-file)
  - [`list`](#list--show-saved-profile-names)
  - [`delete`](#delete--remove-a-profile-from-the-store)
  - [`diff`](#diff--drift-between-env-files)
  - [`merge`](#merge--combine-two-env-files)
- [Library API](#library-api)
- [Development](#development)
- [Support](#support)
- [License](#license)

---

## Why this exists

Managing several environments usually means several files: `.env`, `.env.staging`, secrets in CI, and the nagging question — _"does production still match what we think?"_

This tool gives you a **small profile store** under your repo, a **predictable `use` workflow** (with backups and optional post-switch hooks), and a **`diff` command** to compare any set of env files side by side or as JSON.

---

## Use case — switching environments in seconds

Imagine a typical Laravel app: local dev uses SQLite and the local filesystem, staging uses MySQL and S3-compatible storage, production uses RDS Aurora, S3, and a Redis queue. Without a tool like this, switching means manually editing `.env`, hunting down the right values, and hoping you didn't leave `APP_DEBUG=true` pointing at production.

With `devkit-env-profiles`:

```bash
# One-time setup: snapshot each environment into a named profile
./vendor/bin/devkit-env save local
./vendor/bin/devkit-env save staging
./vendor/bin/devkit-env save production
```

Then switching is a single command — with an automatic backup and optional post-switch hooks (cache clear, migrations, etc.) running for you:

```bash
./vendor/bin/devkit-env use staging
# ✔ Backup of .env written to env/backups/
# ✔ staging profile applied to .env
# ✔ php artisan config:clear
# ✔ php artisan cache:clear

./vendor/bin/devkit-env use production
# ✔ Backup of .env written to env/backups/
# ✔ production profile applied to .env
# ✔ php artisan config:clear
# ✔ php artisan cache:clear
# ✔ php artisan migrate --force --no-interaction
```

And when something feels off, `diff` tells you exactly what diverged:

```bash
./vendor/bin/devkit-env diff local production --format side-by-side
```

```
KEY               local               production
────────────────────────────────────────────────
DB_CONNECTION     sqlite              mysql
FILESYSTEM_DISK   local               s3
QUEUE_CONNECTION  sync                redis
CACHE_DRIVER      file                redis
LOG_LEVEL         debug               — missing —
```

The profiles live outside version control (auto-added to `.gitignore`) so secrets never accidentally land in your repo.

---

## Prerequisites

| Requirement | Version |
|---|---|
| PHP | **8.3+** |
| Composer | any recent |

---

## Install

```bash
composer require --dev devkit/env-profiles
```

<details>
<summary>From a clone of this repo</summary>

```bash
composer install
```

</details>

---

## Running the CLI

The CLI resolves **paths** and **`.devkit-env.json`** relative to the directory you run it from. Always run from your **application root** (where `composer.json` and usually `.env` live).

```bash
# Recommended
./vendor/bin/devkit-env --help

# Alternatives
composer exec devkit-env -- --help
php vendor/bin/devkit-env --help
```

> **Windows:** use `vendor\bin\devkit-env.bat` or `php vendor\bin\devkit-env`.

---

## Commands at a glance

| Command | What it does |
|---|---|
| `save` | Snapshot `./.env` (or `--from`) into a named profile. |
| `use` | Apply a saved profile to your working `.env`, with automatic backup. |
| `list` | Print all saved profile names. |
| `delete` | Remove a profile from the store (does not touch your live `.env`). |
| `diff` | Compare profiles or files; shows missing keys, extra keys, and value drift. |
| `merge` | Merge two env files or profiles; interactive or scriptable. |

---

## Configuration

Drop a **`.devkit-env.json`** in your project root to customise store paths and post-switch hooks.

> **Note:** `defaultEnv` and `targetEnv` only affect `use`. When `save` runs without `--from`, it always reads `./.env` — not these keys.

```json
{
  "storeDir": "env",
  "backupDir": "env/backups",
  "defaultEnv": ".env",
  "afterSwitch": [
    "php artisan config:clear",
    "php artisan cache:clear"
  ],
  "afterSwitchProfiles": {
    "production": [
      "php artisan migrate --force --no-interaction"
    ]
  }
}
```

| Key | Role |
|---|---|
| `storeDir` | Directory for saved profile files and `registry.json`. |
| `backupDir` | Where `use` stores timestamped backups of the replaced file. |
| `defaultEnv` | Path `use` writes a profile to (often `.env`). Relative unless absolute. |
| `targetEnv` | Same as `defaultEnv`; if both are set, **`targetEnv` wins**. |
| `afterSwitch` | Shell commands run after **every** successful `use`. |
| `afterSwitchProfiles` | Extra commands for specific profile names (runs after `afterSwitch`). |

<details>
<summary>Full hook example</summary>

```json
{
  "afterSwitch": [
    "php artisan config:clear",
    "php artisan cache:clear"
  ],
  "afterSwitchProfiles": {
    "staging": [
      "php artisan route:clear"
    ],
    "production": [
      "php artisan optimize",
      "php artisan config:cache"
    ]
  }
}
```

```bash
./vendor/bin/devkit-env use staging      # runs afterSwitch + staging hooks
./vendor/bin/devkit-env use production   # runs afterSwitch + production hooks
./vendor/bin/devkit-env use staging --skip-hooks  # skip all hooks
```

</details>

---

## Files and folders

```
env/                  <- profile store (storeDir)
  staging.env
  production.env
  registry.json       <- name -> filename map
  backups/            <- timestamped backups (backupDir)
```

On first `save`, `use`, `list`, or `delete`, the store and backups directories are automatically appended to `.gitignore`. You can safely commit `.devkit-env.json` (paths only) — keep secrets and `env/` local.

---

## Commands

### `save` — snapshot a file into a named profile

```bash
./vendor/bin/devkit-env save staging                           # save ./.env as "staging"
./vendor/bin/devkit-env save staging --from .env.staging       # save a different file
./vendor/bin/devkit-env save staging --force                   # overwrite without prompting
```

> **Interactive (TTY):** run `save` with no name to pick from a list or type a new one.

---

### `use` — apply a profile to your working env file

```bash
./vendor/bin/devkit-env use staging                            # apply "staging"
./vendor/bin/devkit-env use staging --target .env.local        # write to a specific file
./vendor/bin/devkit-env use staging --backup-dir /tmp/envs     # custom backup location
./vendor/bin/devkit-env use staging --no-backup                # skip backup entirely
```

> **Interactive (TTY):** run `use` without a name to pick from a numbered list.

---

### `list` — show saved profile names

```bash
./vendor/bin/devkit-env list
```

Prints one name per line, or `(no profiles saved yet)` if the store is empty.

---

### `delete` — remove a profile from the store

```bash
./vendor/bin/devkit-env delete staging          # prompts for confirmation
./vendor/bin/devkit-env delete staging --force  # skip confirmation
```

> **Interactive (TTY):** run `delete` without a name to pick from a list.

---

### `diff` — drift between env files

Compares a **baseline** against one or more **targets**: missing keys, extra keys, and mismatched values. Sensitive-looking values are masked by default.

```bash
# Using saved profiles
./vendor/bin/devkit-env diff local staging
./vendor/bin/devkit-env diff local staging production

# Using explicit file paths
./vendor/bin/devkit-env diff \
  --baseline=local \
  --env local=examples/env/local.env \
  --env staging=examples/env/staging.env \
  --env production=examples/env/production.env

# Output formats
./vendor/bin/devkit-env diff local staging --format text
./vendor/bin/devkit-env diff local staging --format json
./vendor/bin/devkit-env diff local staging --format side-by-side

# Masking
./vendor/bin/devkit-env diff local staging --no-mask
./vendor/bin/devkit-env diff local staging --mask-key 'APP_*' --mask-key 'STRIPE_*'
```

**Exit codes:** `0` = no drift &nbsp;·&nbsp; `1` = drift found &nbsp;·&nbsp; `2` = error

---

### `merge` — combine two `.env` files

```bash
# Merge two saved profiles (interactive confirm before overwrite)
./vendor/bin/devkit-env merge local staging

# Merge explicit files to an output file
./vendor/bin/devkit-env merge \
  --left examples/env/local.env \
  --right examples/env/staging.env \
  --out merged.env

# Print to stdout
./vendor/bin/devkit-env merge --left .env --right .env.staging

# Non-interactive (CI-friendly)
./vendor/bin/devkit-env merge --left .env --right .env.staging -n --prefer right --out merged.env

# Dry run
./vendor/bin/devkit-env merge --left .env --right .env.staging --dry-run

# Tickbox selection mode (interactive checklist of right-side changes)
./vendor/bin/devkit-env merge --left .env --right .env.staging --select
```

<details>
<summary><code>--select</code> keybindings</summary>

| Key | Action |
|---|---|
| `1`–`N` | Toggle item |
| `a` | Select all |
| `n` | Select none |
| `v` | Toggle value previews |
| `d` | Done |
| `q` | Cancel |

</details>

---

## Library API

Most users only need the CLI. If you want to integrate programmatically:

```php
require __DIR__ . '/vendor/autoload.php';

// Devkit\Env\Diff\EnvFileParser
// Devkit\Env\Store\ProjectConfig::load()
```

| Namespace | Contents |
|---|---|
| `Devkit\Env\Diff\` | Parsing, comparison, masking, report formatters. |
| `Devkit\Env\Store\` | Config, profile save/apply/list/delete, registry, gitignore hooks, post-switch runner. |

---

## Development

```bash
composer run tests
composer run standards:check
```

---

## Support

If this project saves you time, consider buying me a coffee:

[![Buy Me a Coffee](https://img.shields.io/badge/Buy%20Me%20a%20Coffee-support-FFDD00?logo=buy-me-a-coffee&logoColor=black)](https://buymeacoffee.com/stuarttodd)

---

## License

[MIT](LICENSE)
