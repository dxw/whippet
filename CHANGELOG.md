# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Repository option for app generation, so that application.json does not need to be manually edited after generating a new app

### Changed
- The [WordPress template repo](https://github.com/dxw/wordpress-template) is used as the source when generating a new app
- The [WordPress template repo](https://github.com/dxw/wordpress-template) is used as the source when generating a new theme
- Looks for a `main` branch first and falls back to using the `master` branch

### Removed
- The `-c` option when generating an app to include a `gitlab-ci.yml` file is removed, as this does not exist in the new WordPress template repo.
- `whippet generate theme` no longer does auto-namespacing based on directory name, because the default namespace is always just `Theme/`.


## [1.0.1] - 2020-02-14
- Use stable version of rubbishthorclone

## [1.0.0] - 2020-01-17

### Changed
- Composer dependencies updated
- Semantic versioning implemented

## [Earlier releases]

Releases before 1.0.0 predate this changelog.
