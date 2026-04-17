# devkit-env-diff

Compare `.env` files across named environments (local, staging, production, etc.). Surfaces **missing keys**, **extra keys**, and **value mismatches** against a baseline, with optional masking of sensitive-looking values.

## Prerequisites

- PHP **8.3+**
- [Composer](https://getcomposer.org/)

## Install and run (this repository)

```bash
cd devkit-env-diff
composer install
```

The CLI is available as:

- `vendor/bin/devkit-env-diff` (Composer bin proxy), or  
- `./bin/devkit-env-diff` from the repo root.

Show usage:

```bash
./vendor/bin/devkit-env-diff --help
```

## Quick manual test (copy-paste)

The repo includes example files under [`examples/env/`](examples/env/):

| File | Role |
|------|------|
| `local.env` | Baseline |
| `staging.env` | Target with an extra key vs local |
| `production.env` | Target with missing secret, extra key, and several value differences |

From the project root, after `composer install`:

```bash
./vendor/bin/devkit-env-diff \
  --baseline=local \
  --env local=examples/env/local.env \
  --env staging=examples/env/staging.env \
  --env production=examples/env/production.env
```

You should see sections for **staging** and **production** vs **local**, including:

- `❌ Missing in production: STRIPE_SECRET`
- `⚠️ Extra in staging: DEBUG_MODE`
- `⚠️ Different value: CACHE_DRIVER (...)` (and other mismatches)

Exit code **1** means drift was found; **0** means no differences.

### JSON output

```bash
./vendor/bin/devkit-env-diff \
  --format=json \
  --baseline=local \
  --env local=examples/env/local.env \
  --env production=examples/env/production.env
```

### Masking

By default, values for keys that look sensitive (e.g. `*_SECRET`, `*PASSWORD*`, `API_*`) are shown as `***`. To print raw values:

```bash
./vendor/bin/devkit-env-diff --no-mask --baseline=local \
  --env local=examples/env/local.env \
  --env production=examples/env/production.env
```

Add extra [fnmatch](https://www.php.net/fnmatch) patterns:

```bash
./vendor/bin/devkit-env-diff --mask-key 'CUSTOM_*' ...
```

## Exit codes

| Code | Meaning |
|------|---------|
| 0 | No drift (all targets match baseline for keys and values). |
| 1 | Drift detected (missing, extra, or different values). |
| 2 | Usage error or could not read a file. |

Use exit code **1** in CI to fail a job when environments diverge.

## Library usage

The package namespace is `Devkit\EnvDiff`. Parse files with `EnvFileParser`, compare with `EnvironmentComparer`, or use `MultiEnvironmentDiff` for baseline-vs-many-targets workflows.

## Development

```bash
composer run tests           # Pest
composer run standards:check # PHPCS, PHPMD, PHPStan, Rector dry-run
```

GitHub Actions runs `composer install`, `composer run standards:check`, and `composer run tests` on pull requests to `main`.
