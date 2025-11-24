# GitHub Actions Workflows

This directory contains automated workflows for the envyml project.

## Workflows

### 🔄 CI (`ci.yml`)
**Trigger:** Push to master, Pull Requests

Runs comprehensive tests across multiple PHP and Symfony versions:
- PHP versions: 8.2, 8.3, 8.4
- Symfony versions: 7.0, 7.1
- Code quality checks
- Security vulnerability scanning
- PHP compatibility validation

### 🔧 Renovate Auto Merge (`renovate-auto-merge.yml`)
**Trigger:** Renovate bot pull requests

Automatically merges minor and patch dependency updates from Renovate:
- Auto-approves and merges minor/patch updates
- Requires manual review for major updates
- Waits for all CI checks to pass

### ✅ Renovate Config Validation (`renovate-validate.yml`)
**Trigger:** Changes to renovate.json

Validates the Renovate configuration file:
- Ensures renovate.json syntax is correct
- Validates configuration schema
- Displays config in job summary

### 🎯 Symfony 7.4 Upgrade Monitor (`symfony-7-4-upgrade-monitor.yml`)
**Trigger:** Daily cron (3 AM UTC), Manual dispatch

Monitors Symfony 7.4 release and automates upgrade:
- Checks daily for Symfony 7.4 release on Packagist
- Automatically creates PR to update Symfony constraints when 7.4 is available
- Updates version constraints from `7.*` to `^7.0`
- Can be manually triggered via workflow dispatch

## Renovate Configuration

The project uses Renovate bot for automated dependency updates. See `renovate.json` in the root directory.

Key features:
- Groups Symfony packages together
- Auto-merges minor/patch updates
- Runs before 3 AM UTC on Mondays
- Semantic commit messages
- Security vulnerability alerts

## Branch Strategy

- `master`: Main stable branch (currently supports Symfony 7.x and PHP 8.2+)

## Manual Workflows

All workflows can be manually triggered via:
```bash
gh workflow run <workflow-name>.yml
```

Or through the GitHub UI: Actions → Select workflow → Run workflow
