# CLAUDE.md

Guidance for Claude Code in this repository. Deep references live under `.claude/docs/`.

## What this is

`msstc4php/vo-base`, namespace `MSSTC4PHP\ValueObject`: immutable value objects for PHP >= 8.4 with no framework
dependency at runtime (`deptrac.yaml` forbids every bridged framework's namespace). Bridges
(sibling checkouts, all on `Payload\ValueObjectMapper`): `vo-base-doctrine`, `vo-base-bundle` (Symfony),
`vo-base-laravel`, `vo-base-yii2`, `vo-base-codeigniter4`, `vo-base-cycle`, `vo-base-spiral`.

## Common commands

`COMPOSER=composer-ci.json composer update --ignore-platform-req=php`, then
`COMPOSER=composer-ci.json make check`, `make test`, `make infection`, `make fix`.

## Standard

Tool configuration is byte-identical to the `php84` templates of `msstc4symfony/bundle-standard`; CI's `templates` job
enforces it. Do not edit `.php-cs-fixer.dist.php`, `rector.php`, `phpstan*.neon`, `phpunit.xml.dist`,
`infection.json5`, `Makefile` or `.gitignore` here. The hex layout of that standard does not apply.
