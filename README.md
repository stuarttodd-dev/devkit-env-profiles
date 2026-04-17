# devkit-env

PHP CLI for **named `.env` profiles** (save, switch, backup) and **cross-environment drift reports** (diff). One tool: `devkit-env`.

Composer package: **`devkit/env`**. Binaries: `vendor/bin/devkit-env` (also `vendor/bin/devkit-env-diff` as an alias).

## What it’s for

- **Diff** two or more `.env` files and see missing keys, extra keys, and value mismatches (with optional masking).
- **Manage the active env file** (usually `.env`): copy a saved profile over it, with **automatic backup** of what was there before.
- **Create saved profiles** by copying **any** file (e.g. `save --from .env.staging` — build a library from staging, prod exports, or another machine’s file).
- **Switch** the current project to a saved profile (`use <name>`).
- **List** and **delete** saved profiles (`list`, `delete` / `rm`) without touching your working `.env` unless you `use`.
- **`.devkit-env.json`** can run **shell commands after a switch** (`afterSwitch`, `afterSwitchProfiles`) — e.g. `php artisan config:clear`.

## Prerequisites

- PHP **8.3+**
- Composer

## Install

```bash
composer require devkit/env
# or clone this repo and run:
composer install
```

## Commands overview

| Command | Purpose |
|--------|---------|
| `devkit-env save` | Copy a file into the local store under a **profile name** (`env/` + `registry.json`). |
| `devkit-env use` | Copy a saved profile onto your working env file (e.g. `.env`), **backing up** the previous file first. |
| `devkit-env list` | List saved profile names. |
| `devkit-env delete` / `rm` | Remove a saved profile (registry entry + file under `env/`). |
| `devkit-env diff` | Compare multiple `.env` files (baseline vs targets) — missing keys, extras, value drift, optional masking. |

Run `devkit-env --help` for a short summary. For diff-only flags: `devkit-env diff --help`.

**Legacy invocation:** you can still run `devkit-env-diff …` with the same entrypoint; arguments that start with `-` are treated as **diff** mode (same as before).

## Layout (created in your project root)

Default paths (override with `.devkit-env.json`):

- **`env/`** — stored profile files (e.g. `staging.env`) and **`env/registry.json`** (maps profile names → filenames).
- **`env/backups/`** — timestamped backups of the file being replaced when you `use` a profile.

On first `save`, `use`, `list`, or `delete`, the tool appends a marked block to **`.gitignore`** (or creates the file) so typical patterns are ignored:

- `/env/` (store + backups when using defaults)
- `/env/backups/` only when backup dir is configured outside `env/`

You may commit **`.devkit-env.json`** (paths only); keep **`env/`** and backups out of git.

### Optional config: `.devkit-env.json`

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

- **`defaultEnv`** — default path to the **active** env file this tool manages (often `.env`, or e.g. `config/.env.local`). Relative to the project directory unless absolute.
- **`targetEnv`** — same meaning as **`defaultEnv`**. If both are set, **`targetEnv` wins** (alias for older configs).

You can **override** the default file for a single run:

- `devkit-env use staging --target other/path/.env`
- `devkit-env save --name x --from other/path/.env` (when copying *into* the store; omit `--from` to use the configured default as the source)

- **`afterSwitch`** — shell commands run in order after **every** successful `devkit-env use` (from the project directory).
- **`afterSwitchProfiles`** — optional extra commands for specific profile **names** (same spelling as when you `save` / `use`). Those run **after** the global `afterSwitch` list.

Use **`devkit-env use --skip-hooks`** to apply a profile without running these commands (e.g. in CI).

All paths are relative to the current working directory unless absolute.

## Save a profile

Non-interactive:

```bash
devkit-env save --name staging --from .env.staging
```

Interactive (TTY): run `devkit-env save` — pick an existing profile by **number** to overwrite, or type a **new name**.

- `--force` — overwrite when `--name` already exists.

## Switch active `.env` (use)

```bash
devkit-env use staging
```

- **`--target PATH`** — file to overwrite (default: `targetEnv` from config, usually `.env`).
- **`--backup-dir PATH`** — where backups go (default: `backupDir` from config).
- **`--no-backup`** — do not copy the current target before replacing.

Interactive (TTY): `devkit-env use` without a name shows a numbered list.

## List profiles

```bash
devkit-env list
```

## Delete a saved profile

Removes the name from `env/registry.json` and deletes the corresponding file under `env/`. Does **not** change your current `.env` unless you run `use`.

```bash
devkit-env delete staging
# or
devkit-env rm staging
```

- **`--force`** — skip the “are you sure?” prompt in a TTY.
- Interactive (TTY): `delete` / `rm` with no name shows a numbered list; you still confirm unless `--force`.

## Diff (drift between env files)

Explicit subcommand:

```bash
devkit-env diff --baseline=local \
  --env local=examples/env/local.env \
  --env production=examples/env/production.env
```

Or legacy (no `diff` keyword), same as earlier releases:

```bash
devkit-env --baseline=local --env local=.env --env prod=.env.prod
```

Options: `--format=text|json`, `--no-mask`, `--mask-key PATTERN` (repeatable).  
Exit codes: **0** no drift, **1** drift, **2** error.

## Library API

Namespace **`Devkit\Env`**:

- **`Devkit\Env\Diff\`** — parser, comparer, masking, formatters.
- **`Devkit\Env\Store\`** — `ProjectConfig`, `EnvProfileManager` (save / apply / delete / list), `ProfileRegistry`, `GitignoreManager`, `PostSwitchCommandRunner`.

## Development

```bash
composer run tests
composer run standards:check
```

## License

MIT
