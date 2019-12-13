# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased](https://github.com/B-Galati/monolog-sentry-handler/compare/1.2.1...master)

## [1.2.1](https://github.com/B-Galati/monolog-sentry-handler/compare/1.2.0...1.2.1) - 2019-12-13

### Fixed
- Support initial value being null on array_reduce (#16) - [@jandeschuttere](https://github.com/jandeschuttere) ([095d6d](https://github.com/B-Galati/monolog-sentry-handler/commit/095d6d57e7feaeeb30498f8c4a7fec963b4fde84))

## [1.2.0](https://github.com/B-Galati/monolog-sentry-handler/compare/1.1.0...1.2.0) - 2019-11-06
### Added
- Add extension points (fix #3) (#12)
  >See the [doc](doc/extension-points.md)!
- Re-add .gitattributes as it makes dist lighter (#11)

### Changed
- Suggest Symfony HttplugClient in symfony guide (#13)
- Use Fatal instead of critical level for breadcrumbs (Critical is deprecated) (fix #9) (#11)

### Fixed
- Flush messages on Handler write (fix #10) (#11)
- Bump sentry SDK minimum version to 2.2.3 (#11)

## [1.1.0](https://github.com/B-Galati/monolog-sentry-handler/compare/1.0.0...1.1.0) - 2019-09-26
### Added
- Monolog v2 compatibility (fix #4)
- Bump dependency to "sentry/sentry:^2.2.1" (fix #6)
- Document how to use another http client than curl HTTPlug (fix #7)

### Changed
- Update doc

### Removed
- Remove .gitattributes

## [1.0.0](https://github.com/B-Galati/monolog-sentry-handler/compare/acf546c...1.0.0) - 2019-08-17
### Added
- First version of `SentryHandler`
