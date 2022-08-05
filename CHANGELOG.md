# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Support for PHP 8

## [2.3.0] - 2023-06-14
### Added
- `whippet deps describe` command, that provides a JSON report on the version tags associated with the commit hashes in `whippet.lock`
- Add --public switch to deploy cmd to deploy public/ directory separately to the WP app.
### Changed
- FIXed deprecation warning in deploy module
- Update security.dxw.com to advisories.dxw.com
- Added setup and test scripts following the "scripts to rule them all pattern".
### Removed
- Support for PHP versions below 7.4

## [2.2.4] - 2022-06-29
### Changed
- Require 6.5.8 as minimum version of `guzzlehttp/guzzle` in `composer.json` as well as `composer.lock`

## [2.2.3] - 2022-06-28
### Changed
- Update `guzzlehttp/guzzle` to patch authorization vulnerabilities in versions < 6.5.8.

## [2.2.2] - 2022-05-30
### Changed
- Update `guzzlehttp/guzzle` to patch a cookie-related vulnerability. This vulnerability does not affect us directly, so just updating as a precaution.

## [2.2.1] - 2022-03-30

- Update GuzzleHttp to patch vulnerability in versions below 1.8.4

## [2.2.0] - 2021-09-30

### Added

- A `whippet dependencies validate` command, that checks whippet.json and whippet.lock are properly formed, and have matching entries as well as just matching hashes

## [2.1.0] - 2021-04-06

### Added
- A `-nogitignore` option when generating a theme, that generates a theme without an accompanying `.gitignore` file

### Changed
- The [WordPress template repo's](https://github.com/dxw/wordpress-template) root `.gitignore` file is used by default when generating a theme

## [2.0.0] - 2020-12-09

### Added
- Repository option for app generation, so that application.json does not need to be manually edited after generating a new app

### Changed
- The [WordPress template repo](https://github.com/dxw/wordpress-template) is used as the source when generating a new app
- The [WordPress template repo](https://github.com/dxw/wordpress-template) is used as the source when generating a new theme
- Looks for a `main` branch first and falls back to using the `master` branch

### Removed
- The `-c` option when generating an app to include a `gitlab-ci.yml` file is removed, as this does not exist in the new WordPress template repo.
- `whippet generate theme` no longer does auto-namespacing based on directory name, because the default namespace is always just `Theme/`.
- Support for PHP 7.0 and 7.1


## [1.0.1] - 2020-02-14
- Use stable version of rubbishthorclone

## [1.0.0] - 2020-01-17

### Changed
- Composer dependencies updated
- Semantic versioning implemented

## [Earlier releases]

Releases before 1.0.0 predate this changelog.
