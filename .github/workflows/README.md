# GitHub Actions Workflows

This directory contains automated workflows for the envyml project.

## Workflows

### 🔄 CI (`ci.yml`)
**Trigger:** Push to master/7-2, Pull Requests

Runs comprehensive tests across multiple PHP and Symfony versions:
- PHP versions: 7.4, 8.0, 8.1, 8.2, 8.3
- Symfony versions: 4.4, 5.4, 6.4
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

### 🎯 Symfony 7.2/7.4 Integration (`symfony-7-2-integration.yml`)
**Trigger:** Daily cron (3 AM UTC), Manual dispatch

Monitors Symfony 7.4 release and automates branch integration:
- Checks daily for Symfony 7.4 release on Packagist
- Automatically creates PR to merge `7-2` branch into `master` when 7.4 is available
- Creates GitHub issue if manual intervention is needed due to conflicts
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

- `master`: Main stable branch
- `7-2`: Development branch for Symfony 7.2/7.4 support (will merge to master when Symfony 7.4 is released)

## Manual Workflows

All workflows can be manually triggered via:
```bash
gh workflow run <workflow-name>.yml
```

Or through the GitHub UI: Actions → Select workflow → Run workflow
